<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $file_path
 * @property string $original_filename
 * @property string $document_type
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are guarded against mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tax history records generated from this document.
     */
    public function taxHistories(): HasMany
    {
        return $this->hasMany(TaxHistory::class);
    }

    /**
     * Get the OCR recognition result for the document.
     */
    public function recognizedDocument(): HasOne
    {
        return $this->hasOne(RecognizedDocument::class);
    }
}
