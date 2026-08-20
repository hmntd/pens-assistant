<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $notification_translation_id
 * @property string $type
 * @property bool $is_seen
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property NotificationTranslation|null $translation
 */
class Notification extends Model
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
            'is_seen' => 'boolean',
            'user_id' => 'integer',
            'notification_translation_id' => 'integer',
        ];
    }

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the translation associated with the notification.
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(NotificationTranslation::class, 'notification_translation_id');
    }
}
