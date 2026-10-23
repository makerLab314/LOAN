<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceAccessory;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Smoke-Test: alle Seiten rendern ohne Fehler (für Admin und Moderation).
 */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $device = Device::factory()->quantity(3)->create(['inventory_number' => 'KAB-0001']);
        DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Kaltgerätekabel', 'quantity' => 1]);
        $loan = DeviceLoan::factory()->create(['device_id' => $device->id, 'quantity' => 2]);
        $reservation = DeviceReservation::factory()->create(['device_id' => $device->id]);
        $room = Room::factory()->create();
        $booking = RoomBooking::factory()->create(['room_id' => $room->id]);

        return compact('device', 'loan', 'reservation', 'room', 'booking');
    }

    public static function adminPages(): array
    {
        return [
            ['home'], ['devices.index'], ['devices.create'], ['loans.index'], ['loans.history'],
            ['calendar'], ['rooms.index'], ['rooms.create'], ['bookings.index'], ['bookings.archived'],
            ['categories.index'], ['categories.create'], ['labels.index'], ['scan'],
            ['profile.edit'], ['sessions.index'],
            ['admin.users.index'], ['admin.users.create'], ['admin.audit'], ['admin.settings'],
            ['legal.imprint'], ['legal.privacy'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_render(string $route): void
    {
        $this->seedData();

        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    public function test_model_pages_render(): void
    {
        $data = $this->seedData();
        $admin = $this->admin();

        $this->actingAs($admin);
        $this->get(route('devices.show', $data['device']))->assertOk()->assertSee('KAB-0001');
        $this->get(route('devices.show', [$data['device'], 'unit' => 2]))->assertOk()->assertSee('KAB-0001-2');
        $this->get(route('devices.edit', $data['device']))->assertOk()->assertSee('Kaltgerätekabel');
        $this->get(route('reservations.create', $data['device']))->assertOk();
        $this->get(route('bookings.create', $data['room']))->assertOk();
        $this->get(route('bookings.edit', $data['booking']))->assertOk();
        $this->get(route('rooms.edit', $data['room']))->assertOk();
        $this->get(route('categories.edit', Category::first()))->assertOk();
        $this->get(route('admin.users.edit', $this->moderator()))->assertOk();
        $this->get(route('devices.qr', $data['device']))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        foreach (['general', 'loans', 'rooms', 'permissions', 'security', 'labels', 'legal', 'backups'] as $section) {
            $this->get(route('admin.settings', $section))->assertOk();
        }
    }

    public function test_moderator_sees_core_pages(): void
    {
        $data = $this->seedData();

        $this->actingAs($this->moderator());
        foreach (['home', 'devices.index', 'loans.index', 'calendar', 'rooms.index', 'bookings.index', 'scan', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('devices.show', $data['device']))->assertOk()->assertDontSee(route('devices.edit', $data['device']));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/devices')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee(__('Anmelden'));
    }

    public function test_security_headers_are_set(): void
    {
        $response = $this->actingAs($this->admin())->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Cache-Control');
    }

    public function test_calendar_feed_returns_events(): void
    {
        $this->seedData();

        $this->actingAs($this->admin())
            ->getJson(route('calendar.events', ['start' => now()->subWeek()->toDateString(), 'end' => now()->addWeek()->toDateString()]))
            ->assertOk()
            ->assertJsonCount(3);
    }
}
