<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\NotificationChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendTestNotificationController extends Controller
{
    /**
     * Send a test notification to a specific channel.
     */
    public function __invoke(Request $request, NotificationChannelService $service): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:email,telegram'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $service->sendTestNotification(
            $user, 
            (string) $validated['channel'],
            $validated['telegram_chat_id'] ?? null
        );

        return response()->json($result);
    }
}
