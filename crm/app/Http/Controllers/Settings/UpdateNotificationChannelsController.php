<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateNotificationChannelsController extends Controller
{
    /**
     * Update the user's notification channels settings.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'email_enabled' => ['required', 'boolean'],
            'telegram_enabled' => ['required', 'boolean'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
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
}
