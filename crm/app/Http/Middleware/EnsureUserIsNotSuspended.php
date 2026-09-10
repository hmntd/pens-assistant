<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotSuspended
{
    /**
     * Handle an incoming request and block suspended users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_suspended) {
            auth()->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your account has been suspended. Please contact support.'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')->withErrors([
                'email' => __('Your account has been suspended. Please contact support.'),
            ]);
        }

        return $next($request);
    }
}
