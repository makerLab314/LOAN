<?php

namespace Tests\Feature;

use App\Http\Controllers\SessionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_email_requires_current_password(): void
    {
        $user = $this->moderator(['email' => 'alt@example.org']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => 'neu@example.org', 'locale' => 'de',
        ])->assertSessionHasErrorsIn('profile', 'current_password');
        $this->assertSame('alt@example.org', $user->fresh()->email);

        $this->patch(route('profile.update'), [
            'name' => $user->name, 'email' => 'neu@example.org', 'locale' => 'de', 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertSame('neu@example.org', $user->fresh()->email);
    }

    public function test_name_change_does_not_need_password(): void
    {
        $user = $this->moderator();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Neuer Name', 'email' => $user->email, 'locale' => 'en',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Neuer Name', $user->fresh()->name);
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_password_change_requires_current_password_and_ends_other_sessions(): void
    {
        $user = $this->moderator();
        DB::table('sessions')->insert(['id' => 'anderes-geraet', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'falsch', 'password' => 'NeuesPasswort1', 'password_confirmation' => 'NeuesPasswort1',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'NeuesPasswort1', 'password_confirmation' => 'NeuesPasswort1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NeuesPasswort1', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'anderes-geraet']);
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->actingAs($this->moderator())->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'kurz', 'password_confirmation' => 'kurz',
        ])->assertSessionHasErrorsIn('password', 'password');
    }

    public function test_self_deletion_is_disabled_by_default(): void
    {
        $user = $this->moderator();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertForbidden();
        $this->assertNotSoftDeleted($user);

        $this->setting(['security.allow_self_delete' => true]);
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/login');
        $this->assertSoftDeleted($user);
    }

    public function test_theme_preference_is_saved(): void
    {
        $user = $this->moderator();

        $this->actingAs($user)->postJson(route('profile.theme'), ['theme' => 'dark'])->assertOk();
        $this->assertSame('dark', $user->fresh()->theme);

        $this->postJson(route('profile.theme'), ['theme' => 'pink'])->assertUnprocessable();
    }

    public function test_sessions_can_be_listed_and_revoked(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->moderator();
        $other = $this->moderator();
        DB::table('sessions')->insert([
            ['id' => 'mein-handy', 'user_id' => $user->id, 'ip_address' => '10.0.0.5', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1', 'payload' => '', 'last_activity' => time()],
            ['id' => 'fremd', 'user_id' => $other->id, 'ip_address' => '10.0.0.9', 'user_agent' => '', 'payload' => '', 'last_activity' => time()],
        ]);

        $this->actingAs($user)->get(route('sessions.index'))->assertOk()->assertSee('iPhone')->assertSee('10.0.0.5')->assertDontSee('10.0.0.9');

        // Fremde Sitzungen kann man nicht beenden
        $this->delete(route('sessions.destroy', SessionController::sessionKey('fremd')))->assertRedirect();
        $this->assertDatabaseHas('sessions', ['id' => 'fremd']);

        $this->delete(route('sessions.destroy', SessionController::sessionKey('mein-handy')))->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'mein-handy']);
    }
}
