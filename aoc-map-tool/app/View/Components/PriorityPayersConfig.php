<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Helpers\PriorityPayersConfigHelper;
use Illuminate\View\Component;
use Illuminate\View\View;

class PriorityPayersConfig extends Component
{
    protected PriorityPayersConfigHelper $helper;

    public function __construct(PriorityPayersConfigHelper $helper)
    {
        $this->helper = $helper;
    }

    public function render(): View
    {
        $payersByState = $this->helper->getPayersByState();

        return view('components.priority-payers-config', [
            'payers' => $this->helper->getPaymentSourceNames(),
            'payersByState' => $payersByState,
            'payersByStateJson' => json_encode($payersByState),
            'payerPriorities' => $this->helper->getConfiguredPayerPriorities()->groupBy('state')
        ]);
    }
}
