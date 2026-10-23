<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceAccessory;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function loan(Device $device, array $data = [])
    {
        return $this->post(route('loans.store', $device), array_merge([
            'borrower_name' => 'Max',
            'loan_start_date' => today()->toDateString(),
            'loan_end_date' => today()->addDays(3)->toDateString(),
            'quantity' => 1,
        ], $data));
    }

    public function test_cannot_overbook_even_without_quantity(): void
    {
        $device = Device::factory()->create(['total_quantity' => 1]);
        $this->actingAs($this->moderator());

        $this->loan($device)->assertSessionHasNoErrors();
        // früher: Feld weglassen → Validierung umgangen → zweite Ausleihe
        $this->loan($device, ['quantity' => null])->assertSessionHasErrors('quantity');

        $this->assertSame(1, DeviceLoan::count());
        $this->assertSame('loaned', $device->fresh()->status);
    }

    public function test_quantity_is_limited_to_available(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $this->actingAs($this->moderator());

        $this->loan($device, ['quantity' => 2])->assertSessionHasNoErrors();
        $this->loan($device, ['quantity' => 2])->assertSessionHasErrors('quantity');
        $this->loan($device, ['quantity' => 1])->assertSessionHasNoErrors();

        $this->assertSame(0, $device->fresh()->available_quantity);
    }

    public function test_reservations_block_loans_in_their_period(): void
    {
        $device = Device::factory()->quantity(2)->create();
        DeviceReservation::factory()->create([
            'device_id' => $device->id, 'quantity' => 2,
            'start_at' => today()->addDay()->setTime(8, 0), 'end_at' => today()->addDays(2)->setTime(16, 0),
        ]);
        $this->actingAs($this->moderator());

        // überschneidet die Vormerkung → nichts frei
        $this->loan($device, ['loan_end_date' => today()->addDays(3)->toDateString()])->assertSessionHasErrors('quantity');
        // endet vor der Vormerkung → ok
        $this->loan($device, ['loan_end_date' => today()->toDateString()])->assertSessionHasNoErrors();
    }

    public function test_reservation_overlap_uses_peak_not_sum(): void
    {
        $device = Device::factory()->quantity(2)->create();
        // zwei Vormerkungen, die sich NICHT überschneiden
        DeviceReservation::factory()->create(['device_id' => $device->id, 'start_at' => today()->addDay()->setTime(8, 0), 'end_at' => today()->addDay()->setTime(10, 0)]);
        DeviceReservation::factory()->create(['device_id' => $device->id, 'start_at' => today()->addDay()->setTime(10, 0), 'end_at' => today()->addDay()->setTime(12, 0)]);

        $this->actingAs($this->moderator())->post(route('reservations.store', $device), [
            'start_date' => today()->addDay()->toDateString(), 'start_time' => '08:00',
            'end_date' => today()->addDay()->toDateString(), 'end_time' => '12:00',
            'quantity' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, DeviceReservation::count());
    }

    public function test_duplicate_unit_is_detected(): void
    {
        $device = Device::factory()->quantity(5)->create(['inventory_number' => 'LS-0012']);
        $this->actingAs($this->moderator());

        $this->loan($device, ['units' => [4], 'borrower_name' => 'Anna'])->assertSessionHasNoErrors();
        $this->loan($device, ['units' => [2, 4], 'borrower_name' => 'Ben'])
            ->assertSessionHasErrors(['units' => 'Exemplar LS-0012-4 ist bereits an Anna verliehen.']);
        $this->loan($device, ['units' => [9]])->assertSessionHasErrors('units');

        $this->loan($device, ['units' => [1, 2], 'quantity' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, DeviceLoan::latest('id')->first()->quantity);
    }

    public function test_partial_and_full_return(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->loan($device, ['units' => [1, 2, 3]]);
        $loan = DeviceLoan::first();

        $this->post(route('loans.return', $loan), ['mode' => 'units', 'units' => [2]])->assertSessionHasNoErrors();
        $loan->refresh();
        $this->assertTrue($loan->isActive());
        $this->assertSame(2, $loan->outstanding_quantity);
        $this->assertSame([1, 3], $loan->load('units')->outstandingUnitNumbers());
        $this->assertSame('partial', $device->fresh()->status);

        $this->post(route('loans.return', $loan), ['mode' => 'quantity', 'quantity' => 5])->assertSessionHasErrors('quantity');

        $this->post(route('loans.return', $loan), ['mode' => 'all'])->assertSessionHasNoErrors();
        $loan->refresh();
        $this->assertFalse($loan->isActive());
        $this->assertSame($admin->id, $loan->returned_by);
        $this->assertSame('available', $device->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'loan.partially_returned']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'loan.returned']);
    }

    public function test_missing_accessories_are_recorded(): void
    {
        $device = Device::factory()->quantity(2)->create();
        $cable = DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Kaltgerätekabel', 'quantity' => 1]);
        $bag = DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Tasche', 'quantity' => 1]);
        $this->actingAs($this->moderator());

        $this->loan($device, ['quantity' => 2, 'accessories_present' => 1, 'accessories' => [$cable->id => 1, $bag->id => 0]]);
        $loan = DeviceLoan::first();
        $this->assertSame(2, $loan->accessories[0]['quantity']);
        $this->assertFalse($loan->accessories[1]['checked']);

        // Checkliste ist Pflicht
        $this->post(route('loans.return', $loan), ['mode' => 'all'])->assertSessionHasErrors('accessories');

        // Kabel fehlt (Tasche wurde nie mitgegeben → zählt nicht)
        $this->post(route('loans.return', $loan), ['mode' => 'all', 'accessories_present' => 1, 'accessories' => [0 => 0]])->assertSessionHasNoErrors();
        $loan->refresh();
        $this->assertFalse($loan->isActive());
        $this->assertSame([['name' => 'Kaltgerätekabel', 'missing' => 2]], $loan->return_issues);
        $this->assertTrue($loan->hasOpenIssues());

        $this->post(route('loans.resolve', $loan), ['note' => 'nachgereicht'])->assertRedirect();
        $this->assertFalse($loan->fresh()->hasOpenIssues());
    }

    public function test_extension_checks_reservations(): void
    {
        $device = Device::factory()->create();
        $this->actingAs($this->moderator());
        $this->loan($device, ['loan_end_date' => today()->addDays(2)->toDateString()]);
        $loan = DeviceLoan::first();

        DeviceReservation::factory()->create(['device_id' => $device->id, 'start_at' => today()->addDays(5)->setTime(8, 0), 'end_at' => today()->addDays(6)]);

        $this->post(route('loans.extend', $loan), ['loan_end_date' => today()->addDays(4)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(today()->addDays(4)->toDateString(), $loan->fresh()->loan_end_date->toDateString());
        $this->assertSame(1, $loan->fresh()->extension_count);

        $this->post(route('loans.extend', $loan), ['loan_end_date' => today()->addDays(7)->toDateString()])->assertSessionHasErrors('loan_end_date');
    }

    public function test_max_duration_setting(): void
    {
        $this->setting(['loans.max_days' => 5]);
        $device = Device::factory()->create();

        $this->actingAs($this->moderator());
        $this->loan($device, ['loan_end_date' => today()->addDays(10)->toDateString()])->assertSessionHasErrors('loan_end_date');
        $this->loan($device, ['loan_end_date' => today()->addDays(5)->toDateString()])->assertSessionHasNoErrors();
    }

    public function test_reservation_can_be_converted_to_loan(): void
    {
        $device = Device::factory()->create();
        $mod = $this->moderator();
        $reservation = DeviceReservation::factory()->create([
            'device_id' => $device->id, 'user_id' => $mod->id, 'reserved_by_name' => 'Frau Müller',
            'start_at' => now()->addHours(2), 'end_at' => now()->addDays(2),
        ]);

        $this->actingAs($mod)->post(route('reservations.convert', $reservation))->assertSessionHasNoErrors();

        $loan = DeviceLoan::first();
        $this->assertSame('Frau Müller', $loan->borrower_name);
        $this->assertSame($reservation->id, $loan->device_reservation_id);
        $this->assertSame('converted', $reservation->fresh()->status);
        $this->assertSame('loaned', $device->fresh()->status);
    }

    public function test_conversion_too_early_is_rejected(): void
    {
        $mod = $this->moderator();
        $reservation = DeviceReservation::factory()->create(['user_id' => $mod->id, 'start_at' => now()->addDays(5), 'end_at' => now()->addDays(6)]);

        $this->actingAs($mod)->post(route('reservations.convert', $reservation))->assertForbidden();
        $this->assertSame(0, DeviceLoan::count());
    }

    public function test_total_quantity_cannot_drop_below_loaned(): void
    {
        $device = Device::factory()->quantity(3)->create();
        DeviceLoan::factory()->create(['device_id' => $device->id, 'quantity' => 2]);

        $this->actingAs($this->admin())->put(route('devices.update', $device), [
            'title' => $device->title, 'category_id' => $device->category_id, 'total_quantity' => 1,
        ])->assertSessionHasErrors('total_quantity');
    }

    public function test_loaned_device_cannot_be_deleted_and_delete_is_soft(): void
    {
        $device = Device::factory()->create();
        $loan = DeviceLoan::factory()->create(['device_id' => $device->id]);
        $this->actingAs($this->admin());

        $this->delete(route('devices.destroy', $device))->assertSessionHas('error');
        $this->assertNotSoftDeleted($device);

        $loan->update(['returned_at' => now(), 'returned_quantity' => 1]);
        $this->delete(route('devices.destroy', $device))->assertRedirect(route('devices.index'));
        $this->assertSoftDeleted($device);
        $this->assertDatabaseHas('device_loans', ['id' => $loan->id]);
    }
}
