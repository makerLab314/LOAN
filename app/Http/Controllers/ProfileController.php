<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Name, E-Mail und Sprache. Eine neue E-Mail-Adresse erfordert das aktuelle Passwort.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in(['de', 'en'])],
            'current_password' => [Rule::requiredIf(fn () => strtolower((string) $request->input('email')) !== $user->email), 'nullable', 'current_password'],
        ], [], ['current_password' => __('aktuelles Passwort')]);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => $data['locale'],
        ])->save();

        session(['locale' => $user->locale]);
        Audit::log($emailChanged ? 'user.email_changed' : 'user.profile_updated', $user);

        return back()->with('success', __('Profil gespeichert.'));
    }

    /**
     * Passwort ändern: aktuelles Passwort erforderlich, danach alle anderen Sitzungen beenden.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [], ['current_password' => __('aktuelles Passwort'), 'password' => __('neues Passwort')]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Andere Geräte abmelden: Sitzungen in der DB löschen und Passwort-Hash
        // der aktuellen Sitzung aktualisieren (AuthenticateSession-Middleware)
        Auth::logoutOtherDevices($data['password']);
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();

        Audit::log('user.password_changed', $user);

        return back()->with('success', __('Passwort geändert. Alle anderen Sitzungen wurden abgemeldet.'));
    }

    public function destroy(Request $request, Settings $settings): RedirectResponse
    {
        abort_unless($settings->get('security.allow_self_delete'), 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin() && User::activeAdminCount() <= 1) {
            return back()->with('error', __('Der letzte Admin kann nicht gelöscht werden.'));
        }

        Audit::log('user.self_deleted', $user);
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function theme(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['theme' => ['required', Rule::in(User::THEMES)]]);

        $request->user()->forceFill(['theme' => $data['theme']])->save();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function locale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(['de', 'en'])]]);

        session(['locale' => $data['locale']]);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $data['locale']])->save();
        }

        return back();
    }
}
