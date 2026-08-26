<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\AdminIndexUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AdminIndexUserController extends Controller
{
    /**
     * Display a paginated listing of users with search, role/status filtering, and sorting.
     */
    public function __invoke(AdminIndexUserRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $search = (string) ($validated['search'] ?? '');
        $role = (string) ($validated['role'] ?? '');
        $status = (string) ($validated['status'] ?? '');
        $sortBy = (string) ($validated['sort_by'] ?? 'id');
        $sortDir = strtolower((string) ($validated['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'name', 'email', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'id';
        }

        $query = User::withTrashed();

        // Status Filter
        if ($status === 'active') {
            $query->whereNull('deleted_at')->where('is_suspended', false);
        } elseif ($status === 'suspended') {
            $query->whereNull('deleted_at')->where('is_suspended', true);
        } elseif ($status === 'trashed') {
            $query->onlyTrashed();
        }

        // Role Filter
        if (! empty($role)) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Search Filter
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $sortDir);

        $paginated = $query->paginate($perPage);

        $transformed = array_map(function ($item): array {
            /** @var User $u */
            $u = $item;
            /** @var Role|null $roleModel */
            $roleModel = $u->roles->first();

            return [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $roleModel ? $roleModel->name : 'user',
                'is_admin' => $u->isAdmin(),
                'is_suspended' => (bool) $u->is_suspended,
                'is_trashed' => $u->trashed(),
                'created_at' => $u->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $u->deleted_at?->format('Y-m-d H:i:s'),
            ];
        }, $paginated->items());

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $paginated->currentPage(),
                'data' => $transformed,
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
