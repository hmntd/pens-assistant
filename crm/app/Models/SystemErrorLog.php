<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorLog extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
