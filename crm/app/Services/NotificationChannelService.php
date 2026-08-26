<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTranslation;
use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationChannelService
{
    /**
     * Dispatch a notification across all enabled channels for the given user.
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string $type ('info', 'success', 'warning', 'error')
     * @param string|null $eventCategory ('calc_completed', 'document_processed', 'system_alerts', 'pension_updates')
     * @return array<string, bool> Results per channel
     */
    public function dispatchNotification(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $eventCategory = null
    ): array {
        $channels = $user->getNotificationChannel();

        // Check if this event category is allowed by user preferences
        if ($eventCategory && ! $this->shouldSendForCategory($channels, $eventCategory)) {
            return ['in_app' => true, 'suppressed' => true];
        }

        $results = [];

        // 1. In-App Notification (Always stored)
        $translation = NotificationTranslation::create([
            'title_uk' => $title,
            'title_en' => $title,
            'message_uk' => $message,
            'message_en' => $message,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'notification_translation_id' => $translation->id,
            'type' => $type,
            'is_seen' => false,
        ]);
        $results['in_app'] = true;

        // 2. Email Channel
        if ($channels->email_enabled && ! empty($user->email)) {
            $results['email'] = $this->sendEmail($user->email, $title, $message);
        }

        // 3. Telegram Channel
        if ($channels->telegram_enabled && ! empty($channels->telegram_chat_id)) {
            $results['telegram'] = $this->sendTelegram($channels->telegram_chat_id, $title, $message);
        }

        /* SMS channel disabled for now
        // 4. SMS Channel
        if ($channels->sms_enabled && ! empty($channels->phone_number)) {
            $results['sms'] = $this->sendSms($channels->phone_number, $title, $message);
        }
        */

        return $results;
    }

    /**
     * Send a test notification to a specific channel.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestNotification(User $user, string $channel): array
    {
        $settings = $user->getNotificationChannel();
        $testTitle = '🔔 Test Notification';
        $testMessage = 'This is a test notification from PensAssistant to verify channel connectivity.';

        switch ($channel) {
            case 'email':
                if (empty($user->email)) {
                    return ['success' => false, 'message' => 'User email address is missing.'];
                }
                $sent = $this->sendEmail($user->email, $testTitle, $testMessage);
                return [
                    'success' => $sent,
                    'message' => $sent ? "Test email sent to {$user->email}." : 'Failed to send test email.',
                ];

            case 'telegram':
                if (empty($settings->telegram_chat_id)) {
                    return ['success' => false, 'message' => 'Telegram Chat ID is not configured.'];
                }
                $sent = $this->sendTelegram($settings->telegram_chat_id, $testTitle, $testMessage);
                return [
                    'success' => $sent,
                    'message' => $sent ? "Test message sent to Telegram Chat ID {$settings->telegram_chat_id}." : 'Failed to send Telegram message.',
                ];

            /* SMS channel commented out for future implementation
            case 'sms':
                if (empty($settings->phone_number)) {
                    return ['success' => false, 'message' => 'Phone number is not configured.'];
                }
                $sent = $this->sendSms($settings->phone_number, $testTitle, $testMessage);
                return [
                    'success' => $sent,
                    'message' => $sent ? "Test SMS dispatched to {$settings->phone_number}." : 'Failed to send test SMS.',
                ];
            */

            default:
                return ['success' => false, 'message' => "Unsupported notification channel: {$channel}"];
        }
    }

    protected function shouldSendForCategory(UserNotificationChannel $channels, string $category): bool
    {
        return match ($category) {
            'calc_completed' => $channels->notify_calc_completed,
            'document_processed' => $channels->notify_document_processed,
            'system_alerts' => $channels->notify_system_alerts,
            'pension_updates' => $channels->notify_pension_updates,
            default => true,
        };
    }

    protected function sendEmail(string $toEmail, string $title, string $message): bool
    {
        try {
            Mail::raw("{$title}\n\n{$message}", function ($mail) use ($toEmail, $title) {
                $mail->to($toEmail)->subject($title);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed sending notification email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    protected function sendTelegram(string $chatId, string $title, string $message): bool
    {
        try {
            /** @var string $token */
            $token = config('notification_channels.telegram.bot_token', '');
            /** @var string $baseUrl */
            $baseUrl = config('notification_channels.telegram.api_url', 'https://api.telegram.org');

            if (empty($token)) {
                Log::info("Telegram notification simulated for chat {$chatId} (TELEGRAM_BOT_TOKEN not set): {$title} - {$message}");
                return true;
            }

            $response = Http::post("{$baseUrl}/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "*{$title}*\n\n{$message}",
                'parse_mode' => 'Markdown',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed sending Telegram message to {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    /* SMS channel commented out for future implementation
    protected function sendSms(string $phoneNumber, string $title, string $message): bool
    {
        try {
            /** @var string $driver * /
            $driver = config('notification_channels.sms.driver', 'log');
            /** @var string $apiKey * /
            $apiKey = config('notification_channels.sms.api_key', '');
            /** @var string $apiUrl * /
            $apiUrl = config('notification_channels.sms.api_url', 'https://api.turbosms.ua/message/send.json');
            /** @var string $senderId * /
            $senderId = config('notification_channels.sms.sender_id', 'PensAssist');

            if ($driver === 'turbosms' && ! empty($apiKey)) {
                $response = Http::withToken($apiKey)->post($apiUrl, [
                    'recipients' => [$phoneNumber],
                    'sms' => [
                        'sender' => $senderId,
                        'text' => "{$title}: {$message}",
                    ],
                ]);
                return $response->successful();
            }

            Log::info("SMS notification dispatched (driver: {$driver}) to {$phoneNumber}: {$title} - {$message}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed sending SMS to {$phoneNumber}: " . $e->getMessage());
            return false;
        }
    }
    */
}
