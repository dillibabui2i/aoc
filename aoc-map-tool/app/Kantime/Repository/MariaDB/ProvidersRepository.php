<?php

namespace App\Kantime\Repository\MariaDB;

final class ProvidersRepository
{
    public function get($id)
    {
        return \Illuminate\Support\Facades\DB::table('mssql_providers')
            ->where('BillingProviderID', $id)
            ->get()->first();
    }
}
