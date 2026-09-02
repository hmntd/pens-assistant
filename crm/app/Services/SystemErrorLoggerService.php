<?php

namespace App\Services;

use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemErrorLoggerService
{
    public function __construct(
        protected ?NotificationChannelService $notificationChannelService = null
    ) {
        $this->notificationChannelService ??= app(NotificationChannelService::class);
    }

    /**
     * Log exception details to database and notify admins.
     */
    public function logException(Throwable $exception, Request $request, int $statusCode = 500): ?SystemErrorLog
    {
        try {
            $user = $request->user();

            $errorLog = SystemErrorLog::create([
                'user_id' => $user?->id,
                'status_code' => $statusCode,
                'url' => substr($request->fullUrl(), 0, 2048),
                'method' => substr($request->method(), 0, 10),
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage() ?: 'Uncaught server exception occurred.',
                'stack_trace' => $exception->getTraceAsString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'is_resolved' => false,
            ]);

            $this->notifyAdmins($errorLog);

            return $errorLog;
        } catch (Throwable $e) {
            // Fallback to standard Laravel log if logging to DB fails
            Log::critical('Failed to store system error log in database: '.$e->getMessage(), [
                'original_exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send notification to all system administrators regarding the new error log.
     */
    protected function notifyAdmins(SystemErrorLog $errorLog): void
    {
        try {
            $admins = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->get();

            if ($admins->isEmpty()) {
                return;
            }

            $shortMessage = sprintf(
                '[%d] %s %s: %s',
                $errorLog->status_code,
                $errorLog->method,
                parse_url($errorLog->url, PHP_URL_PATH) ?: $errorLog->url,
                substr($errorLog->message, 0, 80)
            );

            foreach ($admins as $admin) {
                $this->notificationChannelService->dispatchNotification(
                    $admin,
                    'Виявлено системну помилку: '.$shortMessage,
                    'System error detected: '.$shortMessage,
                    'error',
                    'system_alerts'
                );
            }
        } catch (Throwable $e) {
            Log::warning('Could not notify admins about system error: '.$e->getMessage());
        }
    }
}
