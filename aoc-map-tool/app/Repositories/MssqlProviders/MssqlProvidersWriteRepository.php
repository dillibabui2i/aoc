<?php

namespace App\Repositories\MssqlProviders;

use App\MssqlProviders;

class MssqlProvidersWriteRepository
{

    public function update(MssqlProviders $providers, array $data): void
    {
        $providers->update($data);
    }

}
