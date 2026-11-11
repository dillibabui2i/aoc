<?php

namespace App\DTO\WhiteBoard;

class StateData
{
    /**
     * @param string $stateCode
     * @param string $stateName
     * @param TotalsData $totalsData
     */
    public function __construct(
        public string $stateCode,
        public string $stateName,
        public TotalsData $totalsData
    )
    {}
}
