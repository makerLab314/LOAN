<?php

namespace Tests\Feature;

use App\Http\Controllers\LabelController;
use App\Http\Controllers\SessionController;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceAccessory;
use App\Models\DeviceLoan;
use App\Models\DeviceLoanUnit;
use App\Models\DeviceReservation;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Regressionstests zum Sicherheits-Audit (Host-Header, Sitzungen, Rückgaben, Exporte …).
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function loan(Device $device, array $data = [])
    {
        return $this->post(route('loans.store', $device), array_merge([
            'borrower_name' => 'Max',
            'loan_start_date' => today()->toDateString(),
            'loan_end_date' => today()->addDays(3)->toDateString(),
        ], $data));
    }

    /* ------------------------------------------------------------------ *
     * 1. Host-Header / Proxies
     * ------------------------------------------------------------------ */

    public function test_reset_link_is_built_from_app_url_not_host_header(): void
    {
        Notification::fake();
        $victim = User::factory()->create(['email' => 'victim@example.org']);

        $this->post('http://evil.example/forgot-password', ['email' => 'victim@example.org'])->assertRedirect();

        Notification::assertSentTo($victim, ResetPassword::class, function (ResetPassword $n) use ($victim) {
            $url = $n->toMail($victim)->actionUrl;

            return ! str_contains($url, 'evil.example')
                && str_starts_with($url, rtrim(config('app.url'), '/').'/reset-password/');
        });
    }

    public function test_only_the_app_host_is_trusted(): void
    {
        $patterns = app(TrustHosts::class)->hosts();
        $matches = fn (string $host) => collect($patterns)->contains(fn ($p) => preg_match('{'.$p.'}i', $host));

        $this->assertTrue($matches((string) parse_url(config('app.url'), PHP_URL_HOST)));
        $this->assertTrue($matches('127.0.0.1')); // nginx-Healthcheck
        $this->assertFalse($matches('evil.example'));
        $this->assertFalse($matches('sub.'.parse_url(config('app.url'), PHP_URL_HOST)));
    }

    public function test_forgot_password_does_not_leak_mail_errors(): void
    {
        User::factory()->create(['email' => 'exists@example.org']);
        Password::shouldReceive('sendResetLink')->once()->andThrow(new \RuntimeException('SMTP down'));

        $this->post(route('password.email'), ['email' => 'exists@example.org'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    /* ------------------------------------------------------------------ *
     * 2. Sitzungen & "Angemeldet bleiben"
     * ------------------------------------------------------------------ */

    public function test_admin_revoking_sessions_invalidates_remember_me(): void
    {
        $target = $this->moderator();
        $target->forceFill(['remember_token' => 'old-token'])->save();

        $this->actingAs($this->admin())->delete(route('admin.users.sessions.destroy', $target))->assertRedirect();

        $this->assertNotSame('old-token', $target->fresh()->remember_token);
    }

    public function test_single_session_is_revoked_by_key_without_exposing_its_id(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->moderator();
        $user->forceFill(['remember_token' => 'old-token'])->save();
        $otherId = str_repeat('a', 40);
        DB::table('sessions')->insert([
            'id' => $otherId, 'user_id' => $user->id, 'ip_address' => '10.0.0.1',
            'user_agent' => 'Firefox/1', 'payload' => '', 'last_activity' => time(),
        ]);

        $this->actingAs($user);
        $this->get(route('sessions.index'))->assertOk()
            ->assertDontSee($otherId)
            ->assertSee(SessionController::sessionKey($otherId));

        $this->delete(route('sessions.destroy', SessionController::sessionKey($otherId)))->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => $otherId]);
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
    }

    public function test_admin_changing_own_password_keeps_current_session_valid(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => User::ROLE_ADMIN, 'locale' => 'de',
            'password' => 'NeuesPasswort123', 'password_confirmation' => 'NeuesPasswort123',
        ])->assertRedirect(route('admin.users.index'));

        // AuthenticateSession hat den NEUEN Hash gespeichert → nächste Anfrage bleibt angemeldet
        $this->assertSame(
            Auth::guard('web')->hashPasswordForCookie($admin->fresh()->password),
            session('password_hash_web'),
        );
    }

    public function test_admin_area_requires_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create(); // ohne bestätigtes Passwort

        $this->actingAs($admin)->get(route('admin.users.index'))->assertRedirect(route('password.confirm'));
        $this->actingAs($admin)->get(route('admin.settings'))->assertRedirect(route('password.confirm'));
        $this->actingAs($this->moderator())->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_recreating_a_deleted_user_restores_the_account(): void
    {
        $old = $this->moderator(['email' => 'ehemalig@example.org']);
        $old->delete();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Wieder da', 'email' => 'ehemalig@example.org', 'role' => User::ROLE_MODERATOR, 'locale' => 'de',
            'password' => 'GeheimesPasswort1', 'password_confirmation' => 'GeheimesPasswort1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $restored = User::where('email', 'ehemalig@example.org')->firstOrFail();
        $this->assertSame($old->id, $restored->id);
        $this->assertSame('Wieder da', $restored->name);
    }

    /* ------------------------------------------------------------------ *
     * 3./4. Rückgaben: Exemplare & Zubehör
     * ------------------------------------------------------------------ */

    public function test_partial_return_of_unit_loan_requires_units(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $this->actingAs($this->moderator());
        $this->loan($device, ['units' => [1, 2, 3]])->assertSessionHasNoErrors();
        $loan = DeviceLoan::firstOrFail();

        $this->post(route('loans.return', $loan), ['mode' => 'quantity', 'quantity' => 1])->assertSessionHasErrors('units');
        $this->assertSame(0, $loan->fresh()->returned_quantity);
    }

    public function test_full_return_closes_all_outstanding_units(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $this->actingAs($this->moderator());
        $this->loan($device, ['units' => [1, 2, 3]])->assertSessionHasNoErrors();
        $loan = DeviceLoan::firstOrFail();
        // Altzustand: Menge bereits teilweise zurück, Exemplare aber noch offen
        $loan->update(['returned_quantity' => 1]);

        $this->post(route('loans.return', $loan), ['mode' => 'units', 'units' => [1, 2]])->assertSessionHasNoErrors();

        $this->assertFalse($loan->fresh()->isActive());
        $this->assertSame(0, DeviceLoanUnit::whereNull('returned_at')->count());
        $this->assertSame([], $device->fresh()->loanedUnits());
        $this->loan($device, ['units' => [3], 'borrower_name' => 'Erika'])->assertSessionHasNoErrors();
    }

    public function test_return_dialog_only_submits_checklist_when_it_completes_the_loan(): void
    {
        $device = Device::factory()->quantity(3)->create();
        $acc = DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Netzteil', 'quantity' => 1, 'position' => 0]);
        $this->actingAs($this->moderator());
        $this->loan($device, ['units' => [1, 2, 3], 'accessories_present' => 1, 'accessories' => [$acc->id => 1]])->assertSessionHasNoErrors();

        $html = $this->get(route('devices.show', $device))->assertOk()->getContent();
        $this->assertStringContainsString('<fieldset class="contents" :disabled="!completes" data-return-checklist>', $html);
        // Mit Exemplar-Nummern verliehen → keine "Teilmenge"-Option
        $this->assertStringNotContainsString('name="mode" value="quantity"', $html);
    }

    public function test_quantity_mode_full_return_uses_the_checklist(): void
    {
        $device = Device::factory()->quantity(2)->create();
        $acc = DeviceAccessory::create(['device_id' => $device->id, 'name' => 'Netzteil', 'quantity' => 1, 'position' => 0]);
        $this->actingAs($this->moderator());
        $this->loan($device, ['quantity' => 2, 'accessories_present' => 1, 'accessories' => [$acc->id => 1]])->assertSessionHasNoErrors();
        $loan = DeviceLoan::firstOrFail();

        $this->post(route('loans.return', $loan), [
            'mode' => 'quantity', 'quantity' => 2, 'accessories_present' => 1, 'accessories' => [0 => 1],
        ])->assertSessionHasNoErrors();

        $this->assertFalse($loan->fresh()->isActive());
        $this->assertNull($loan->fresh()->return_issues);
    }

    /* ------------------------------------------------------------------ *
     * 5./6. Export & Etiketten
     * ------------------------------------------------------------------ */

    public function test_csv_export_neutralizes_formulas_in_every_cell(): void
    {
        // Nutzername ist frei wählbar (Profil) – über den echten Weg …
        $evil = $this->moderator();
        $this->actingAs($evil)->patch(route('profile.update'), [
            'name' => '=HYPERLINK("http://evil.example","x")', 'email' => $evil->email, 'locale' => 'de',
        ])->assertSessionHasNoErrors();
        // … und mit führendem Leerzeichen bzw. anderen Formelzeichen
        AuditLog::create(['user_name' => ' =cmd|"/c calc"!A1', 'action' => 'auth.login', 'subject_label' => '@SUM(1+1)']);

        $csv = $this->actingAs($this->admin())->get(route('admin.audit.export'))->streamedContent();

        $this->assertStringContainsString(';"\'=HYPERLINK(', $csv);
        $this->assertStringContainsString(';"\' =cmd|', $csv);
        $this->assertStringContainsString(";'@SUM(1+1);", $csv);
        $this->assertDoesNotMatchRegularExpression('/;"?\s*[=@]/', $csv);
    }

    public function test_label_print_is_capped(): void
    {
        $big = Device::factory()->quantity(LabelController::MAX_LABELS + 1)->create();
        $this->actingAs($this->moderator());
        $params = ['devices' => [$big->id], 'format' => 'a4_3x8', 'code_type' => 'qr'];

        $this->get(route('labels.print', $params + ['mode' => 'units']))->assertSessionHasErrors('devices');
        $this->get(route('labels.print', $params + ['mode' => 'device']))->assertOk();
    }

    /* ------------------------------------------------------------------ *
     * Kleinere Fehler
     * ------------------------------------------------------------------ */

    public function test_reservation_cannot_be_converted_twice(): void
    {
        $device = Device::factory()->quantity(4)->create();
        $user = $this->moderator();
        $reservation = DeviceReservation::factory()->create([
            'device_id' => $device->id, 'user_id' => $user->id, 'quantity' => 1,
            'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        ]);
        $stale = DeviceReservation::findOrFail($reservation->id);
        $service = app(LoanService::class);

        $service->convertReservation($reservation, $user);

        try {
            $service->convertReservation($stale, $user); // zweiter, gleichzeitiger Klick
            $this->fail('Vormerkung wurde zweimal ausgegeben');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reservation', $e->errors());
        }
        $this->assertSame(1, DeviceLoan::count());
    }

    public function test_conversion_requires_exactly_the_reserved_number_of_units(): void
    {
        $device = Device::factory()->quantity(4)->create();
        $user = $this->moderator();
        $reservation = DeviceReservation::factory()->create([
            'device_id' => $device->id, 'user_id' => $user->id, 'quantity' => 2,
            'start_at' => now()->subHour(), 'end_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->post(route('reservations.convert', $reservation), ['units' => [1, 2, 3]])
            ->assertSessionHasErrors('units');
        $this->actingAs($user)->post(route('reservations.convert', $reservation), ['units' => [1, 2]])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, DeviceLoan::firstOrFail()->quantity);
    }

    public function test_reservations_respect_max_loan_days(): void
    {
        $this->setting(['loans.max_days' => 7]);
        $device = Device::factory()->create();
        $this->actingAs($this->moderator());

        $this->post(route('reservations.store', $device), [
            'start_date' => today()->addDay()->toDateString(), 'start_time' => '08:00',
            'end_date' => today()->addDays(30)->toDateString(), 'end_time' => '16:00',
            'quantity' => 1,
        ])->assertSessionHasErrors('end_date');
        $this->assertSame(0, DeviceReservation::count());
    }

    public function test_total_quantity_cannot_drop_below_loaned_units_or_reservations(): void
    {
        $category = Category::factory()->create();
        $device = Device::factory()->quantity(3)->create(['category_id' => $category->id]);
        $this->actingAs($this->admin());
        $update = fn (int $total) => $this->put(route('devices.update', $device), [
            'title' => $device->title, 'category_id' => $category->id, 'total_quantity' => $total,
        ]);

        $this->loan($device, ['units' => [3]])->assertSessionHasNoErrors();
        $update(2)->assertSessionHasErrors('total_quantity');   // Exemplar 3 ist draußen

        DeviceLoan::firstOrFail()->update(['returned_at' => now(), 'returned_quantity' => 1]);
        DeviceLoanUnit::query()->update(['returned_at' => now()]);
        DeviceReservation::factory()->create(['device_id' => $device->id, 'quantity' => 3]);
        $update(2)->assertSessionHasErrors('total_quantity');   // 3 Exemplare vorgemerkt

        DeviceReservation::query()->update(['status' => DeviceReservation::STATUS_CANCELLED]);
        $update(2)->assertSessionHasNoErrors();
        $this->assertSame(2, $device->fresh()->total_quantity);
    }

    public function test_inventory_number_uniqueness_is_case_insensitive(): void
    {
        $category = Category::factory()->create();
        Device::factory()->create(['inventory_number' => 'ABC-1', 'category_id' => $category->id]);

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'inventory_number' => 'abc-1', 'title' => 'Doppelt', 'category_id' => $category->id, 'total_quantity' => 1,
        ])->assertSessionHasErrors('inventory_number');

        $this->assertSame(1, Device::count());
    }
}
