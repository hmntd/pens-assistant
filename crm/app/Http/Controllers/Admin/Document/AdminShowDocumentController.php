<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminShowDocumentController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $document = Document::with(['user', 'recognizedDocument', 'taxHistories'])
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
