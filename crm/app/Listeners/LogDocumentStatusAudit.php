<?php

namespace App\Listeners;

use App\Events\DocumentStatusUpdated;
use App\Models\AuditLog;

class LogDocumentStatusAudit
{
    /**
     * Handle the event.
     */
    public function handle(DocumentStatusUpdated $event): void
    {
        AuditLog::create([
            'user_id' => $event->document->user_id,
            'action' => 'document_status_updated',
            'entity_type' => 'Document',
            'entity_id' => $event->document->id,
            'payload' => [
                'status' => $event->status,
                'original_filename' => $event->document->original_filename,
                'message' => $event->message,
            ],
            'ip_address' => request()->ip(),
        ]);
    }
}
