<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentOcrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentOcrJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentOcrService $ocrService): void
    {
        $document = Document::find($this->document->id);
        if (!$document || in_array($document->status, ['completed', 'failed'], true)) {
            return;
        }

        $ocrService->processDocument($document);
    }
}
