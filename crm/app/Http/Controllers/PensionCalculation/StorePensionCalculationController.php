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
use Illuminate\Validation\ValidationException;

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

        $errors = [];

        // Validate target retirement year / date of birth / retirement date
        $targetRetirementYear = $request->input('target_retirement_year')
            ?? $request->input('retirement_date')
            ?? $targetUser->target_retirement_year
            ?? $targetUser->date_of_birth;

        if (empty($targetRetirementYear)) {
            $errors['target_retirement_year'] = ['Плановий рік виходу на пенсію не вказано в профілі.'];
        }

        // Validate insurance service (salary history, employment history, tax history, or full profile dates)
        $hasSalaryOrService = ! empty($request->input('salary_history'))
            || ! empty($request->input('employment_history'))
            || $targetUser->taxHistories()->count() > 0;

        if (! $hasSalaryOrService) {
            $errors['insurance_service'] = ['Історія страхового стажу порожня. Завантажте документи або введіть стаж вручну.'];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $calculatedPension = $service->calculateAndSave($targetUser, $request->validated());

        return response()->json([
            'message' => 'Pension calculated and saved successfully.',
            'data' => $calculatedPension,
        ], Response::HTTP_CREATED);
    }
}
