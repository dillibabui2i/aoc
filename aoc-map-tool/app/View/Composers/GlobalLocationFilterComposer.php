<?php

namespace App\View\Composers;

use App\Helpers\H;
use Illuminate\View\View;

class GlobalLocationFilterComposer
{
    /**
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        $filters = H::getHelper('CommonDatatableFilters');

        $view->with([
            'stateOptions'  => $filters->getFilterDataSource('state'),
            'branchOptions' => $filters->getFilterDataSource('aoc_facility_id'),
            'regionOptions' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        ]);
    }
}
