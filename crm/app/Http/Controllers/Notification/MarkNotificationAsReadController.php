<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkNotificationAsReadController extends Controller
{
    /**
     * Handle the incoming request to mark a specific notification as read.
     */
    public function __invoke(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['is_seen' => true]);

        return response()->json(['success' => true]);
    }
}
