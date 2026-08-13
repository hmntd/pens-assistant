<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property float $estimated_monthly_pension
 * @property float $total_accumulated_capital
 * @property array|null $calculation_breakdown
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalculatedPension extends Model
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
            'estimated_monthly_pension' => 'decimal:2',
            'total_accumulated_capital' => 'decimal:2',
            'calculation_breakdown' => 'array',
        ];
    }

    /**
     * Get the user that owns the calculated pension record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
