<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rolle „Raumplanung“: nur Räume, Buchungen und Kalender – Geräte sind ausgeblendet und gesperrt.
 */
class RoomPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function planner(): User
    {
        return User::factory()->roomPlanner()->create(['name' => 'Rita Raum']);
    }

    public function test_device_areas_are_hidden_and_blocked(): void
    {
        $device = Device::factory()->create();
        $this->actingAs($this->planner());

        $this->get(route('devices.index'))->assertForbidden();
        $this->get(route('devices.show', $device))->assertForbidden();
        $this->get(route('loans.index'))->assertForbidden();
        $this->get(route('scan'))->assertForbidden();
        $this->post(route('lookup'), ['code' => 'X'])->assertForbidden();
        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('reservations.create', $device))->assertForbidden();

        $this->get(route('rooms.index'))->assertOk()
            ->assertDontSee(route('devices.index'))
            ->assertDontSee(route('scan'));
    }

    public function test_home_offers_booking_and_calendar_instead_of_devices(): void
    {
        $room = Room::factory()->create(['name' => 'Neuer Saal']);
        RoomBooking::factory()->create(['room_id' => $room->id, 'purpose' => 'WOW-Day', 'start_at' => now()->addHour(), 'end_at' => now()->addHours(2)]);

        $this->actingAs($this->planner())->get(route('home'))
            ->assertOk()
            ->assertSee('Neue Buchung')
            ->assertSee(route('bookings.new'))
            ->assertSee('WOW-Day')
            ->assertDontSee('Gerät ausleihen')
            ->assertDontSee('Inventar-ID');

        // Alle anderen behalten ihre Startseite
        $this->actingAs($this->moderator())->get(route('home'))->assertSee('Gerät ausleihen');
    }

    public function test_new_booking_opens_the_form_with_the_first_room(): void
    {
        Room::factory()->create(['name' => 'Saal B']);
        $first = Room::factory()->create(['name' => 'Aula']);

        $this->actingAs($this->planner())->get(route('bookings.new'))
            ->assertRedirect(route('bookings.create', $first));
    }

    public function test_planner_can_book_and_edit_bookings_of_others(): void
    {
        $room = Room::factory()->create();
        $other = RoomBooking::factory()->create(['room_id' => $room->id]);
        $day = today()->addDays(2)->toDateString();

        $this->actingAs($this->planner());
        $this->post(route('bookings.store', $room), [
            'start_date' => $day, 'start_time' => '10:00', 'end_date' => $day, 'end_time' => '11:00', 'purpose' => 'Elternabend',
        ])->assertSessionHasNoErrors();
        $this->get(route('bookings.edit', $other))->assertOk();
    }

    public function test_calendar_and_search_only_show_rooms(): void
    {
        $room = Room::factory()->create(['name' => 'Beamerraum']);
        RoomBooking::factory()->create(['room_id' => $room->id, 'start_at' => now()->addHour(), 'end_at' => now()->addHours(2)]);
        $device = Device::factory()->create(['title' => 'Beamer Epson']);
        DeviceReservation::factory()->create(['device_id' => $device->id, 'start_at' => now()->addHour(), 'end_at' => now()->addDay()]);
        Category::factory()->create(['name' => 'Beamer']);

        $this->actingAs($this->planner());

        $events = $this->getJson(route('calendar.events', [
            'start' => today()->subDay()->toDateString(), 'end' => today()->addDays(3)->toDateString(),
            'types' => ['rooms', 'reservations', 'loans'],
        ]))->assertOk()->json();
        $this->assertNotEmpty($events);
        $this->assertSame(['b'], array_values(array_unique(array_map(fn ($e) => $e['id'][0], $events))));

        $groups = collect($this->getJson(route('search', ['q' => 'Beamer']))->assertOk()->json('results'))->pluck('type')->unique()->values()->all();
        $this->assertSame(['room'], $groups);
    }

    public function test_device_permissions_cannot_be_given_to_room_planners(): void
    {
        app(Settings::class)->set(['permissions.raumplanung' => ['rooms.book', 'loans.create', 'devices.manage']]);

        $this->assertSame(['rooms.book'], app(Settings::class)->permissionsFor(User::ROLE_ROOMS));
        $this->assertFalse(Settings::assignable(User::ROLE_ROOMS, 'labels.print'));
        $this->assertTrue(Settings::assignable(User::ROLE_ROOMS, 'rooms.manage'));
    }

    public function test_admin_can_assign_the_role(): void
    {
        $user = $this->moderator();

        $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => User::ROLE_ROOMS, 'locale' => 'de',
        ])->assertSessionHasNoErrors();

        $this->assertSame(User::ROLE_ROOMS, $user->fresh()->role);
    }
}
