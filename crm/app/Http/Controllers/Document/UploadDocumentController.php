<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Notification;
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

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'document_uploaded',
            'entity_type' => 'Document',
            'entity_id' => $document->id,
            'payload' => ['original_filename' => $originalFilename],
            'ip_address' => $request->ip(),
        ]);

        // Notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'success',
            'message' => "Файл '{$originalFilename}' успішно завантажено та передано на OCR розпізнавання.",
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
