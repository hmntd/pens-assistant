<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\NotificationChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationChannelController extends Controller
{
    /**
     * Display the user's notification channels settings.
     */
    public function edit(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('settings/Notifications', [
            'channels' => $user->getNotificationChannel(),
            'userEmail' => $user->email,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's notification channels settings.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'email_enabled' => ['required', 'boolean'],
            'telegram_enabled' => ['required', 'boolean'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            // 'sms_enabled' => ['required', 'boolean'],
            // 'phone_number' => ['nullable', 'string', 'max:50'],
            'notify_calc_completed' => ['required', 'boolean'],
            'notify_document_processed' => ['required', 'boolean'],
            'notify_system_alerts' => ['required', 'boolean'],
            'notify_pension_updates' => ['required', 'boolean'],
        ]);

        $channel = $user->getNotificationChannel();
        $channel->update($validated);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'notification_channels_updated',
            'entity_type' => 'UserNotificationChannel',
            'entity_id' => $channel->id,
            'payload' => [
                'updated_fields' => array_keys($validated),
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('notifications.edit')->with('status', 'Notification settings updated successfully.');
    }

    /**
     * Send a test notification to a specific channel.
     */
    public function sendTest(Request $request, NotificationChannelService $service): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:email,telegram'], // SMS disabled for now: in:email,telegram,sms
        ]);

        $result = $service->sendTestNotification($user, (string) $validated['channel']);

        return response()->json($result);
    }
}
