<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarkAllNotificationsAsReadController extends Controller
{
    /**
     * Handle the incoming request to mark all notifications as read.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->notifications()->where('is_seen', false)->update(['is_seen' => true]);
        Cache::forget("user:{$user->id}:notifications");

        return response()->json(['success' => true]);
    }
}
