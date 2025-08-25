<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Eingeloggter User hat Vorrang
        if (Auth::check() && Auth::user()->locale) {
            App::setLocale(Auth::user()->locale);
            session(['locale' => Auth::user()->locale]); // Session synchron halten
        } else {
            // 2) Gäste: Session oder Default 'de'
            $locale = session('locale', 'de');
            App::setLocale($locale);
        }

        return $next($request);
    }
}
