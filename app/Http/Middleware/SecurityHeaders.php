<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy (mit Nonce für Scripts) und weitere Sicherheits-Header.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $nonce = Vite::cspNonce();
        $dev = app()->environment('local') && Vite::isRunningHot();
        $devHost = $dev ? ' '.rtrim((string) file_get_contents(public_path('hot'))) : '';
        $devWs = $dev ? ' '.preg_replace('/^http/', 'ws', rtrim((string) file_get_contents(public_path('hot')))) : '';

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'".$devHost,
            // Alpine setzt Inline-Styles (x-show); Tailwind-Utilities kommen aus der CSS-Datei
            "style-src 'self' 'unsafe-inline'".$devHost,
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'".$devHost.$devWs,
            "media-src 'self' blob:",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Angemeldete Seiten nicht im Browser-/Proxy-Cache ablegen
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
