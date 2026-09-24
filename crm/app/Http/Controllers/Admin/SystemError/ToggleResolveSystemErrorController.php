<?php

namespace App\Http\Controllers\Admin\SystemError;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToggleResolveSystemErrorController extends Controller
{
    public function __invoke(Request $request, SystemErrorLog $errorLog): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->is_admin && ! $user->hasRole('admin'))) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $newResolved = ! $errorLog->is_resolved;

        $errorLog->update([
            'is_resolved' => $newResolved,
            'resolved_at' => $newResolved ? now() : null,
            'resolved_by_id' => $newResolved ? $user->id : null,
        ]);

        $errorLog->load(['user:id,first_name,last_name,email', 'resolver:id,first_name,last_name,email']);

        return response()->json([
            'success' => true,
            'data' => $errorLog,
            'message' => $newResolved ? 'Error log marked as resolved.' : 'Error log marked as unresolved.',
        ]);
    }
}
