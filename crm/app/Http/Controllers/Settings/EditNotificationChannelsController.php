<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditNotificationChannelsController extends Controller
{
    /**
     * Display the user's notification channels settings page.
     */
    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return Inertia::render('settings/Notifications', [
            'channels' => $user->getNotificationChannel(),
            'userEmail' => $user->email,
            'telegramBotUsername' => config('notification_channels.telegram.bot_username', 'PensAssistantBot'),
            'status' => $request->session()->get('status'),
        ]);
    }
}
