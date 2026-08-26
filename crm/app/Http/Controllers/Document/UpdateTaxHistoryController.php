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

class UpdateTaxHistoryController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $taxHistory = TaxHistory::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'monthly_breakdown' => ['required', 'array'],
            'monthly_breakdown.*' => ['required', 'numeric', 'min:0'],
        ]);

        $rawBreakdown = $validated['monthly_breakdown'];
        $cleanBreakdown = [];

        // Ensure keys 1 to 12
        for ($m = 1; $m <= 12; $m++) {
            $val = $rawBreakdown[$m] ?? $rawBreakdown[(string) $m] ?? $rawBreakdown[$m - 1] ?? 0;
            $cleanBreakdown[$m] = (float) $val;
        }

        $annualIncome = array_sum($cleanBreakdown);
        $taxPaid = $annualIncome * 0.18;
        $monthsWorked = count(array_filter($cleanBreakdown, fn ($val) => $val > 0)) ?: 12;

        $taxHistory->update([
            'monthly_breakdown' => $cleanBreakdown,
            'annual_income' => $annualIncome,
            'tax_paid' => $taxPaid,
            'months_worked' => $monthsWorked,
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'tax_history_updated',
            'entity_type' => 'TaxHistory',
            'entity_id' => $taxHistory->id,
            'payload' => [
                'year' => $taxHistory->year,
                'annual_income' => $annualIncome,
            ],
            'ip_address' => $request->ip(),
        ]);

        // Dispatch domain event for notification handling
        event(new TaxHistoryAdded($user, (string) $taxHistory->year));

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __("Salary breakdown for year :year updated successfully.", ['year' => $taxHistory->year]),
            ]);
            return redirect()->back();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Monthly salary history for year saved successfully.',
            'data' => $taxHistory,
        ], Response::HTTP_OK);
    }
}
