<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\AdminUpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminUpdateUserRoleController extends Controller
{
    /**
     * Update user role.
     */
    public function __invoke(AdminUpdateUserRoleRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->findOrFail($id);
        Gate::authorize('updateRole', $user);

        if (Auth::id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot update your own role.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $newRole = (string) $validated['role'];
        $user->syncRoles([$newRole]);

        return response()->json([
            'status' => 'success',
            'message' => "User #{$user->id} role updated to {$newRole}.",
        ]);
    }
}
