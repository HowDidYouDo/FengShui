<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Default aus Config
        $locale = config('app.locale');

        // 2. Überschreiben mit Session (falls vorhanden)
        if (session()->has('locale')) {
            $locale = session('locale');
        }

        // 3. Überschreiben mit User-Präferenz (falls eingeloggt)
        // Das hat Vorrang vor der Session (z.B. beim Gerätewechsel)
        if (Auth::check() && Auth::user()->locale) {
            $locale = Auth::user()->locale;

            // Session synchronisieren, damit es beim Logout nicht springt
            if (session('locale') !== $locale) {
                session(['locale' => $locale]);
            }
        }

        // Sprache setzen
        App::setLocale($locale);

        return $next($request);
    }
}
