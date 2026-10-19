<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SystemErrorLog extends Model
{
    use HasFactory;

    public const CACHE_KEY_STATS = 'admin:system_errors:stats';

    protected $table = 'system_error_logs';

    protected $fillable = [
        'user_id',
        'status_code',
        'url',
        'method',
        'exception_class',
        'message',
        'stack_trace',
        'user_agent',
        'ip_address',
        'is_resolved',
        'resolved_at',
        'resolved_by_id',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY_STATS));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY_STATS));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
