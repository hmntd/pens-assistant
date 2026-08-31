<?php

namespace App\Services;

use App\Models\CalculatedPension;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PensionCalculationPdfService
{
    /**
     * Generate and download PDF report for given pension calculation.
     */
    public function generatePdfResponse(CalculatedPension $calculation, ?User $user = null, string $locale = 'uk'): Response
    {
        $user = $user ?? $calculation->user;

        $logs = [];
        if (!empty($calculation->calculation_logs)) {
            $logs = is_array($calculation->calculation_logs)
                ? $calculation->calculation_logs
                : (json_decode((string) $calculation->calculation_logs, true) ?? []);
        }

        $viewName = $locale === 'en'
            ? 'pdf.pension_calculation_report_en'
            : 'pdf.pension_calculation_report_uk';

        $pdf = Pdf::loadView($viewName, [
            'calculation' => $calculation,
            'user' => $user,
            'calculation_logs' => $logs,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Pension_Calculation_' . $calculation->id . '_' . date('Ymd') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
