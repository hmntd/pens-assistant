<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\RecognizedDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminShowDocumentController extends Controller
{
    /**
     * Display metadata preview for a document.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var Document $doc */
        $doc = Document::with(['user', 'recognizedDocument'])->findOrFail($id);
        Gate::authorize('view', $doc);

        /** @var User|null $owner */
        $owner = $doc->user;
        $fullPath = storage_path("app/{$doc->file_path}");
        $fileSizeBytes = file_exists($fullPath) ? (int) filesize($fullPath) : 0;
        /** @var RecognizedDocument|null $recognized */
        $recognized = $doc->recognizedDocument;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $doc->id,
                'user_id' => $doc->user_id,
                'user' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                ] : null,
                'title' => $doc->original_filename,
                'original_filename' => $doc->original_filename,
                'document_type' => $doc->document_type,
                'file_size' => $fileSizeBytes,
                'formatted_file_size' => $fileSizeBytes > 1024 * 1024
                    ? round($fileSizeBytes / (1024 * 1024), 2).' MB'
                    : round($fileSizeBytes / 1024, 2).' KB',
                'status' => $doc->status,
                'recognized_data' => $recognized ? $recognized->extracted_data : null,
                'created_at' => $doc->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
