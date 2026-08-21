<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IndexDocumentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 100)));

        $documents = Document::where('user_id', $user->id)
            ->with(['recognizedDocument', 'taxHistories'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $taxHistories = $user->taxHistories()
            ->orderBy('year', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $documents->items(),
            'tax_histories' => $taxHistories,
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
        ], Response::HTTP_OK);
    }
}
