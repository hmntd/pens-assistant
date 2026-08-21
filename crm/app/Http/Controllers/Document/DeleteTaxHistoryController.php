<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TaxHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DeleteTaxHistoryController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $taxHistory = TaxHistory::where('user_id', $user->id)
            ->findOrFail($id);

        $year = $taxHistory->year;
        $taxHistory->delete();

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'tax_history_deleted',
            'entity_type' => 'TaxHistory',
            'entity_id' => $id,
            'payload' => ['year' => $year],
            'ip_address' => $request->ip(),
        ]);

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Service history record deleted.'),
            ]);
            return redirect()->back();
        }

        return response()->json([
            'message' => 'Запис страхового стажу видалено.',
        ], Response::HTTP_OK);
    }
}
