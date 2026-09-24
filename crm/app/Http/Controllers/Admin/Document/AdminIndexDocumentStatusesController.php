<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminIndexDocumentStatusesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $statusCounts = Document::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalDocuments = Document::count();
        $totalUsersWithDocuments = Document::distinct('user_id')->count('user_id');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_documents' => $totalDocuments,
                'total_users' => $totalUsersWithDocuments,
                'statuses' => [
                    'pending' => $statusCounts['pending'] ?? 0,
                    'processing' => $statusCounts['processing'] ?? 0,
                    'completed' => $statusCounts['completed'] ?? 0,
                    'failed' => $statusCounts['failed'] ?? 0,
                ],
            ],
        ], Response::HTTP_OK);
    }
}
