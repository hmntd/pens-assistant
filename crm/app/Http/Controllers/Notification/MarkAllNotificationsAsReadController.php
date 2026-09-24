<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkAllNotificationsAsReadController extends Controller
{
    /**
     * Handle the incoming request to mark all notifications as read.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->notifications()->where('is_seen', false)->update(['is_seen' => true]);

        return response()->json(['success' => true]);
    }
}
