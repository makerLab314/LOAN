<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kiosk verlassen: Wer am Tablet vorbeikommt, soll es nicht abmelden können –
 * dafür braucht es das Passwort des Kiosk-Kontos.
 */
class KioskController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->isKiosk()) {
            return redirect()->route('home');
        }

        return view('kiosk.exit');
    }

    public function logout(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isKiosk(), 403);

        $request->validate(['password' => ['required', 'string']]);

        if (! Auth::guard('web')->validate(['email' => $request->user()->email, 'password' => $request->input('password')])) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
