<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use App\Services\PensionCalculationPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DownloadPensionCalculationPdfController extends Controller
{
    public function __construct(
        private readonly PensionCalculationPdfService $pdfService
    ) {}

    public function __invoke(Request $request, CalculatedPension $calculation): Response
    {
        if ($calculation->user_id !== $request->user()?->id) {
            abort(403, 'Unauthorized access to pension calculation PDF.');
        }

        $lang = (string) $request->query('lang', $request->cookie('locale', 'uk'));
        $locale = strtolower($lang) === 'en' ? 'en' : 'uk';

        return $this->pdfService->generatePdfResponse($calculation, $request->user(), $locale);
    }
}
