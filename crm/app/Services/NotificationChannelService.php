<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTranslation;
use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
        string $messageUk,
        string $messageEn,
        string $type = 'info',
        ?string $eventCategory = null
    ): array {
        $channels = $user->getNotificationChannel();

        // Check if this event category is allowed by user preferences
        if ($eventCategory && ! $this->shouldSendForCategory($channels, $eventCategory)) {
            return ['in_app' => false, 'suppressed' => true];
        }

        $results = [];

        // 1. In-App Notification (Always stored)
        $translation = NotificationTranslation::firstOrCreate([
            'uk' => $messageUk,
            'en' => $messageEn,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'notification_translation_id' => $translation->id,
            'type' => $type,
            'is_seen' => false,
        ]);
        $results['in_app'] = true;

        // Determine user's active locale ('en' or 'uk')
        $locale = app()->getLocale();
        $localizedMessage = (strtolower($locale) === 'en') ? $messageEn : $messageUk;

        // 2. Email Channel
        if ($channels->email_enabled && ! empty($user->email)) {
            $results['email'] = $this->sendEmail($user->email, 'PensAssistant', $localizedMessage);
        }

        // 3. Telegram Channel
        if ($channels->telegram_enabled && ! empty($channels->telegram_chat_id)) {
            $results['telegram'] = $this->sendTelegram($channels->telegram_chat_id, 'PensAssistant', $localizedMessage);
        }

        return $results;
    }

    /**
     * Send a test notification to a specific channel.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestNotification(User $user, string $channel, ?string $inputTelegramChatId = null): array
    {
        $settings = $user->getNotificationChannel();
        $testTitle = '🔔 Тестове сповіщення';
        $testMessage = 'Це тестове сповіщення від системи PensAssistant для перевірки з’єднання з каналом.';

        switch ($channel) {
            case 'email':
                if (empty($user->email)) {
                    return ['success' => false, 'message' => 'Електронна адреса користувача відсутня.'];
                }
                $sent = $this->sendEmail($user->email, $testTitle, $testMessage);
                return [
                    'success' => $sent,
                    'message' => $sent ? "Тестовий лист надіслано на {$user->email}." : 'Не вдалося надіслати тестовий лист.',
                ];

            case 'telegram':
                // Use input field value first, then fall back to database value
                $chatId = ! empty($inputTelegramChatId) ? trim($inputTelegramChatId) : $settings->telegram_chat_id;

                if (empty($chatId)) {
                    return ['success' => false, 'message' => 'Telegram Chat ID не налаштовано. Натисніть піктограму підказки (i) для інструкції з налаштування.'];
                }

                $sent = $this->sendTelegram($chatId, $testTitle, $testMessage);

                return [
                    'success' => $sent,
                    'message' => $sent 
                        ? "Тестове повідомлення успішно надіслано в Telegram (Chat ID: {$chatId})." 
                        : 'Не вдалося надіслати тестове повідомлення в Telegram. Перевірте введений Chat ID та переконайтеся, що ви натиснули /start у боті.',
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            Log::error("Failed sending SMS to {$phoneNumber}: " . $e->getMessage());
            return false;
        }
    }
    */
}
