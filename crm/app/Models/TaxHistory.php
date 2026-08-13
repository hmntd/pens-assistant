<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $document_id
 * @property int $year
 * @property float $annual_income
 * @property float $tax_paid
 * @property int $months_worked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TaxHistory extends Model
{
    use HasFactory;

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
