<?php

namespace App\Providers;

use App\View\Composers\AdjustmentReasonRawComposer;
use App\View\Composers\GlobalLocationFilterComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['billing.billingInvoiceOutcome'], AdjustmentReasonRawComposer::class);
        View::composer(['account.dashboard'], GlobalLocationFilterComposer::class);
    }
}
