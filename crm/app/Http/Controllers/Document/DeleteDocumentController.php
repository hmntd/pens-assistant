<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DeleteDocumentController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $document = Document::where('user_id', $user->id)->find($id);

        if (!$document) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['message' => 'Document not found']);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Document deleted successfully.'),
            ]);
            return redirect()->back();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully',
        ], Response::HTTP_OK);
    }
}
