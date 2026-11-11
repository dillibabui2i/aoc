<?php

namespace App\Traits\Map;

trait Need2HireTrait
{
    /**
     * @param $data
     * @param $strictOrderList
     * @return void
     */
    public function checkIsInPriorityOrderingList(&$data, $strictOrderList): void
    {
        foreach ($data as $item) {
            $isVbc = ($item->non_vbc ?? '') !== 'Yes';
            $item->isPriorityzed = $isVbc && in_array($item->Payer, $strictOrderList[$item->State]['list'] ?? []);
        }
    }
}
