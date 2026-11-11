<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class RenewablesTypesRepository
{
    public function insert(array $row): void
    {
        DB::table('renewables_types')->insert($row);
    }

    public function update(int $id, array $row): void
    {
        DB::table('renewables')
            ->where('id', $id)
            ->update($row);
    }

    public function get(int $id) : array
    {
        return DB::select("SELECT * FROM renewables_types WHERE renewable_type_id = ?", [$id]);
    }
}
