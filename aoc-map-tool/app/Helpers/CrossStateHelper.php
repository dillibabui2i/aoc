<?php

declare(strict_types=1);

namespace App\Helpers;

class CrossStateHelper
{
    private const IPEA_CROSS_STATE_NOTE = "Cross-state note added: This nurse is scheduled for an IPEA
        in %s for a client in %s. The IPEA email will include branch/location
        details for %s and all other resources will reflect %s.";

    public function getIpeaCrossStateNotes(string $ipeaState, string $clientState): string
    {
        return sprintf(self::IPEA_CROSS_STATE_NOTE, $ipeaState, $clientState, $ipeaState, $clientState);
    }

    public function isCrossState(string $state1, string $state2): bool
    {
        return $state1 !== $state2;
    }
}
