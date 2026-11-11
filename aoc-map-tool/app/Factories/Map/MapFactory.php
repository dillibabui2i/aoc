<?php

namespace App\Factories\Map;

use App\DTO\Map\MapTableParams;
use App\Services\Map\NeedToHireService;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

class MapFactory
{
    /**
     * @param string $tableName
     * @param MapTableParams $mapTableParams
     * @return NeedToHireService
     */
    public function getInstance(string $tableName, MapTableParams $mapTableParams)
    {
        if ($tableName === 'maps_nth') {
            return new NeedToHireService($mapTableParams);
        }

        throw new InvalidArgumentException('Table name is not a valid map table');
    }
}
