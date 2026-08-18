<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\TaxHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StoreTaxHistoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'is_range' => 'nullable|boolean',
            'year' => 'required_without:is_range|nullable|integer|between:1950,2099',
            'from_year' => 'required_if:is_range,true|nullable|integer|between:1950,2099',
            'to_year' => 'required_if:is_range,true|nullable|integer|between:1950,2099|gte:from_year',
            'monthly_salary' => 'required|numeric|min:0',
            'months_worked' => 'nullable|integer|between:1,12',
        ]);

        $isRange = ! empty($validated['is_range']);
        $monthlySalary = (float) $validated['monthly_salary'];
        $monthsWorked = (int) ($validated['months_worked'] ?? 12);

        if ($isRange) {
            $fromYear = (int) $validated['from_year'];
            $toYear = (int) $validated['to_year'];

            for ($y = $fromYear; $y <= $toYear; $y++) {
                $annualIncome = $monthlySalary * $monthsWorked;
                $taxPaid = $annualIncome * 0.18;

                TaxHistory::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $y,
                    ],
                    [
                        'annual_income' => $annualIncome,
                        'tax_paid' => $taxPaid,
                        'months_worked' => $monthsWorked,
                    ]
                );
            }

            $auditMsg = "Внесено стаж за період {$fromYear}-{$toYear} рр.";
        } else {
            $year = (int) $validated['year'];
            $annualIncome = $monthlySalary * $monthsWorked;
            $taxPaid = $annualIncome * 0.18;

            TaxHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $year,
                ],
                [
                    'annual_income' => $annualIncome,
                    'tax_paid' => $taxPaid,
                    'months_worked' => $monthsWorked,
                ]
            );

            $auditMsg = "Внесено стаж за {$year} рік.";
        }

        // Audit log
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

        // Notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'success',
            'message' => "Успішно оновлено історію страхового стажу: {$auditMsg}",
        ]);

        $allHistories = TaxHistory::where('user_id', $user->id)
            ->orderBy('year', 'asc')
            ->get();

        return response()->json([
            'message' => 'Історію страхового стажу збережено.',
            'data' => $allHistories,
        ], Response::HTTP_CREATED);
    }
}
