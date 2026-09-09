<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DestroySessionController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($id === 'current' || $id === $request->session()->getId()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('Cannot terminate your current active session from here.'),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->withErrors(['session' => __('Cannot terminate your current active session from here.')]);
        }

        Session::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Session terminated successfully.'),
            ]);
        }

        return back()->with('status', 'session-terminated');
    }
}
