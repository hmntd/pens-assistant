<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDocumentController extends Controller
{
    /**
     * Stream the document file inline for authenticated user or admin review.
     */
    public function __invoke(Request $request, int $id): StreamedResponse|Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = Document::query();
        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $document = $query->find($id);

        if (! $document || ! Storage::disk('local')->exists($document->file_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document file not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $mimeType = Storage::disk('local')->mimeType($document->file_path) ?: 'application/octet-stream';

        return Storage::disk('local')->response($document->file_path, $document->original_filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"',
        ]);
    }
}
