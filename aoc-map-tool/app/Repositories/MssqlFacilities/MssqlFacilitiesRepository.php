<?php

namespace App\Repositories\MssqlFacilities;

use App\MssqlFacilities;

class MssqlFacilitiesRepository
{
    public function __construct(private MssqlFacilities $facilities)
    {
    }

    public function getUniqueStates(): array
    {
        return $this->facilities
          ->query()
          ->distinct()
          ->select('State')
          ->get()
          ->sortBy('State')
          ->pluck('State')
          ->toArray();
    }

    public function getById(int $facilityId): ?MssqlFacilities
    {
        return $this->facilities->query()->find($facilityId);
    }
}
