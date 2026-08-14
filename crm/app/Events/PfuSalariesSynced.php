<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PfuSalariesSynced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<int, array{year: int, month: int, amount: float}> $records
     */
    public function __construct(
        public array $records,
        public int $processedCount,
        public ?int $userId = null
    ) {}
}
