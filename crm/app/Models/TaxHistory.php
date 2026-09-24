<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $document_id
 * @property int $year
 * @property float $annual_income
 * @property float $tax_paid
 * @property int $months_worked
 * @property array|null $monthly_breakdown
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TaxHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tax_histories';

    /**
     * The attributes that are guarded against mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (TaxHistory $taxHistory) {
            if (empty($taxHistory->monthly_breakdown) && (float) $taxHistory->annual_income > 0) {
                $monthsWorked = min(12, max(1, (int) ($taxHistory->months_worked ?: 12)));
                $monthlyAvg = (float) $taxHistory->annual_income / $monthsWorked;
                $breakdown = [];
                for ($m = 1; $m <= 12; $m++) {
                    $breakdown[$m] = $m <= $monthsWorked ? round($monthlyAvg, 2) : 0.0;
                }
                $taxHistory->monthly_breakdown = $breakdown;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'annual_income' => 'decimal:2',
            'tax_paid' => 'decimal:2',
            'months_worked' => 'integer',
            'monthly_breakdown' => 'array',
        ];
    }

    /**
     * Get the user that owns the tax history record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document associated with this tax history record.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
