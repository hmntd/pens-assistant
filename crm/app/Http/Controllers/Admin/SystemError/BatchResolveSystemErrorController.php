<?php

namespace App\Http\Controllers\Admin\SystemError;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SystemError\BatchResolveSystemErrorRequest;
use App\Models\SystemErrorLog;
use Illuminate\Http\JsonResponse;

class BatchResolveSystemErrorController extends Controller
{
    public function __invoke(BatchResolveSystemErrorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $isResolved = (bool) $validated['is_resolved'];

        SystemErrorLog::whereIn('id', $validated['ids'])->update([
            'is_resolved' => $isResolved,
            'resolved_at' => $isResolved ? now() : null,
            'resolved_by_id' => $isResolved ? $user?->id : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => count($validated['ids']).' error log(s) updated successfully.',
        ]);
    }
}
