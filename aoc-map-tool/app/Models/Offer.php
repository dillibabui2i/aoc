<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection originalValues
 * @property Collection finalValues
 */
class Offer extends Model
{
    public const STATUS_SENT = 1;

    use HasFactory;

    public $table = 'offers';
    protected $fillable = [
        'employee_id',
        'status',
        'created_by',
    ];

    public function isSent(): bool
    {
        return $this->status === 1;
    }

    public function values(): HasMany
    {
        return $this->hasMany(OfferValue::class);
    }

    public function originalValues(): HasMany
    {
        return $this->hasMany(OfferValue::class)->where('version', '=',0);
    }

    public function finalValues(): HasMany
    {
        return $this->hasMany(OfferValue::class)->where('version', '=',1);
    }

    public function compensationItems(): HasMany
    {
        return $this->hasMany(OfferCompensationItem::class)->orderBy('sort_order');
    }

    public function originalCompensationItems(): HasMany
    {
        return $this->compensationItems()->where('version', '=', OfferCompensationItem::VERSION_ORIGINAL);
    }

    public function finalCompensationItems(): HasMany
    {
        return $this->compensationItems()->where('version', '=', OfferCompensationItem::VERSION_FINAL);
    }
}
