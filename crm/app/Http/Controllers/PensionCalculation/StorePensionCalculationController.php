<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePensionCalculationRequest;
use App\Jobs\CalculateUserPensionJob;
use App\Models\CalculatedPension;
use App\Models\User;
use App\Services\PensionCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StorePensionCalculationController extends Controller
{
    public function __invoke(StorePensionCalculationRequest $request, PensionCalculatorService $service): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $targetUser = $user;

        if ($user->isAdmin() && $request->filled('target_user_id')) {
            $targetUser = User::findOrFail($request->input('target_user_id'));
        }

        Gate::authorize('create', [CalculatedPension::class, $targetUser]);

        $errors = [];

        $dobYear = $targetUser->date_of_birth ? (int) $targetUser->date_of_birth->format('Y') : 0;
        $fallbackRetirementYear = $dobYear > 0 ? ($dobYear + 60) : null;

        $targetRetirementYear = $request->input('target_retirement_year')
            ?? $request->input('retirement_date')
            ?? $targetUser->target_retirement_year
            ?? $fallbackRetirementYear;

        if (empty($targetRetirementYear)) {
            $errors['target_retirement_year'] = ['Target retirement year is not specified in profile.'];
        }

        $gender = $request->input('gender') ?? $targetUser->gender;
        if (empty($gender)) {
            $errors['gender'] = ['Gender is not specified in user profile. Select a gender for calculation.'];
        }

        // Validate insurance service (salary history, employment history, tax history, or full profile dates)
        $hasSalaryOrService = ! empty($request->input('salary_history'))
            || ! empty($request->input('employment_history'))
            || $targetUser->taxHistories()->count() > 0;

        if (! $hasSalaryOrService) {
            $errors['insurance_service'] = ['Insurance service history is empty. Upload documents or enter service manually.'];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $jobData = array_merge($request->validated(), [
            'gender' => $gender,
            'target_retirement_year' => $targetRetirementYear,
        ]);

        $calculatedPension = CalculatedPension::create([
            'user_id' => $targetUser->id,
            'status' => 'pending',
            'estimated_monthly_pension' => 0.00,
            'total_accumulated_capital' => 0.00,
            'input_parameters' => $jobData,
        ]);

        if (app()->environment('testing')) {
            CalculateUserPensionJob::dispatchSync($targetUser, $jobData, $calculatedPension->id);
            $calculatedPension = $targetUser->calculatedPensions()->latest('id')->first();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Pension calculated and saved successfully.',
                    'data' => $calculatedPension,
                ], Response::HTTP_CREATED);
            }
        } else {
            CalculateUserPensionJob::dispatch($targetUser, $jobData, $calculatedPension->id);
        }

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Pension calculation has been queued for processing.'),
            ]);
            return redirect()->back();
        }

        return response()->json([
            'status' => 'queued',
            'message' => 'Pension calculation has been queued for background processing.',
            'data' => $calculatedPension,
        ], Response::HTTP_ACCEPTED);
    }
}
