<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AdminDeleteDocumentController extends Controller
{
    /**
     * Delete document.
     */
    public function __invoke(int $id): JsonResponse
    {
        /** @var Document $doc */
        $doc = Document::findOrFail($id);
        Gate::authorize('delete', $doc);

        if (Storage::exists($doc->file_path)) {
            Storage::delete($doc->file_path);
        }

        $doc->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Document \"{$doc->original_filename}\" deleted successfully.",
        ]);
    }
}
