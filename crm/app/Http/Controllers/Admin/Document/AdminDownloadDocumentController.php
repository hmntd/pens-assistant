<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminDownloadDocumentController extends Controller
{
    /**
     * Download document file.
     */
    public function __invoke(int $id): BinaryFileResponse|JsonResponse
    {
        /** @var Document $doc */
        $doc = Document::findOrFail($id);
        Gate::authorize('download', $doc);

        $fullPath = storage_path("app/{$doc->file_path}");

        if (! file_exists($fullPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found on server.',
            ], 404);
        }

        return response()->download($fullPath, $doc->original_filename);
    }
}
