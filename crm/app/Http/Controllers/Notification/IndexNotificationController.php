<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IndexNotificationController extends Controller
{
    /**
     * Handle the incoming request to list notifications for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "user:{$user->id}:notifications";

        $payload = Cache::remember($cacheKey, 600, function () use ($user) {
            $notifications = $user->notifications()
                ->with('translation')
                ->latest()
                ->take(20)
                ->get();

            return [
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
                })->values()->all(),
                'unread_count' => $user->notifications()->where('is_seen', false)->count(),
            ];
        });

        return response()->json($payload);
    }
}
