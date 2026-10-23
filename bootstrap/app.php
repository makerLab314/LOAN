<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hinter einem Reverse Proxy (z. B. Traefik/nginx mit TLS) laufen –
        // TRUSTED_PROXIES = IP/Bereich des Proxys, z. B. "172.16.0.0/12".
        // "*" vertraut jedem Client, der den Port direkt erreicht (X-Forwarded-For fälschbar).
        $proxies = env('TRUSTED_PROXIES');
        if ($proxies) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Nur Anfragen an den Host aus APP_URL annehmen (plus localhost für Healthchecks)
        $middleware->trustHosts(at: fn () => array_values(array_filter([
            ($host = parse_url((string) config('app.url'), PHP_URL_HOST)) ? '^'.preg_quote($host).'$' : null,
            '^localhost$',
            '^127\.0\.0\.1$',
        ])), subdomains: false);

        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
