<?php

namespace App\Http\Middleware;

use App\Services\RedisSessionTrackerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function __construct(
        private readonly RedisSessionTrackerService $sessionTracker
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession() && $request->user()) {
            $this->sessionTracker->trackRequestSession($request);
        }

        return $response;
    }
}
