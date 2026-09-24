<?php

namespace App\Http\Controllers\Admin\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PensionCalculation\AdminIndexPensionCalculationRequest;
use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminIndexPensionCalculationController extends Controller
{
    /**
     * Display a paginated listing of calculated pensions with search and date filters.
     */
    public function __invoke(AdminIndexPensionCalculationRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $search = (string) ($validated['search'] ?? '');
        $fromDate = (string) ($validated['from_date'] ?? '');
        $toDate = (string) ($validated['to_date'] ?? '');
        $sortBy = (string) ($validated['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($validated['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'created_at', 'final_pension', 'base_pension'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query = CalculatedPension::with('user');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }
                $q->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if (! empty($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if (! empty($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $query->orderBy($sortBy, $sortDir);

        $paginated = $query->paginate($perPage);

        $transformed = collect($paginated->items())->map(function (CalculatedPension $calc): array {
            /** @var User|null $owner */
            $owner = $calc->user;
            $inputParams = $calc->input_parameters ?? [];
            $targetYear = isset($inputParams['target_retirement_year']) ? (int) $inputParams['target_retirement_year'] : ($calc->created_at->year ?? 2026);

            return [
                'id' => $calc->id,
                'user_id' => $calc->user_id,
                'user_name' => $owner ? $owner->name : 'User',
                'user_email' => $owner ? $owner->email : 'N/A',
                'pension_type' => $calc->pension_type ?? 'old_age',
                'target_retirement_year' => $targetYear,
                'total_service_months' => $calc->total_service_months ?? 0,
                'total_service_years' => round(($calc->total_service_months ?? 0) / 12, 1),
                'kz_wage_coefficient' => (float) ($calc->kz_wage_coefficient ?? 0),
                'zp_macroeconomic_average' => (float) ($calc->zp_macroeconomic_average ?? 0),
                'ks_service_coefficient' => (float) ($calc->ks_service_coefficient ?? 0),
                'base_pension_amount' => (float) ($calc->base_pension ?? 0),
                'final_pension_amount' => (float) ($calc->final_pension ?? $calc->estimated_monthly_pension ?? 0),
                'created_at' => $calc->created_at?->format('Y-m-d H:i:s'),
            ];
        });

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
