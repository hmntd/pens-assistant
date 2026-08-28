<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PensionCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        public array $data = []
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

        $service->calculateAndSave($user, $this->data);
    }
}
