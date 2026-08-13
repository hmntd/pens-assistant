<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminIndexDocumentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $statusFilter = $request->input('status');

        $query = Document::with(['user', 'recognizedDocument', 'taxHistories'])
            ->orderByDesc('id');

        if (!empty($statusFilter)) {
            $query->where('status', (string) $statusFilter);
        }

        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
        ], Response::HTTP_OK);
    }
}
