<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexNotificationController extends Controller
{
    /**
     * Handle the incoming request to list notifications for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->with('translation')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function (Model $item) {
                /** @var Notification $n */
                $n = $item;
                $trans = $n->translation;

                return [
                    'id' => $n->id,
                    'user_id' => $n->user_id,
                    'type' => $n->type,
                    'is_seen' => $n->is_seen,
                    'created_at' => $n->created_at?->toIso8601String(),
                    'translations' => [
                        'uk' => $trans !== null ? $trans->uk : '',
                        'en' => $trans !== null ? $trans->en : '',
                    ],
                ];
            }),
            'unread_count' => $request->user()->notifications()->where('is_seen', false)->count(),
        ]);
    }
}
