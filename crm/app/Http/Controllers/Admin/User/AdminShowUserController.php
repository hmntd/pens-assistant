<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AdminShowUserController extends Controller
{
    /**
     * Display extended details for a specific user.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->withCount(['calculatedPensions', 'documents', 'taxHistories'])->findOrFail($id);
        Gate::authorize('view', $user);

        /** @var Role|null $roleModel */
        $roleModel = $user->roles->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'disability_group' => $user->disability_group,
                'benefits' => $user->benefits ?? [],
                'target_retirement_year' => $user->target_retirement_year,
                'role' => $roleModel ? $roleModel->name : 'user',
                'is_admin' => $user->isAdmin(),
                'is_suspended' => (bool) $user->is_suspended,
                'is_trashed' => $user->trashed(),
                'calculations_count' => $user->calculated_pensions_count ?? 0,
                'documents_count' => $user->documents_count ?? 0,
                'tax_histories_count' => $user->tax_histories_count ?? 0,
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $user->deleted_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
