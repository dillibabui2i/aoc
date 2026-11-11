<?php

namespace App\View\Composers;

use App\Models\Adjustment;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdjustmentReasonRawComposer
{
    public function compose(View $view): void
    {
        $billingCategories = Cache::remember(
            'billing_categories_dropdown_raw',
            3600,
            function () {
                return Adjustment::query()->pluck('category')->toArray();
            }
        );

        $view->with('billingCategoriesRaw', $billingCategories);
    }
}
