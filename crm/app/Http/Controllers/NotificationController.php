<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->notifications()->where('is_seen', false)->count(),
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->where('is_seen', false)->update(['is_seen' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['is_seen' => true]);

        return response()->json(['success' => true]);
    }
}
