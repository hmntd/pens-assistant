<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $email_enabled
 * @property bool $telegram_enabled
 * @property string|null $telegram_chat_id
 * @property bool $sms_enabled
 * @property string|null $phone_number
 * @property bool $notify_calc_completed
 * @property bool $notify_document_processed
 * @property bool $notify_system_alerts
 * @property bool $notify_pension_updates
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 */
class UserNotificationChannel extends Model
{
    use HasFactory;

    /**
     * The attributes that are guarded against mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'email_enabled' => 'boolean',
            'telegram_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'notify_calc_completed' => 'boolean',
            'notify_document_processed' => 'boolean',
            'notify_system_alerts' => 'boolean',
            'notify_pension_updates' => 'boolean',
        ];
    }

    /**
     * Get the user associated with these notification channel settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
