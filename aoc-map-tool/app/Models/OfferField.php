<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferField extends Model
{
    use HasFactory;

    public $table = 'offer_fields';
    protected $fillable = [
        'section',
        'key',
        'label',
        'value_type',
    ];

    /**
     * Compensation-item Type dropdown options (AC5/AC16 catalog). Adding a future compensation
     * type is a data change here, not a code change.
     */
    public function scopeCompensation(Builder $query): Builder
    {
        return $query->where('section', 'compensation')->orderBy('id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(OfferValue::class, 'key', 'key');
    }

    public function originalValues(): HasMany
    {
        return $this->hasMany(OfferValue::class, 'key', 'key')->where('version', '=','0');
    }

    public function finalValues(): HasMany
    {
        return $this->hasMany(OfferValue::class, 'key', 'key')->where('version', '=','1');
    }
}
