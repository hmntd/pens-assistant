<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public string $status,
        public ?string $message = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->document->user_id),
            new PrivateChannel('App.Models.User.' . $this->document->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'document.status.updated';
    }
}
