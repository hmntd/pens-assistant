<?php

namespace App\Http\Controllers\Admin\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use App\Models\User;
use App\Services\PensionCalculationPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminDownloadPensionCalculationPdfController extends Controller
{
    public function __construct(
        private readonly PensionCalculationPdfService $pdfService
    ) {}

    public function __invoke(Request $request, CalculatedPension $calculation): Response
    {
        $lang = (string) $request->query('lang', $request->cookie('locale', 'uk'));
        $locale = strtolower($lang) === 'en' ? 'en' : 'uk';

        /** @var User|null $user */
        $user = $calculation->user;

        return $this->pdfService->generatePdfResponse($calculation, $user, $locale);
    }
}
