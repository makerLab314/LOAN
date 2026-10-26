<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Erste Anmeldung mit einem vom Admin vergebenen Passwort: eigenes festlegen.
 */
class ForcedPasswordController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('home');
        }

        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route('home');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['password' => __('neues Passwort')]);

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => __('Bitte ein anderes als das bisherige Passwort wählen.')]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Wie bei jeder Passwortänderung: andere Sitzungen beenden, diese behalten
        Auth::logoutOtherDevices($data['password']);
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();

        Audit::log('user.password_changed', $user, ['first_login' => true]);

        return redirect()->intended(route('home', absolute: false))->with('success', __('Passwort geändert. Willkommen!'));
    }
}
