<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Models\Document;
use App\Services\DocumentOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadDocumentController extends Controller
{
    public function __invoke(UploadDocumentRequest $request, DocumentOcrService $ocrService): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        /** @var \App\Models\User $user */
        $user = $request->user();

        $originalFilename = $file->getClientOriginalName();
        $documentType = $request->input('document_type', 'income_certificate');

        $path = $file->store('documents', 'local');

        $document = Document::create([
            'user_id' => $user->id,
            'file_path' => (string) $path,
            'original_filename' => $originalFilename,
            'document_type' => (string) $documentType,
            'status' => 'pending',
        ]);

        $recognized = $ocrService->processDocument($document);

        return response()->json([
            'status' => 'success',
            'data' => [
                'document' => [
                    'id' => $document->id,
                    'original_filename' => $document->original_filename,
                    'status' => $document->status,
                ],
                'recognized' => [
                    'status' => $recognized->status,
                    'raw_text' => $recognized->raw_text,
                    'extracted_data' => $recognized->extracted_data,
                    'confidence_score' => $recognized->confidence_score,
                ],
                'calculated_pension' => $user->latestCalculatedPension,
            ],
        ], Response::HTTP_CREATED);
    }
}
