<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferCompensationItem extends Model
{
    use HasFactory;

    public const VERSION_ORIGINAL = 0;
    public const VERSION_FINAL = 1;

    public $table = 'offer_compensation_items';

    protected $fillable = [
        'offer_id',
        'version',
        'offer_field_id',
        'custom_label',
        'amount',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function offerField(): BelongsTo
    {
        return $this->belongsTo(OfferField::class, 'offer_field_id');
    }

    public function label(): string
    {
        if ($this->offerField?->key === 'other') {
            return $this->custom_label ?: 'Other';
        }

        return $this->offerField?->label ?? $this->custom_label ?? 'Other';
    }
}
