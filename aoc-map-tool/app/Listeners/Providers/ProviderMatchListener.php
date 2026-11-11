<?php

namespace App\Listeners\Providers;

use App\Events\Providers\ProvidersBatchSynced;
use App\Jobs\Providers\MatchCandidatesToProvidersJob;

class ProviderMatchListener
{

    public function handle(ProvidersBatchSynced $event): void
    {
        dispatch(new MatchCandidatesToProvidersJob($event->providerIds));
    }

}
