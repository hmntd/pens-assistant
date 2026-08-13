<?php

use App\Http\Controllers\PensionCoefficient\DeletePensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\IndexPensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\StorePensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\UpdatePensionCoefficientController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('pension-coefficients')->group(function () {
    Route::get('/', IndexPensionCoefficientController::class)->name('pension-coefficients.index');
    Route::post('/', StorePensionCoefficientController::class)->name('pension-coefficients.store');
    Route::put('/{id}', UpdatePensionCoefficientController::class)->name('pension-coefficients.update');
    Route::delete('/{id}', DeletePensionCoefficientController::class)->name('pension-coefficients.destroy');
});

Route::get('/grpc-test', function () {
    $ocrClient = new \Ocr\OcrServiceClient('ocr:50052', [
        'credentials' => \Grpc\ChannelCredentials::createInsecure(),
    ]);

    $ocrRequest = new \Ocr\OcrRequest();
    $ocrRequest->setFileContent('...simulated raw PDF byte stream...');
    $ocrRequest->setFileExtension('pdf');
    $ocrRequest->setDocumentType('income_certificate');

    /** @var \Ocr\OcrResponse $ocrResponse */
    list($ocrResponse, $ocrStatus) = $ocrClient->RecognizeTaxDocument($ocrRequest)->wait();

    $ocrResult = [];
    if ($ocrStatus->code === \Grpc\STATUS_OK && $ocrResponse && $ocrResponse->getSuccess()) {
        $ocrResult = [
            'status' => 'success',
            'raw_text' => $ocrResponse->getRawText(),
            'confidence' => $ocrResponse->getConfidence(),
            'extracted_data' => iterator_to_array($ocrResponse->getData()),
        ];
    } else {
        $ocrResult = ['status' => 'error', 'message' => $ocrResponse ? $ocrResponse->getErrorMessage() : ($ocrStatus->details ?? 'Connection failed')];
    }

    $calcClient = new \Calc\CalcServiceClient('calc:50051', [
        'credentials' => \Grpc\ChannelCredentials::createInsecure(),
    ]);

    $calcRequest = new \Calc\PensionRequest();
    $calcRequest->setCustomerId('user-999');
    $calcRequest->setBirthYear(1990);
    $calcRequest->setTargetRetirementYear(2055);

    // Create mock tax records for the 'repeated' history field
    $record2024 = new \Calc\TaxRecord();
    $record2024->setYear(2024);
    $record2024->setAnnualIncome(50000.0);
    $record2024->setTaxPaid(10000.0);
    $record2024->setMonthsWorked(12);

    $record2025 = new \Calc\TaxRecord();
    $record2025->setYear(2025);
    $record2025->setAnnualIncome(55000.0);
    $record2025->setTaxPaid(11000.0);
    $record2025->setMonthsWorked(12);

    // Pass the array of TaxRecord objects into the request
    $calcRequest->setHistory([$record2024, $record2025]);

    /** @var \Calc\PensionResponse $calcResponse */
    list($calcResponse, $calcStatus) = $calcClient->CalculatePension($calcRequest)->wait();

    $calcResult = [];
    if ($calcStatus->code === \Grpc\STATUS_OK && $calcResponse && $calcResponse->getSuccess()) {
        $calcResult = [
            'status' => 'success',
            'estimated_monthly_pension' => $calcResponse->getEstimatedMonthlyPension(),
            'total_accumulated_capital' => $calcResponse->getTotalAccumulatedCapital(),
            'breakdown' => $calcResponse->getCalculationBreakdown(),
        ];
    } else {
        $calcResult = ['status' => 'error', 'message' => $calcResponse ? $calcResponse->getErrorMessage() : ($calcStatus->details ?? 'Connection failed')];
    }

    return response()->json([
        'ocr_service' => $ocrResult,
        'calc_service' => $calcResult,
    ]);
});

require __DIR__ . '/settings.php';
