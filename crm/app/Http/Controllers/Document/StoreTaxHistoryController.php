<?php

namespace App\Http\Controllers\Document;

use App\Events\TaxHistoryAdded;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TaxHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class StoreTaxHistoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'is_range' => ['nullable', 'boolean'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2099'],
            'from_year' => ['nullable', 'integer', 'min:1950', 'max:2099'],
            'to_year' => ['nullable', 'integer', 'min:1950', 'max:2099'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'months_worked' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $isRange = (bool) ($validated['is_range'] ?? false);
        $monthlySalary = (float) $validated['monthly_salary'];
        $monthsWorked = (int) $validated['months_worked'];

        $createdYears = [];

        if ($isRange) {
            $fromYear = (int) ($validated['from_year'] ?? 2020);
            $toYear = (int) ($validated['to_year'] ?? date('Y'));

            if ($fromYear > $toYear) {
                list($fromYear, $toYear) = [$toYear, $fromYear];
            }

            for ($yr = $fromYear; $yr <= $toYear; $yr++) {
                $annualIncome = $monthlySalary * $monthsWorked;
                $taxPaid = $annualIncome * 0.18;
                $monthlyBreakdown = [];
                for ($m = 1; $m <= 12; $m++) {
                    $monthlyBreakdown[$m] = $m <= $monthsWorked ? $monthlySalary : 0.0;
                }

                TaxHistory::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $yr,
                    ],
                    [
                        'annual_income' => $annualIncome,
                        'tax_paid' => $taxPaid,
                        'months_worked' => $monthsWorked,
                        'monthly_breakdown' => $monthlyBreakdown,
                    ]
                );
                $createdYears[] = $yr;
            }
            $period = "{$fromYear}-{$toYear}";
        } else {
            $yr = (int) ($validated['year'] ?? date('Y'));
            $annualIncome = $monthlySalary * $monthsWorked;
            $taxPaid = $annualIncome * 0.18;
            $monthlyBreakdown = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyBreakdown[$m] = $m <= $monthsWorked ? $monthlySalary : 0.0;
            }

            TaxHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $yr,
                ],
                [
                    'annual_income' => $annualIncome,
                    'tax_paid' => $taxPaid,
                    'months_worked' => $monthsWorked,
                    'monthly_breakdown' => $monthlyBreakdown,
                ]
            );
            $createdYears[] = $yr;
            $period = (string) $yr;
        }

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'tax_history_stored',
            'entity_type' => 'TaxHistory',
            'payload' => [
                'is_range' => $isRange,
                'monthly_salary' => $monthlySalary,
            ],
            'ip_address' => $request->ip(),
        ]);

        // Dispatch domain event for notification handling
        event(new TaxHistoryAdded($user, $period));

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Insurance service record for :period added successfully.', ['period' => $period]),
            ]);
            return redirect()->back();
        }

        $allHistories = TaxHistory::where('user_id', $user->id)
            ->orderBy('year', 'asc')
            ->get();

        return response()->json([
            'message' => 'Історію страхового стажу збережено.',
            'data' => $allHistories,
        ], Response::HTTP_CREATED);
    }
}
