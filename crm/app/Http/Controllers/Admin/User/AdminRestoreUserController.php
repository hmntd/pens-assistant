<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminRestoreUserController extends Controller
{
    /**
     * Restore soft-deleted user.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $user);

        $user->restore();

        return response()->json([
            'status' => 'success',
            'message' => "User #{$user->id} restored successfully.",
        ]);
    }
}
