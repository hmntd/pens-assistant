<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale')
            ?? $request->header('X-Locale')
            ?? $request->input('locale')
            ?? 'uk';

        if (in_array($locale, ['uk', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
