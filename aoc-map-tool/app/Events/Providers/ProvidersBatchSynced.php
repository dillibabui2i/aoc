<?php

namespace App\Events\Providers;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProvidersBatchSynced
{

    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly array $providerIds) {}

}
