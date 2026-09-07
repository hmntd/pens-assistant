<?php

namespace App\Jobs;

use App\Models\CalculatedPension;
use App\Models\User;
use App\Services\NotificationChannelService;
use App\Services\PensionCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculateUserPensionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public array $data = [],
        public ?int $calculatedPensionId = null
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(PensionCalculatorService $service): void
    {
        $user = User::find($this->user->id);
        if (! $user) {
            return;
        }

        $calculatedPension = $this->calculatedPensionId
            ? CalculatedPension::find($this->calculatedPensionId)
            : null;

        try {
            $service->calculateAndSave($user, $this->data, $calculatedPension);
        } catch (Throwable $e) {
            Log::error('CalculateUserPensionJob failed', [
                'user_id' => $user->id,
                'calculated_pension_id' => $this->calculatedPensionId,
                'error' => $e->getMessage(),
            ]);

            if ($calculatedPension) {
                $calculatedPension->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            try {
                app(NotificationChannelService::class)->dispatchNotification(
                    $user,
                    "Помилка під час розрахунку пенсії: {$e->getMessage()}",
                    "Pension calculation error: {$e->getMessage()}",
                    'error',
                    'calc_completed'
                );
            } catch (Throwable $ntEx) {
                // silent fallback for notification error
            }

            throw $e;
        }
    }
}
