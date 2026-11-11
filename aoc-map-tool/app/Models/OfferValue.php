<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferValue extends Model
{
    use HasFactory;

    public const SCOPE_ORIGINAL = 0;
    public const SCOPE_FINAL = 1;
    public $table = 'offer_values';

    protected $fillable = [
        'offer_id',
        'key',
        'version',
        'value_string',
        'value_decimal',
        'value_int',
        'value_date',
        'value_bool',
        'value_json',
        'updated_by',
    ];

    protected $casts = [
        'value_decimal' => 'decimal:2',
        'value_int' => 'integer',
        'value_date' => 'date',
        'value_bool' => 'boolean',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(OfferField::class, 'key', 'key');
    }
}
