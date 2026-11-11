<?php

namespace App\Kantime\Repository\MariaDB;

final class FacilityRepository
{
    public function getFacilityList(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('mssql_facilities')
            ->select(['BillingFacilityID', 'FacilityID', 'id'])
            ->get();
    }
}
