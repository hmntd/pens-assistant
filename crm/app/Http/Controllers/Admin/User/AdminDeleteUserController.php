<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminDeleteUserController extends Controller
{
    /**
     * Soft delete user account.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);
        Gate::authorize('delete', $user);

        if (Auth::id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => "User #{$user->id} deleted (Soft Delete).",
        ]);
    }
}
