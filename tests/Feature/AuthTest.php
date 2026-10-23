<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout(): void
    {
        $user = $this->moderator();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        $user = $this->moderator();

        $this->post('/login', ['email' => $user->email, 'password' => 'falsch'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.failed']);
    }

    public function test_login_is_throttled(): void
    {
        $user = $this->moderator();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'falsch']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deleted_users_cannot_log_in(): void
    {
        $user = $this->moderator();
        $user->delete();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_forgot_password_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $user = $this->moderator();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'niemand@example.org']);

        $known->assertSessionHas('status');
        $unknown->assertSessionHas('status');
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_ends_all_sessions(): void
    {
        Notification::fake();
        $user = $this->moderator();
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NeuesPasswort123',
                'password_confirmation' => 'NeuesPasswort123',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('NeuesPasswort123', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'abc']);
    }

    public function test_cli_creates_admin_with_random_password(): void
    {
        $this->artisan('loan:create-admin', ['email' => 'chef@example.org'])->assertSuccessful();

        $user = User::where('email', 'chef@example.org')->first();
        $this->assertTrue($user->isAdmin());
        $this->assertFalse(Hash::check('geheimespasswort', $user->password));
    }
}
