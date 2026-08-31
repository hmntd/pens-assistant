<?php

namespace App\Http\Controllers\Admin\SystemError;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SystemError\IndexSystemErrorRequest;
use App\Models\SystemErrorLog;
use Illuminate\Http\JsonResponse;

class IndexSystemErrorController extends Controller
{
    public function __invoke(IndexSystemErrorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $status = $validated['status'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = SystemErrorLog::with(['user:id,first_name,last_name,email', 'resolver:id,first_name,last_name,email'])
            ->latest();

        if ($status === 'unresolved') {
            $query->where('is_resolved', false);
        } elseif ($status === 'resolved') {
            $query->where('is_resolved', true);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('status_code', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($perPage);

        $stats = [
            'total' => SystemErrorLog::count(),
            'unresolved' => SystemErrorLog::where('is_resolved', false)->count(),
            'resolved_today' => SystemErrorLog::where('is_resolved', true)->whereDate('resolved_at', now()->today())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $logs,
            'stats' => $stats,
        ]);
    }
}
