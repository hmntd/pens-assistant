<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePensionCalculationRequest;
use App\Models\CalculatedPension;
use App\Models\User;
use App\Services\PensionCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class StorePensionCalculationController extends Controller
{
    public function __invoke(StorePensionCalculationRequest $request, PensionCalculatorService $service): JsonResponse
    {
        $user = $request->user();
        $targetUser = $user;

        if ($user->isAdmin() && $request->filled('target_user_id')) {
            $targetUser = User::findOrFail($request->input('target_user_id'));
        }

        Gate::authorize('create', [CalculatedPension::class, $targetUser]);

        $calculatedPension = $service->calculateAndSave($targetUser, $request->validated());

        return response()->json([
            'message' => 'Pension calculated and saved successfully.',
            'data' => $calculatedPension,
        ], Response::HTTP_CREATED);
    }
}
