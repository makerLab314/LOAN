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

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_cannot_manage_master_data_by_default(): void
    {
        $mod = $this->moderator();
        $device = Device::factory()->create();
        $room = Room::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($mod);
        $this->get(route('devices.create'))->assertForbidden();
        $this->post(route('devices.store'), ['title' => 'X', 'category_id' => $category->id, 'total_quantity' => 1])->assertForbidden();
        $this->put(route('devices.update', $device), ['title' => 'X'])->assertForbidden();
        $this->delete(route('devices.destroy', $device))->assertForbidden();
        $this->post(route('rooms.store'), ['name' => 'X', 'location' => 'Y'])->assertForbidden();
        $this->delete(route('rooms.destroy', $room))->assertForbidden();
        $this->post(route('categories.store'), ['name' => 'X'])->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.audit'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();

        $this->assertDatabaseHas('devices', ['id' => $device->id, 'deleted_at' => null]);
    }

    public function test_moderator_can_do_daily_business(): void
    {
        $device = Device::factory()->create();

        $this->actingAs($this->moderator())
            ->post(route('loans.store', $device), [
                'borrower_name' => 'Klasse 7b',
                'loan_start_date' => today()->toDateString(),
                'loan_end_date' => today()->addDays(2)->toDateString(),
                'quantity' => 1,
            ])->assertRedirect(route('devices.show', $device));

        $this->assertDatabaseHas('device_loans', ['device_id' => $device->id, 'borrower_name' => 'Klasse 7b']);
    }

    public function test_permission_matrix_grants_and_revokes_rights(): void
    {
        $mod = $this->moderator();
        $category = Category::factory()->create();

        $this->setting(['permissions.moderation' => ['devices.manage', 'users.manage', 'settings.manage']]);

        $this->actingAs($mod)
            ->post(route('devices.store'), ['title' => 'Beamer', 'category_id' => $category->id, 'total_quantity' => 2])
            ->assertRedirect();
        $this->assertDatabaseHas('devices', ['title' => 'Beamer']);

        // admin_only-Rechte lassen sich nicht an andere Rollen vergeben
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
    }

    public function test_settings_form_cannot_grant_admin_only_permissions(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'permissions'), [
            'permissions' => ['moderation' => ['loans.create', 'settings.manage', 'users.manage', 'bogus']],
        ])->assertRedirect();

        $this->assertSame(['loans.create'], app(Settings::class)->get('permissions.moderation'));
    }

    public function test_room_bookings_of_others_are_protected(): void
    {
        $owner = $this->moderator();
        $other = $this->moderator();
        $booking = RoomBooking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other);
        $this->get(route('bookings.edit', $booking))->assertForbidden();
        $this->delete(route('bookings.destroy', $booking))->assertForbidden();
        $this->assertNotSoftDeleted($booking);

        $this->setting(['permissions.moderation' => ['rooms.book', 'room_bookings.manage_others']]);
        $this->delete(route('bookings.destroy', $booking))->assertRedirect();
        $this->assertSoftDeleted($booking);
    }

    public function test_device_reservations_of_others_are_protected(): void
    {
        $owner = $this->moderator();
        $reservation = DeviceReservation::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($this->moderator())->delete(route('reservations.destroy', $reservation))->assertForbidden();
        $this->assertSame('pending', $reservation->fresh()->status);

        $this->actingAs($owner)->delete(route('reservations.destroy', $reservation))->assertRedirect();
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_last_admin_cannot_be_deleted_or_demoted(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin);
        // sich selbst löschen/degradieren geht nicht
        $this->delete(route('admin.users.destroy', $admin))->assertForbidden();
        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => User::ROLE_MODERATOR, 'locale' => 'de',
        ])->assertForbidden();

        // anderen Admin löschen geht – danach ist $admin der letzte
        $this->delete(route('admin.users.destroy', $otherAdmin))->assertRedirect();
        $this->assertSoftDeleted($otherAdmin);
        $this->assertSame(1, User::activeAdminCount());
    }

    public function test_role_is_not_mass_assignable_via_profile(): void
    {
        $mod = $this->moderator();

        $this->actingAs($mod)->patch(route('profile.update'), [
            'name' => 'Neu', 'email' => $mod->email, 'locale' => 'de', 'role' => 'administration',
        ])->assertRedirect();

        $this->assertSame(User::ROLE_MODERATOR, $mod->fresh()->role);
    }
}
