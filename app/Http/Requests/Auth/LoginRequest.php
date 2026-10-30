<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\ClientIp;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Fehlversuche je Konto – egal von welcher IP –, bevor es kurz gesperrt wird */
    private const ACCOUNT_MAX_ATTEMPTS = 20;

    /** Sekunden */
    private const ACCOUNT_DECAY = 900;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Das Kiosk-Tablet bleibt immer angemeldet (Remember-Cookie, ~400 Tage)
        $kiosk = User::query()->where('email', (string) $this->input('email'))->value('role') === User::ROLE_KIOSK;

        if (! Auth::attempt($this->only('email', 'password'), $kiosk || $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountThrottleKey(), self::ACCOUNT_DECAY);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Pro E-Mail+IP (5) und pro Konto über alle IPs hinweg (bremst verteiltes Raten)
        $key = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), 5) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->accountThrottleKey(), self::ACCOUNT_MAX_ATTEMPTS) => $this->accountThrottleKey(),
            default => null,
        };

        if ($key === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.ClientIp::bucket($this->ip()));
    }

    private function accountThrottleKey(): string
    {
        return 'login-account|'.Str::transliterate(Str::lower((string) $this->input('email')));
    }
}
