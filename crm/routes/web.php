<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('/grpc-test', function () {
    $results = [];

    try {
        $ocrClient = new \Ocr\OcrServiceClient('ocr:50052', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $ocrRequest = new \Ocr\HelloRequest();
        $ocrRequest->setName('Laravel CRM');

        list($ocrResponse, $ocrStatus) = $ocrClient->SayHello($ocrRequest)->wait();

        if ($ocrStatus->code !== \Grpc\STATUS_OK) {
            $results['ocr'] = "Error: " . $ocrStatus->details;
        } else {
            $results['ocr'] = $ocrResponse->getMessage();
        }
    } catch (\Exception $e) {
        $results['ocr'] = "Exception: " . $e->getMessage();
    }

    try {
        $calcClient = new \Calc\CalcServiceClient('calc:50051', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $calcRequest = new \Calc\HelloRequest();
        $calcRequest->setName('Laravel CRM');

        list($calcResponse, $calcStatus) = $calcClient->SayHello($calcRequest)->wait();

        if ($calcStatus->code !== \Grpc\STATUS_OK) {
            $results['calc'] = "Error: " . $calcStatus->details;
        } else {
            $results['calc'] = $calcResponse->getMessage();
        }
    } catch (\Exception $e) {
        $results['calc'] = "Exception: " . $e->getMessage();
    }

    return response()->json([
        'status' => 'success',
        'grpc_responses' => $results
    ]);
});

require __DIR__ . '/settings.php';
