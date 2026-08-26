<?php

namespace App\Http\Controllers\Admin\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminShowPensionCalculationController extends Controller
{
    /**
     * Display detailed breakdown and C++ audit logs for a calculated pension.
     */
    public function __invoke(int $id): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        /** @var CalculatedPension $calc */
        $calc = CalculatedPension::with('user')->findOrFail($id);
        /** @var User|null $owner */
        $owner = $calc->user;
        $inputParams = $calc->input_parameters ?? [];
        $targetYear = isset($inputParams['target_retirement_year']) ? (int) $inputParams['target_retirement_year'] : ($calc->created_at->year ?? now()->year);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $calc->id,
                'user_id' => $calc->user_id,
                'user_name' => $owner ? $owner->name : 'User',
                'user_email' => $owner ? $owner->email : 'N/A',
                'pension_type' => $calc->pension_type ?? 'age',
                'target_retirement_year' => $targetYear,
                'total_service_months' => $calc->total_service_months ?? 0,
                'total_service_years' => round(($calc->total_service_months ?? 0) / 12, 1),
                'kz_wage_coefficient' => (float) ($calc->kz_wage_coefficient ?? 0),
                'zp_macroeconomic_average' => (float) ($calc->zp_macroeconomic_average ?? 0),
                'ks_service_coefficient' => (float) ($calc->ks_service_coefficient ?? 0),
                'base_pension_amount' => (float) ($calc->base_pension ?? 0),
                'final_pension_amount' => (float) ($calc->final_pension ?? $calc->estimated_monthly_pension ?? 0),
                'calculation_breakdown' => $calc->calculation_breakdown ?? [],
                'calculation_logs' => $calc->calculation_logs ?? [],
                'created_at' => $calc->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
