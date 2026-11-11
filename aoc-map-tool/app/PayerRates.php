<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayerRates extends Model
{
    use HasFactory;

    //protected $table = 'payer_rates';
    protected $fillable = [
        'payer_id',
        'payer',
        'is_custom',
        'pending_data',
        'effectiveFrom',
        'effectiveThrough',
        'comment',
        'hhagroupprimaryrate',
        'cnagroupprimaryrate',
        'lvnlpnrate',
        'lvnlpnspecrate',
        'rnrate',
        'rnspecrate',
        'status',
        'approved_by',
        'approved_at',
        'denied_by',
        'denied_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'pending_data' => 'array',
    ];

    public function getEffectiveFromAttribute($value)
    {
        return getFormatedDate($value);
    }

    public function getEffectiveThroughAttribute($value)
    {
        return getFormatedDate($value);
    }

    public function isExistDuplicateInDateRange(string $payer, string $start, string $end):bool
    {
        return $this->where('payer', $payer)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('effectiveFrom', [$start, $end])
                    ->orWhereBetween('effectiveThrough', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('effectiveFrom', '<=', $start)
                            ->where('effectiveThrough', '>=', $end);
                    });
            })
            ->exists();
    }

    public function services()
    {
        return $this->hasMany(PayerRateService::class, 'payer_rate_id');
    }
}
