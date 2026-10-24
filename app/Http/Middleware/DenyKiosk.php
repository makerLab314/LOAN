<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sperrt Bereiche für das gemeinsame Kiosk-Konto (Profil, Sitzungen, Suche,
 * Verlauf, Verwaltung …) – unabhängig davon, welche Rechte die Rolle hat.
 */
class DenyKiosk
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isKiosk(), 403);

        return $next($request);
    }
}
