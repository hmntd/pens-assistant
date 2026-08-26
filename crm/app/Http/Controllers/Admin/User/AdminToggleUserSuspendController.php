<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminToggleUserSuspendController extends Controller
{
    /**
     * Toggle user suspend status.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->findOrFail($id);
        Gate::authorize('toggleSuspend', $user);

        if (Auth::id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot suspend your own account.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->is_suspended = ! $user->is_suspended;
        $user->save();

        $statusText = $user->is_suspended ? 'suspended' : 'unsuspended';

        return response()->json([
            'status' => 'success',
            'message' => "User account #{$user->id} {$statusText}.",
        ]);
    }
}
