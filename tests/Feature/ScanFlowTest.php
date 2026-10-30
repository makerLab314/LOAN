<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceAccessory;
use App\Models\DeviceLoan;
use App\Models\User;
use App\Services\ReturnScans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ausleihe und Rückgabe per Scan: Einstiege, Sammel-Ausleihe, Kiosk-Rückgabe nur nach Scan.
 */
class ScanFlowTest extends TestCase
{
    use RefreshDatabase;

    private function kiosk(): User
    {
        return User::factory()->kiosk()->create();
    }

    private function checkout(array $data = [])
    {
        return $this->post(route('loans.checkout.store'), array_merge([
            'borrower_name' => 'Klasse 7b',
            'loan_start_date' => today()->toDateString(),
            'loan_end_date' => today()->addDays(3)->toDateString(),
        ], $data));
    }

    private function lend(Device $device, array $data = []): DeviceLoan
    {
        $this->post(route('loans.store', $device), array_merge([
            'borrower_name' => 'Mia',
            'loan_start_date' => today()->toDateString(),
            'loan_end_date' => today()->addDays(3)->toDateString(),
            'quantity' => 1,
        ], $data))->assertSessionHasNoErrors();

        return DeviceLoan::latest('id')->firstOrFail();
    }

    /** Wie am Kiosk: erst scannen (bzw. ID eingeben), dann ausleihen */
    private function lendScanned(Device $device, array $data = []): DeviceLoan
    {
        $this->postJson(route('lookup'), ['code' => $device->inventory_number])->assertOk();

        return $this->lend($device, $data);
    }

    /* ------------------------------------------------------------------ *
     * Einstiege
     * ------------------------------------------------------------------ */

    public function test_lend_button_on_dashboard_leads_to_device_list_with_scan_entry(): void
    {
        $this->actingAs($this->moderator());

        $this->get(route('home'))->assertSee(route('devices.index', ['ausleihen' => 1]), false);
        $this->get(route('devices.index', ['ausleihen' => 1]))->assertSee(route('loans.checkout'), false);
        // Über das Menü (ohne Parameter) kein Scan-Banner
        $this->get(route('devices.index'))->assertDontSee(route('loans.checkout'), false);
    }

    public function test_loans_page_links_to_scan_lending_and_returning(): void
    {
        $this->actingAs($this->moderator())->get(route('loans.index'))
            ->assertSee(route('loans.checkout'), false)
            ->assertSee(route('loans.return-scan'), false);
    }

    public function test_scan_pages_require_the_matching_right(): void
    {
        $this->setting(['permissions.moderation' => ['reservations.create']]);
        $this->actingAs($this->moderator());

        $this->get(route('devices.index', ['ausleihen' => 1]))->assertDontSee(route('loans.checkout'), false);
        $this->get(route('loans.checkout'))->assertForbidden();
        $this->checkout(['items' => [['device_id' => Device::factory()->create()->id]]])->assertForbidden();
        $this->get(route('loans.return-scan'))->assertForbidden();
        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'X'])->assertForbidden();
    }

    public function test_checkout_and_return_pages_render_for_kiosk(): void
    {
        Device::factory()->quantity(3)->create(['title' => 'Tablet']);
        $this->actingAs($this->kiosk());

        $this->get(route('loans.checkout'))->assertOk()->assertSee('data-checkout', false)->assertSee('Tablet');
        $this->get(route('loans.return-scan'))->assertOk()->assertSee('data-return', false);
        $this->get(route('home'))->assertSee(route('loans.return-scan'), false);
    }

    public function test_checkout_page_does_not_expose_borrower_names(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $this->actingAs($this->moderator());
        $this->lend($device, ['units' => [2], 'borrower_name' => 'Geheime Person']);

        $this->get(route('home')); // Erfolgsmeldung mit dem Namen abholen
        $this->get(route('loans.checkout'))->assertOk()->assertDontSee('Geheime Person');
    }

    public function test_lookup_returns_device_and_unit(): void
    {
        $device = Device::factory()->quantity(3)->create(['inventory_number' => 'TAB-0001']);

        $this->actingAs($this->moderator())->postJson(route('lookup'), ['code' => 'tab-0001-2'])
            ->assertOk()->assertJson(['found' => true, 'device_id' => $device->id, 'unit' => 2]);
    }

    /* ------------------------------------------------------------------ *
     * Sammel-Ausleihe
     * ------------------------------------------------------------------ */

    public function test_several_devices_and_units_are_lent_with_one_entry(): void
    {
        $tablets = Device::factory()->quantity(5)->create();
        $cables = Device::factory()->quantity(10)->create();
        $beamer = Device::factory()->create();
        $this->actingAs($this->moderator());

        $this->checkout(['items' => [
            ['device_id' => $tablets->id, 'units' => [1, 3]],
            ['device_id' => $tablets->id, 'units' => [4]],   // gleiches Gerät erneut gescannt
            ['device_id' => $cables->id, 'quantity' => 4],
            ['device_id' => $beamer->id, 'quantity' => 1],
        ]])->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));

        $loans = DeviceLoan::with('units')->get()->keyBy('device_id');
        $this->assertCount(3, $loans);
        $this->assertSame([1, 3, 4], $loans[$tablets->id]->outstandingUnitNumbers());
        $this->assertSame(4, $loans[$cables->id]->quantity);
        $this->assertSame(1, $loans[$beamer->id]->quantity);
        $this->assertTrue($loans->every(fn ($l) => $l->borrower_name === 'Klasse 7b' && $l->loan_end_date->isSameDay(today()->addDays(3))));
    }

    public function test_checkout_is_all_or_nothing(): void
    {
        $free = Device::factory()->create();
        $busy = Device::factory()->create(['title' => 'Kamera']);
        $this->actingAs($this->moderator());
        $this->lend($busy);

        $this->checkout(['items' => [
            ['device_id' => $free->id, 'quantity' => 1],
            ['device_id' => $busy->id, 'quantity' => 1],
        ]])->assertSessionHasErrors('items');

        $this->assertSame(0, $free->loans()->count());
        $this->assertStringContainsString('Kamera', session('errors')->first('items'));
    }

    public function test_checkout_rejects_busy_units_and_empty_lists(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $this->actingAs($this->moderator());
        $this->lend($device, ['units' => [2]]);

        $this->checkout(['items' => [['device_id' => $device->id, 'units' => [2]]]])->assertSessionHasErrors('items');
        $this->checkout(['items' => []])->assertSessionHasErrors('items');
        $this->checkout(['items' => [['device_id' => 999999]]])->assertSessionHasErrors('items.0.device_id');
        $this->assertSame(1, DeviceLoan::count());
    }

    public function test_checkout_requires_accessory_confirmation(): void
    {
        $device = Device::factory()->create();
        DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Netzteil', 'quantity' => 1, 'position' => 0]);
        $this->actingAs($this->moderator());

        $this->checkout(['items' => [['device_id' => $device->id]]])->assertSessionHasErrors('accessories_confirmed');
        $this->checkout(['items' => [['device_id' => $device->id]], 'accessories_confirmed' => '1'])->assertSessionHasNoErrors();

        $this->assertSame([['name' => 'Netzteil', 'quantity' => 1, 'checked' => true]], DeviceLoan::firstOrFail()->accessories);
    }

    public function test_checkout_respects_max_loan_days(): void
    {
        $this->setting(['loans.max_days' => 2]);
        $device = Device::factory()->create();
        $this->actingAs($this->moderator());

        $this->checkout([
            'items' => [['device_id' => $device->id]],
            'loan_end_date' => today()->addDays(10)->toDateString(),
        ])->assertSessionHasErrors('loan_end_date');
    }

    /* ------------------------------------------------------------------ *
     * Rückgabe per Scan
     * ------------------------------------------------------------------ */

    public function test_kiosk_cannot_return_without_scanning(): void
    {
        $device = Device::factory()->create();
        $this->actingAs($this->kiosk());
        $loan = $this->lendScanned($device);

        $this->post(route('loans.return', $loan))->assertSessionHasErrors('code');
        $this->post(route('loans.return-scan.store'), ['returns' => [['loan_id' => $loan->id]]])->assertSessionHasErrors('returns');
        $this->assertTrue($loan->fresh()->isActive());

        // Geräteseite: kein Rückgabe-Dialog, sondern der Weg über den Scan
        $this->get(route('devices.show', $device))
            ->assertDontSee('return-dialog-'.$loan->id)
            ->assertSee(route('loans.return-scan'), false);
    }

    public function test_kiosk_returns_loans_lent_without_scan_without_scanning(): void
    {
        // Etikett abgefallen oder großer Lautsprecher: ohne Scan ausgeliehen
        $device = Device::factory()->create();
        $this->actingAs($this->kiosk());
        $loan = $this->lend($device);
        $this->assertFalse($loan->checkout_scanned);

        $this->get(route('devices.show', $device))->assertSee('return-dialog-'.$loan->id);
        $this->post(route('loans.return', $loan))->assertSessionHasNoErrors();
        $this->assertFalse($loan->fresh()->isActive());
    }

    public function test_kiosk_returns_after_scanning_and_scan_is_used_up(): void
    {
        $device = Device::factory()->create(['inventory_number' => 'BEA-0001']);
        $this->actingAs($this->kiosk());
        $loan = $this->lendScanned($device);

        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'bea-0001'])
            ->assertOk()
            ->assertJsonPath('loans.0.id', $loan->id)
            ->assertJsonPath('loans.0.borrower', 'Mia');

        $this->post(route('loans.return-scan.store'), ['returns' => [['loan_id' => $loan->id]]])
            ->assertSessionHasNoErrors()->assertRedirect(route('loans.return-scan'));
        $this->assertFalse($loan->fresh()->isActive());

        // Der Scan gilt nur für eine Rückgabe
        $second = $this->lendScanned($device);
        $this->post(route('loans.return', $second))->assertSessionHasErrors('code');
        $this->assertTrue($second->fresh()->isActive());
    }

    public function test_kiosk_returns_only_the_scanned_units(): void
    {
        $device = Device::factory()->quantity(4)->create(['inventory_number' => 'TAB-0001']);
        $this->actingAs($this->kiosk());
        $loan = $this->lendScanned($device, ['units' => [1, 2, 3]]);

        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'TAB-0001-2'])->assertOk();

        // Komplettrückgabe ginge nur mit allen Exemplaren
        $this->post(route('loans.return', $loan))->assertSessionHasErrors('units');
        $this->post(route('loans.return-scan.store'), ['returns' => [['loan_id' => $loan->id, 'units' => [2, 3]]]])
            ->assertSessionHasErrors('returns');
        $this->assertSame([1, 2, 3], $loan->fresh()->load('units')->outstandingUnitNumbers());

        $this->post(route('loans.return-scan.store'), ['returns' => [['loan_id' => $loan->id, 'units' => [2]]]])
            ->assertSessionHasNoErrors();
        $this->assertSame([1, 3], $loan->fresh()->load('units')->outstandingUnitNumbers());

        // Geräte-Etikett allein reicht bei Exemplar-Ausleihen nicht
        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'TAB-0001'])->assertOk();
        $this->post(route('loans.return', $loan), ['mode' => 'units', 'units' => [1]])->assertSessionHasErrors('units');
    }

    public function test_kiosk_scan_expires(): void
    {
        $device = Device::factory()->create(['inventory_number' => 'BEA-0001']);
        $this->actingAs($this->kiosk());
        $loan = $this->lendScanned($device);

        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'BEA-0001'])->assertOk();
        $this->travel(ReturnScans::TTL + 60)->seconds();

        $this->post(route('loans.return', $loan))->assertSessionHasErrors('code');
        $this->assertTrue($loan->fresh()->isActive());
    }

    public function test_one_kiosk_scan_covers_one_return_even_in_a_batch(): void
    {
        $device = Device::factory()->quantity(5)->create(['inventory_number' => 'BEA-0001']);
        $this->actingAs($this->kiosk());
        $first = $this->lendScanned($device, ['quantity' => 2]);
        $second = $this->lendScanned($device, ['quantity' => 3]);
        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'BEA-0001'])->assertOk();

        $this->post(route('loans.return-scan.store'), ['returns' => [
            ['loan_id' => $first->id],
            ['loan_id' => $second->id],
        ]])->assertSessionHasErrors('returns');
        $this->assertTrue($first->fresh()->isActive());
        $this->assertTrue($second->fresh()->isActive());

        // Nichts gebucht – der Scan gilt weiter, aber nur für eine Rückgabe
        $this->post(route('loans.return-scan.store'), ['returns' => [['loan_id' => $first->id]]])->assertSessionHasNoErrors();
        $this->assertFalse($first->fresh()->isActive());
        $this->assertTrue($second->fresh()->isActive());
    }

    public function test_staff_can_still_return_directly_and_in_batches(): void
    {
        $a = Device::factory()->create();
        $b = Device::factory()->quantity(5)->create();
        $this->actingAs($this->moderator());
        $first = $this->lend($a);
        $second = $this->lend($b, ['quantity' => 3]);

        $this->post(route('loans.return-scan.store'), ['returns' => [
            ['loan_id' => $first->id],
            ['loan_id' => $second->id, 'quantity' => 2],
        ]])->assertSessionHasNoErrors();

        $this->assertFalse($first->fresh()->isActive());
        $this->assertSame(1, $second->fresh()->outstanding_quantity);

        $this->post(route('loans.return', $second))->assertSessionHasNoErrors();
        $this->assertFalse($second->fresh()->isActive());
    }

    public function test_batch_return_records_missing_accessories(): void
    {
        $device = Device::factory()->create(['inventory_number' => 'BEA-0001']);
        DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Fernbedienung', 'quantity' => 1, 'position' => 0]);
        $this->actingAs($this->kiosk());
        $loan = $this->lend($device);
        $this->postJson(route('loans.return-scan.lookup'), ['code' => 'BEA-0001'])->assertJsonPath('loans.0.accessories.0.name', 'Fernbedienung');

        $this->post(route('loans.return-scan.store'), ['returns' => [
            ['loan_id' => $loan->id, 'accessories_present' => '1', 'accessories' => ['0' => '0']],
        ]])->assertSessionHasNoErrors()->assertSessionHas('warning');

        $this->assertSame([['name' => 'Fernbedienung', 'missing' => 1]], $loan->fresh()->return_issues);
    }

    public function test_return_scan_rejects_unknown_codes(): void
    {
        $this->actingAs($this->kiosk())->postJson(route('loans.return-scan.lookup'), ['code' => 'NIX-9999'])
            ->assertNotFound()->assertJson(['found' => false]);
    }
}
