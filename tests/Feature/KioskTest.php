<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class KioskTest extends TestCase
{
    use RefreshDatabase;

    private function kiosk(): User
    {
        return User::factory()->kiosk()->create(['email' => 'kiosk@example.org']);
    }

    public function test_kiosk_never_gets_rights_outside_the_kiosk_whitelist(): void
    {
        $this->setting(['permissions.kiosk' => ['loans.create', 'devices.manage', 'reservations.manage_others', 'users.manage']]);
        $kiosk = $this->kiosk();

        $this->assertTrue($kiosk->can('loans.create'));
        $this->assertFalse($kiosk->can('devices.manage'));
        $this->assertFalse($kiosk->can('reservations.manage_others'));
        $this->assertFalse($kiosk->can('users.manage'));
        $this->assertFalse($kiosk->can('loans.extend'));
        $this->assertFalse($kiosk->can('loans.resolve'));
    }

    public function test_admin_cannot_save_forbidden_rights_for_kiosk(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'permissions'), [
            'permissions' => ['kiosk' => ['loans.create', 'devices.manage', 'audit.view']],
        ])->assertRedirect();

        $this->assertSame(['loans.create'], app(Settings::class)->get('permissions.kiosk'));
    }

    public function test_kiosk_login_always_sets_remember_cookie(): void
    {
        $this->kiosk();

        $response = $this->post('/login', ['email' => 'kiosk@example.org', 'password' => 'password']);

        $response->assertRedirect('/');
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_normal_login_without_checkbox_sets_no_remember_cookie(): void
    {
        User::factory()->create(['email' => 'mod@example.org']);

        $this->post('/login', ['email' => 'mod@example.org', 'password' => 'password'])
            ->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    public function test_kiosk_is_locked_out_of_personal_and_admin_areas(): void
    {
        $this->actingAs($this->kiosk());

        foreach (['profile.edit', 'sessions.index', 'loans.history', 'bookings.archived', 'admin.users.index', 'admin.audit', 'admin.settings', 'labels.index', 'categories.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->getJson(route('search', ['q' => 'a']))->assertForbidden();
        $this->patch(route('profile.update'), ['name' => 'X', 'email' => 'x@example.org'])->assertForbidden();
        $this->put(route('password.update'), ['current_password' => 'password', 'password' => 'Neues-Passwort-123', 'password_confirmation' => 'Neues-Passwort-123'])->assertForbidden();
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertForbidden();
        $this->delete(route('sessions.destroy-others'), ['password' => 'password'])->assertForbidden();
    }

    public function test_kiosk_sees_overview_devices_calendar_and_rooms_without_search(): void
    {
        $this->actingAs($this->kiosk());

        $home = $this->get(route('home'))->assertOk()->assertSee(__('Vormerken'))->getContent();
        $this->assertStringNotContainsString('search-dialog', $home);
        $this->assertStringNotContainsString('href="'.route('profile.edit').'"', $home);
        $this->assertStringContainsString('data-kiosk-idle', $home);

        foreach (['devices.index', 'loans.index', 'calendar', 'rooms.index', 'bookings.index', 'scan'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_kiosk_page_hides_past_borrowers(): void
    {
        $device = Device::factory()->create();
        DeviceLoan::factory()->create(['device_id' => $device->id, 'borrower_name' => 'Frühere Person', 'returned_at' => now(), 'returned_quantity' => 1]);

        $this->actingAs($this->kiosk())->get(route('devices.show', $device))
            ->assertOk()->assertDontSee('Frühere Person');
    }

    public function test_kiosk_can_lend_and_return_but_not_extend_or_resolve(): void
    {
        $device = Device::factory()->create();
        $this->actingAs($this->kiosk());

        $this->post(route('loans.store', $device), [
            'borrower_name' => 'Mia', 'loan_start_date' => today()->toDateString(),
            'loan_end_date' => today()->addDays(3)->toDateString(), 'quantity' => 1,
        ])->assertSessionHasNoErrors();
        $loan = DeviceLoan::firstOrFail();

        $this->post(route('loans.extend', $loan), ['loan_end_date' => today()->addDays(10)->toDateString()])->assertForbidden();
        $this->post(route('loans.resolve', $loan))->assertForbidden();
        // Rückgabe am Kiosk nur nach Scan bzw. ID-Eingabe (siehe ScanFlowTest)
        $this->postJson(route('loans.return-scan.lookup'), ['code' => $device->inventory_number])->assertOk();
        $this->post(route('loans.return', $loan))->assertSessionHasNoErrors();
        $this->assertFalse($loan->fresh()->isActive());
    }

    public function test_kiosk_reservation_needs_a_name_and_cannot_be_cancelled_at_the_kiosk(): void
    {
        $device = Device::factory()->create();
        $this->actingAs($this->kiosk());
        $payload = [
            'start_date' => today()->addDay()->toDateString(), 'start_time' => '08:00',
            'end_date' => today()->addDay()->toDateString(), 'end_time' => '12:00', 'quantity' => 1,
        ];

        $this->post(route('reservations.store', $device), $payload)->assertSessionHasErrors('reserved_by_name');
        $this->post(route('reservations.store', $device), $payload + ['reserved_by_name' => 'Ben'])->assertSessionHasNoErrors();

        $reservation = DeviceReservation::firstOrFail();
        $this->assertSame('Ben', $reservation->displayName());
        $this->delete(route('reservations.destroy', $reservation))->assertForbidden();
        $this->assertTrue($reservation->fresh()->isActive());
    }

    public function test_kiosk_room_booking_needs_a_name_and_cannot_be_changed(): void
    {
        $room = Room::factory()->create();
        $this->actingAs($this->kiosk());
        $payload = [
            'start_date' => today()->addDay()->toDateString(), 'start_time' => '10:00',
            'end_date' => today()->addDay()->toDateString(), 'end_time' => '12:00', 'purpose' => 'Projekt',
        ];

        $this->post(route('bookings.store', $room), $payload)->assertSessionHasErrors('booked_by_name');
        $this->post(route('bookings.store', $room), $payload + ['booked_by_name' => 'Lea'])->assertSessionHasNoErrors();

        $booking = RoomBooking::firstOrFail();
        $this->assertSame('Lea', $booking->displayName());
        $this->get(route('bookings.index'))->assertSee('Lea');
        $this->get(route('bookings.edit', $booking))->assertForbidden();
        $this->delete(route('bookings.destroy', $booking))->assertForbidden();
        $this->assertNotSoftDeleted($booking);
    }

    public function test_kiosk_logout_requires_the_password(): void
    {
        $kiosk = $this->kiosk();
        $this->actingAs($kiosk);

        $this->post(route('logout'))->assertRedirect(route('kiosk.exit'));
        $this->assertAuthenticatedAs($kiosk);

        $this->post(route('kiosk.exit'), ['password' => 'falsch'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($kiosk);

        $this->post(route('kiosk.exit'), ['password' => 'password'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_kiosk_exit_is_only_for_kiosk_accounts(): void
    {
        $this->actingAs($this->moderator());

        $this->get(route('kiosk.exit'))->assertRedirect(route('home'));
        $this->post(route('kiosk.exit'), ['password' => 'password'])->assertForbidden();
    }

    public function test_admin_can_always_end_kiosk_sessions(): void
    {
        $kiosk = $this->kiosk();
        $token = $kiosk->remember_token;

        $this->actingAs($this->admin())->get(route('admin.users.edit', $kiosk))
            ->assertOk()->assertSee(route('admin.users.sessions.destroy', $kiosk));
        $this->delete(route('admin.users.sessions.destroy', $kiosk))->assertRedirect();

        $this->assertNotSame($token, $kiosk->fresh()->remember_token);
    }

    public function test_pwa_manifest_and_icons_are_public(): void
    {
        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('start_url', '/');

        foreach (['192', '512'] as $size) {
            foreach ([route('pwa.icon', $size), route('pwa.icon.maskable', $size)] as $url) {
                $png = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
                $info = getimagesizefromstring($png);
                $this->assertSame([(int) $size, (int) $size], [$info[0], $info[1]]);
            }
        }
        $this->get('/pwa/icon-9999.png')->assertNotFound();
    }
}
