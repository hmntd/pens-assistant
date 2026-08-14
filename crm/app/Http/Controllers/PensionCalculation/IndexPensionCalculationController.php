<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class IndexPensionCalculationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::authorize('viewAny', CalculatedPension::class);

        $query = CalculatedPension::with('user:id,name,email');

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $records = $query->latest()->get();

        return response()->json([
            'data' => $records,
        ], Response::HTTP_OK);
    }
}
