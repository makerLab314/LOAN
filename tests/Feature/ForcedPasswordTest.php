<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Vom Admin vergebenes Passwort → bei der ersten Anmeldung ein eigenes verlangen.
 */
class ForcedPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const NEW = 'Mein-eigenes-Passwort-42';

    private function createViaAdmin(array $data = []): User
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), array_merge([
            'name' => 'Neu', 'email' => 'neu@example.org', 'role' => User::ROLE_MODERATOR, 'locale' => 'de',
            'password' => 'Start-Passwort-123', 'password_confirmation' => 'Start-Passwort-123',
        ], $data))->assertSessionHasNoErrors();
        auth()->logout();
        // Sonst bleibt der Passwort-Hash des Admins in der Test-Sitzung (AuthenticateSession)
        $this->flushSession();

        return User::where('email', $data['email'] ?? 'neu@example.org')->firstOrFail();
    }

    public function test_new_users_must_change_their_password_by_default(): void
    {
        $user = $this->createViaAdmin();
        $this->assertTrue($user->must_change_password);

        $this->post('/login', ['email' => 'neu@example.org', 'password' => 'Start-Passwort-123']);

        $this->get(route('home'))->assertRedirect(route('password.force'));
        $this->get(route('devices.index'))->assertRedirect(route('password.force'));
        $this->getJson(route('search', ['q' => 'a']))->assertForbidden();
        $this->post(route('loans.checkout.store'), [])->assertRedirect(route('password.force'));
        $this->get(route('password.force'))->assertOk();
        $this->get(route('legal.imprint'))->assertOk();
    }

    public function test_changing_the_password_unlocks_the_app_and_ends_other_sessions(): void
    {
        $user = $this->createViaAdmin();
        DB::table('sessions')->insert(['id' => str_repeat('a', 40), 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($user);

        $this->put(route('password.force.update'), ['password' => 'Start-Passwort-123', 'password_confirmation' => 'Start-Passwort-123'])
            ->assertSessionHasErrors('password');
        $this->put(route('password.force.update'), ['password' => 'kurz', 'password_confirmation' => 'kurz'])
            ->assertSessionHasErrors('password');

        $this->put(route('password.force.update'), ['password' => self::NEW, 'password_confirmation' => self::NEW])
            ->assertSessionHasNoErrors()->assertRedirect(route('home'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check(self::NEW, $user->password));
        $this->assertSame(0, DB::table('sessions')->where('id', str_repeat('a', 40))->count());
        $this->get(route('home'))->assertOk();
        $this->get(route('password.force'))->assertRedirect(route('home'));
    }

    public function test_user_form_offers_the_option_checked_by_default(): void
    {
        $this->actingAs($this->admin())->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('name="must_change_password" value="1" class="checkbox mt-0.5" checked', false);
    }

    public function test_admin_can_skip_the_requirement(): void
    {
        $user = $this->createViaAdmin(['must_change_password' => '0']);

        $this->assertFalse($user->must_change_password);
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    public function test_password_set_by_admin_later_requires_a_change_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'locale' => 'de',
            'password' => 'Neu-gesetzt-123', 'password_confirmation' => 'Neu-gesetzt-123', 'must_change_password' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->must_change_password);

        // Ohne neues Passwort ändert das Häkchen nichts
        $other = User::factory()->create();
        $this->put(route('admin.users.update', $other), [
            'name' => $other->name, 'email' => $other->email, 'role' => $other->role, 'locale' => 'de', 'must_change_password' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->must_change_password);
    }

    public function test_flag_cannot_be_mass_assigned_and_kiosk_is_never_forced(): void
    {
        $kiosk = $this->createViaAdmin(['email' => 'kiosk@example.org', 'role' => User::ROLE_KIOSK, 'must_change_password' => '1']);
        $this->assertFalse($kiosk->must_change_password);

        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'X', 'email' => $user->email, 'locale' => 'de', 'must_change_password' => '1',
        ]);
        $this->assertFalse($user->fresh()->must_change_password);

        // Auch ein (z. B. alter) Kiosk mit gesetztem Flag wird nicht umgeleitet
        $kiosk->forceFill(['must_change_password' => true])->save();
        $this->flushSession();
        $this->actingAs($kiosk)->get(route('home'))->assertOk();
    }

    public function test_password_reset_by_mail_and_cli_admin(): void
    {
        $user = User::factory()->create(['email' => 'mail@example.org']);
        $user->forceFill(['must_change_password' => true])->save();

        $token = Password::createToken($user);
        $this->post(route('password.store'), [
            'token' => $token, 'email' => 'mail@example.org', 'password' => self::NEW, 'password_confirmation' => self::NEW,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->must_change_password);

        Artisan::call('loan:create-admin', ['email' => 'cli@example.org']);
        $this->assertTrue(User::where('email', 'cli@example.org')->firstOrFail()->must_change_password);
    }
}
