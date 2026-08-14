<?php

namespace App\Listeners;

use App\Events\PfuSalariesSynced;
use App\Models\AuditLog;

class LogPfuSalariesSyncedAudit
{
    /**
     * Handle the PfuSalariesSynced event.
     */
    public function handle(PfuSalariesSynced $event): void
    {
        AuditLog::create([
            'user_id' => $event->userId,
            'action' => 'pfu_average_salaries_synced',
            'entity_type' => 'PfuAverageSalary',
            'entity_id' => null,
            'payload' => [
                'processed_count' => $event->processedCount,
                'record_count' => count($event->records),
                'records' => $event->records,
            ],
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
