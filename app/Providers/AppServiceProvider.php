<?php

namespace App\Providers;

use App\Models\DeviceReservation;
use App\Models\RoomBooking;
use App\Models\User;
use App\Policies\DeviceReservationPolicy;
use App\Policies\RoomBookingPolicy;
use App\Policies\UserPolicy;
use App\Support\Audit;
use App\Support\Branding;
use App\Support\Settings;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Links (z. B. in Passwort-Reset-Mails) immer aus APP_URL bauen, nie aus dem
        // Host-Header der Anfrage – sonst lassen sich Reset-Links auf fremde Domains umbiegen.
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && ! $this->app->environment('local')) {
            URL::forceRootUrl($appUrl);
        }
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        $this->applyRuntimeSettings();
        $this->registerAuthorization();
        $this->registerRateLimiters();
        $this->registerAuditListeners();

        Vite::useCspNonce();

        View::share('settings', $this->app->make(Settings::class));
        View::share('branding', $this->app->make(Branding::class));
    }

    private function applyRuntimeSettings(): void
    {
        $settings = $this->app->make(Settings::class);

        $tz = (string) $settings->get('general.timezone');
        if ($tz && in_array($tz, timezone_identifiers_list(), true) && ! $this->app->runningUnitTests()) {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }

        config([
            'app.name' => $settings->get('general.app_name') ?: config('app.name'),
            'session.lifetime' => max(5, (int) $settings->get('security.session_lifetime')),
        ]);

        Password::defaults(function () use ($settings) {
            $rule = Password::min(max(8, (int) $settings->get('security.password_min')))->letters()->numbers();

            return $settings->get('security.password_uncompromised') && ! $this->app->runningUnitTests()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    private function registerAuthorization(): void
    {
        Gate::policy(DeviceReservation::class, DeviceReservationPolicy::class);
        Gate::policy(RoomBooking::class, RoomBookingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Administration darf alles – außer, eine Policy verbietet es ausdrücklich
        // (z. B. den letzten Admin löschen). Deshalb nur für reine Rechte-Gates.
        $permissions = array_keys(config('loan.permissions'));

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return in_array($permission, app(Settings::class)->permissionsFor($user->role), true);
            });
        }
    }

    private function registerRateLimiters(): void
    {
        // Zusätzlich zur Sperre pro E-Mail+IP (LoginRequest): max. 20 Versuche/Minute je IP
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by('login|'.$request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by('pw|'.$request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(120)->by('search|'.($request->user()?->id ?: $request->ip())));
    }

    private function registerAuditListeners(): void
    {
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User ? Audit::log('auth.login', $e->user, [], $e->user) : null);
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User ? Audit::log('auth.logout', $e->user, [], $e->user) : null);
        Event::listen(PasswordReset::class, fn (PasswordReset $e) => $e->user instanceof User ? Audit::log('auth.password_reset', $e->user, [], $e->user) : null);
        Event::listen(Failed::class, function (Failed $e) {
            Audit::log('auth.failed', $e->user instanceof User ? $e->user : null, [
                'email' => (string) ($e->credentials['email'] ?? ''),
            ], null);
        });
    }
}
