<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vom Admin vergebenes Passwort: Bis ein eigenes gesetzt ist, führt jede Seite
 * zur Passwortänderung. Abmelden, Sprache und Rechtliches bleiben erreichbar.
 */
class RequirePasswordChange
{
    private const ALLOWED = [
        'password.force',
        'password.force.update',
        'logout',
        'locale.switch',
        'profile.theme',
        'legal.*',
        'pwa.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $user->isKiosk() && ! $request->routeIs(...self::ALLOWED)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Bitte zuerst ein neues Passwort festlegen.')], 403)
                : redirect()->route('password.force');
        }

        return $next($request);
    }
}
