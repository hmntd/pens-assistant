<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowDocumentController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $document = Document::where('user_id', $user->id)
            ->with(['recognizedDocument', 'taxHistories'])
            ->find($id);

        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'data' => $document,
        ], Response::HTTP_OK);
    }
}
