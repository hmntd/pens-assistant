<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property float|null $final_pension
 * @property float|null $base_pension
 * @property float|null $zp_macroeconomic_average
 * @property float|null $kz_wage_coefficient
 * @property float|null $ks_service_coefficient
 * @property int|null $total_service_months
 * @property string|null $pension_type
 * @property string|null $disability_group
 * @property array|null $input_parameters
 * @property array|null $applied_benefits
 * @property array|null $calculation_logs
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
            'final_pension' => 'decimal:2',
            'base_pension' => 'decimal:2',
            'zp_macroeconomic_average' => 'decimal:2',
            'kz_wage_coefficient' => 'decimal:4',
            'ks_service_coefficient' => 'decimal:4',
            'total_service_months' => 'integer',
            'calculation_breakdown' => 'array',
            'input_parameters' => 'array',
            'applied_benefits' => 'array',
            'calculation_logs' => 'array',
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
