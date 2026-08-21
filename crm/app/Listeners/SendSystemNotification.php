<?php

namespace App\Listeners;

use App\Events\DocumentStatusUpdated;
use App\Events\DocumentUploaded;
use App\Events\PensionCalculated;
use App\Events\TaxHistoryAdded;
use App\Events\UserProfileUpdated;
use App\Models\Notification;
use App\Models\NotificationTranslation;

class SendSystemNotification
{
    /**
     * Handle DocumentUploaded event.
     */
    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        $filename = $event->document->original_filename ?: "№{$event->document->id}";

        $translation = NotificationTranslation::firstOrCreate([
            'uk' => "Файл '{$filename}' успішно завантажено та передано на OCR розпізнавання.",
            'en' => "File '{$filename}' successfully uploaded and sent for OCR recognition.",
        ]);

        Notification::create([
            'user_id' => $event->document->user_id,
            'notification_translation_id' => $translation->id,
            'type' => 'success',
        ]);
    }

    /**
     * Handle DocumentStatusUpdated event.
     */
    public function handleDocumentStatusUpdated(DocumentStatusUpdated $event): void
    {
        $document = $event->document;
        $status = strtolower($event->status);
        $filename = $document->original_filename ?: "№{$document->id}";

        if ($status === 'completed') {
            $translation = NotificationTranslation::firstOrCreate([
                'uk' => "Документ '{$filename}' успішно розпізнано та дані внесені до страхового стажу.",
                'en' => "Document '{$filename}' has been successfully recognized and added to insurance history.",
            ]);

            Notification::create([
                'user_id' => $document->user_id,
                'notification_translation_id' => $translation->id,
                'type' => 'success',
            ]);
        } elseif ($status === 'failed') {
            $translation = NotificationTranslation::firstOrCreate([
                'uk' => "Помилка при розпізнаванні документа '{$filename}'.",
                'en' => "Failed to process document recognition for '{$filename}'.",
            ]);

            Notification::create([
                'user_id' => $document->user_id,
                'notification_translation_id' => $translation->id,
                'type' => 'error',
            ]);
        }
    }

    /**
     * Handle TaxHistoryAdded event.
     */
    public function handleTaxHistoryAdded(TaxHistoryAdded $event): void
    {
        $translation = NotificationTranslation::firstOrCreate([
            'uk' => "Запис про страховий стаж успішно додано.",
            'en' => "Insurance service record added successfully.",
        ]);

        Notification::create([
            'user_id' => $event->user->id,
            'notification_translation_id' => $translation->id,
            'type' => 'success',
        ]);
    }

    /**
     * Handle UserProfileUpdated event.
     */
    public function handleUserProfileUpdated(UserProfileUpdated $event): void
    {
        $translation = NotificationTranslation::firstOrCreate([
            'uk' => "Персональні дані та налаштування профілю успішно оновлено.",
            'en' => "Personal data and profile settings updated successfully.",
        ]);

        Notification::create([
            'user_id' => $event->user->id,
            'notification_translation_id' => $translation->id,
            'type' => 'success',
        ]);
    }

    /**
     * Handle PensionCalculated event.
     */
    public function handlePensionCalculated(PensionCalculated $event): void
    {
        $translation = NotificationTranslation::firstOrCreate([
            'uk' => "Розрахунок пенсійних виплат успішно виконано.",
            'en' => "Pension calculation successfully completed.",
        ]);

        Notification::create([
            'user_id' => $event->user->id,
            'notification_translation_id' => $translation->id,
            'type' => 'calculation',
        ]);
    }
}
