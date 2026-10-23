<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['de', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?: $request->session()->get('locale')
            ?: app(Settings::class)->get('general.default_locale', 'de');

        App::setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'de');

        return $next($request);
    }
}
