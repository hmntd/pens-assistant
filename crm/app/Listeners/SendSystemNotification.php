<?php

namespace App\Listeners;

use App\Events\DocumentStatusUpdated;
use App\Events\DocumentUploaded;
use App\Events\PensionCalculated;
use App\Events\TaxHistoryAdded;
use App\Events\UserProfileUpdated;
use App\Models\User;
use App\Services\NotificationChannelService;

class SendSystemNotification
{
    public function __construct(
        protected NotificationChannelService $notificationChannelService
    ) {}

    /**
     * Handle DocumentUploaded event.
     */
    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        $user = User::find($event->document->user_id);
        if (! $user) {
            return;
        }

        $filename = $event->document->original_filename ?: "№{$event->document->id}";

        $this->notificationChannelService->dispatchNotification(
            $user,
            "Файл '{$filename}' успішно завантажено та передано на OCR розпізнавання.",
            "File '{$filename}' successfully uploaded and sent for OCR recognition.",
            'success',
            'document_processed'
        );
    }

    /**
     * Handle DocumentStatusUpdated event.
     */
    public function handleDocumentStatusUpdated(DocumentStatusUpdated $event): void
    {
        $user = User::find($event->document->user_id);
        if (! $user) {
            return;
        }

        $document = $event->document;
        $status = strtolower($event->status);
        $filename = $document->original_filename ?: "№{$document->id}";

        if ($status === 'completed') {
            $this->notificationChannelService->dispatchNotification(
                $user,
                "Документ '{$filename}' успішно розпізнано та дані внесені до страхового стажу.",
                "Document '{$filename}' has been successfully recognized and added to insurance history.",
                'success',
                'document_processed'
            );
        } elseif ($status === 'failed') {
            $this->notificationChannelService->dispatchNotification(
                $user,
                "Помилка при розпізнаванні документа '{$filename}'.",
                "Failed to process document recognition for '{$filename}'.",
                'error',
                'document_processed'
            );
        }
    }

    /**
     * Handle TaxHistoryAdded event.
     */
    public function handleTaxHistoryAdded(TaxHistoryAdded $event): void
    {
        $this->notificationChannelService->dispatchNotification(
            $event->user,
            "Запис про страховий стаж успішно додано.",
            "Insurance service record added successfully.",
            'success',
            'system_alerts'
        );
    }

    /**
     * Handle UserProfileUpdated event.
     */
    public function handleUserProfileUpdated(UserProfileUpdated $event): void
    {
        $this->notificationChannelService->dispatchNotification(
            $event->user,
            "Персональні дані та налаштування профілю успішно оновлено.",
            "Personal data and profile settings updated successfully.",
            'success',
            'system_alerts'
        );
    }

    /**
     * Handle PensionCalculated event.
     */
    public function handlePensionCalculated(PensionCalculated $event): void
    {
        $this->notificationChannelService->dispatchNotification(
            $event->user,
            "Розрахунок пенсійних виплат успішно виконано.",
            "Pension calculation successfully completed.",
            'calculation',
            'calc_completed'
        );
    }
}
