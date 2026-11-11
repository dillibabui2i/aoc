<?php

namespace App\Repositories\PayerRate;

use App\Enums\PayerRatesStatus;
use App\Helpers\H;
use App\PayerRates;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class PayerRateReadRepository
{
    public function getSoonExpiringRecords(): LazyCollection
    {
        return PayerRates::query()->select(
            'payer_rates.id',
            DB::raw('COALESCE(payment_source.name, payer_rates.payer) as name'),
            'payer_rates.effectiveThrough',
        )->leftJoin('payment_source', 'payer_id', '=', 'payment_source.payersource_id')
            ->where('payer_rates.is_deleted', '0')
            ->where('payer_rates.status', PayerRatesStatus::APPROVED)
            ->where(function ($query) {
                $query->whereDate('payer_rates.effectiveThrough', Carbon::now()->addDays(1))
                    ->orWhereDate('payer_rates.effectiveThrough', Carbon::now()->addDays(3));
            })->cursor();
    }
}
