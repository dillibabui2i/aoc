<?php

namespace App\Transformer\Dashboard\Buckets\Jobvite;

class TherapyApplicationsTransformer extends AbstractApplicationsTransformer
{
    protected function getButtonsView(): string
    {
        return 'datatables.dashboard.jobvite.therapy.buttons';
    }

    protected function getCheckboxView(): string
    {
        return 'datatables.dashboard.jobvite.therapy.checkbox';
    }
}
