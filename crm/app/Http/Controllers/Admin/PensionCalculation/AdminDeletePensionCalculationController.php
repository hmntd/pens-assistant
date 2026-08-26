<?php

namespace App\Http\Controllers\Admin\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminDeletePensionCalculationController extends Controller
{
    /**
     * Delete a calculated pension record.
     */
    public function __invoke(int $id): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $calc = CalculatedPension::findOrFail($id);
        $calc->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Calculation record #{$id} deleted.",
        ]);
    }
}
