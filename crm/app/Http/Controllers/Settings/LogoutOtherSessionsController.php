<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\RedisSessionTrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LogoutOtherSessionsController extends Controller
{
    public function __construct(
        private readonly RedisSessionTrackerService $sessionTracker
    ) {}

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        Auth::logoutOtherDevices($request->password);

        $this->sessionTracker->destroyOtherSessions((string) $user->id, $request->session()->getId());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Logged out of other browser sessions successfully.'),
            ]);
        }

        return back()->with('status', 'other-browser-sessions-logged-out');
    }
}
