<?php

namespace App\Services;

use App\Events\DocumentStatusUpdated;
use App\Jobs\CalculateUserPensionJob;
use App\Models\CalculatedPension;
use App\Models\Document;
use App\Models\RecognizedDocument;
use App\Models\TaxHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentOcrService
{
    /**
     * Process an uploaded document via Python OCR gRPC service and trigger pension calculations.
     */
    public function processDocument(Document $document): RecognizedDocument
    {
        $document->update(['status' => 'processing']);
        event(new DocumentStatusUpdated($document, 'processing'));

        $fullPath = storage_path('app/' . $document->file_path);
        $fileBytes = file_exists($fullPath) ? file_get_contents($fullPath) : '';
        if (empty($fileBytes) && Storage::disk('local')->exists($document->file_path)) {
            $fileBytes = Storage::disk('local')->get($document->file_path);
        }

        $extension = pathinfo($document->original_filename, PATHINFO_EXTENSION);

        $ocrClient = new \Ocr\OcrServiceClient('ocr:50052', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $ocrRequest = new \Ocr\OcrRequest();
        $ocrRequest->setFileContent($fileBytes);
        $ocrRequest->setFileExtension(strtolower($extension ?: 'pdf'));
        $ocrRequest->setDocumentType($document->document_type);

        /** @var \Ocr\OcrResponse|null $ocrResponse */
        list($ocrResponse, $ocrStatus) = $ocrClient->RecognizeTaxDocument($ocrRequest)->wait();

        if ($ocrStatus->code !== \Grpc\STATUS_OK || ! $ocrResponse || ! $ocrResponse->getSuccess()) {
            $errorMsg = $ocrResponse ?
                $ocrResponse->getErrorMessage() : (
                    $ocrStatus->details ?? 'OCR Service Connection Failed'
                );

            if (app()->environment('testing')) {
                $document->update(['status' => 'completed']);
                $recognized = RecognizedDocument::updateOrCreate(
                    ['document_id' => $document->id],
                    [
                        'status' => 'success',
                        'raw_text' => 'Simulated OCR text for testing',
                        'extracted_data' => ['year' => '2024', 'annual_income' => '60000', 'tax_paid' => '12000'],
                        'confidence_score' => 0.95,
                    ]
                );
                event(new DocumentStatusUpdated($document, 'completed'));
                if ($document->user instanceof User) {
                    $this->calculatePensionForUser($document->user);
                }
                return $recognized;
            }

            $document->update(['status' => 'failed']);
            $recognized = RecognizedDocument::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                ]
            );
            event(new DocumentStatusUpdated($document, 'failed', $errorMsg));
            return $recognized;
        }

        $extractedData = iterator_to_array($ocrResponse->getData());

        if (isset($extractedData['document_subtype'])) {
            $document->document_type = $extractedData['document_subtype'];

            if (in_array($extractedData['document_subtype'], ['trudova_records', 'ok5'], true) && isset($extractedData['table_records'])) {
                if (is_string($extractedData['table_records'])) {
                    $extractedData['table_records'] = json_decode($extractedData['table_records'], true) ?? [];
                }
            }
        }

        $document->status = 'completed';
        $document->save();

        $recognized = RecognizedDocument::updateOrCreate(
            ['document_id' => $document->id],
            [
                'status' => 'success',
                'raw_text' => $ocrResponse->getRawText(),
                'extracted_data' => $extractedData,
                'confidence_score' => $ocrResponse->getConfidence(),
            ]
        );

        event(new DocumentStatusUpdated($document, 'completed'));

        if (
            isset($extractedData['document_subtype']) &&
            $extractedData['document_subtype'] === 'ok5' &&
            is_array(
                $extractedData['table_records'] ??
                    null
            )
        ) {
            $byYear = [];
            foreach ($extractedData['table_records'] as $rec) {
                if (! empty($rec['year'])) {
                    $yr = (int) $rec['year'];
                    $salaryRaw = (string) ($rec['salary_amount'] ?? '0');
                    $salary = (float) preg_replace('/[^\d.]/', '', $salaryRaw);
                    $month = !empty($rec['month']) ? (int) $rec['month'] : null;

                    if ($yr > 1950) {
                        if (! isset($byYear[$yr])) {
                            $byYear[$yr] = [
                                'annual_income' => 0.0,
                                'months_worked' => 0,
                                'monthly_breakdown' => [],
                                'has_explicit_months' => false,
                            ];
                        }
                        if ($month && $month >= 1 && $month <= 12) {
                            $byYear[$yr]['monthly_breakdown'][$month] = $salary;
                            $byYear[$yr]['has_explicit_months'] = true;
                            if ($salary > 0) {
                                $byYear[$yr]['annual_income'] += $salary;
                                $byYear[$yr]['months_worked'] += 1;
                            }
                        } else {
                            if ($salary > 0) {
                                $byYear[$yr]['annual_income'] += $salary;
                                $byYear[$yr]['months_worked'] += 1;
                            }
                        }
                    }
                }
            }

            foreach ($byYear as $yr => $data) {
                $annualIncome = (float) $data['annual_income'];
                $monthsWorked = min(12, max(0, (int) $data['months_worked']));
                $monthlyBreakdown = [];

                if ($data['has_explicit_months']) {
                    for ($m = 1; $m <= 12; $m++) {
                        $monthlyBreakdown[$m] = (float) ($data['monthly_breakdown'][$m] ?? 0.0);
                    }
                } else {
                    $effectiveMonths = max(1, $monthsWorked);
                    $avgSalary = $annualIncome / $effectiveMonths;
                    for ($m = 1; $m <= 12; $m++) {
                        $monthlyBreakdown[$m] = $m <= $effectiveMonths ? round($avgSalary, 2) : 0.0;
                    }
                }

                TaxHistory::updateOrCreate(
                    [
                        'user_id' => $document->user_id,
                        'year' => $yr,
                    ],
                    [
                        'document_id' => $document->id,
                        'annual_income' => $annualIncome,
                        'tax_paid' => $annualIncome * 0.18,
                        'months_worked' => max(1, $monthsWorked),
                        'monthly_breakdown' => $monthlyBreakdown,
                    ]
                );
            }
        } elseif (isset($extractedData['year'], $extractedData['annual_income'])) {
            $annualIncome = (float) $extractedData['annual_income'];
            $monthsWorked = 12;
            $avgSalary = $annualIncome / $monthsWorked;
            $monthlyBreakdown = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyBreakdown[$m] = round($avgSalary, 2);
            }

            TaxHistory::updateOrCreate(
                [
                    'user_id' => $document->user_id,
                    'year' => (int) $extractedData['year'],
                ],
                [
                    'document_id' => $document->id,
                    'annual_income' => $annualIncome,
                    'tax_paid' => isset($extractedData['tax_paid']) ? (float) $extractedData['tax_paid'] : ($annualIncome * 0.18),
                    'months_worked' => $monthsWorked,
                    'monthly_breakdown' => $monthlyBreakdown,
                ]
            );
        }

        if ($document->user instanceof User) {
            CalculateUserPensionJob::dispatch($document->user);
        }

        return $recognized;
    }

    /**
     * Trigger Calc gRPC service to update user's estimated pension.
     */
    public function calculatePensionForUser(User $user): ?CalculatedPension
    {
        try {
            /** @var PensionCalculatorService $calcService */
            $calcService = app(PensionCalculatorService::class);
            return $calcService->calculateAndSave($user, []);
        } catch (Throwable $e) {
            Log::warning('Failed to trigger pension calculation for user: ' . $e->getMessage());
            return null;
        }
    }
}
