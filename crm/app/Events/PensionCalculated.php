<?php

namespace App\Events;

use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PensionCalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public CalculatedPension $calculatedPension
    ) {}
}
