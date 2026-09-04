<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ShowPensionCalculationController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $calculatedPension = CalculatedPension::with('user:id,first_name,last_name,email')->findOrFail($id);

        Gate::authorize('view', $calculatedPension);

        return response()->json([
            'data' => $calculatedPension,
        ], Response::HTTP_OK);
    }
}
