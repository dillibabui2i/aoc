<?php

namespace App\Repositories\MssqlProviders;

use App\MssqlProviders;
use Illuminate\Support\LazyCollection;

class MssqlProvidersReadRepository
{
    public function __construct(private MssqlProviders $providers)
    {
    }

    public function getProviders(array $providersIds): LazyCollection
    {
        return $this->providers->query()
          ->whereIn('id', $providersIds)
          ->select('id', 'Phone', 'Mobile', 'AOC_Email', 'External_Email')
          ->cursor();
    }

    public function getById(int $providerId): ?MssqlProviders
    {
        return $this->providers->query()->find($providerId);
    }
}
