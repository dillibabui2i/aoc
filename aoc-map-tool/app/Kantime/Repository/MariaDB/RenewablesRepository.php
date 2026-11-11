<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

final class RenewablesRepository
{
    public function get(int $aocProviderId, int $type): Collection
    {
        return DB::table('renewables')->where('aoc_provider_id', $aocProviderId)
            ->where('renewables_type', $type)
            ->orderBy('id')->get();
    }

    public function deleteByRenewableItemId(int $renewableItemId): void
    {
        DB::table('renewables')
            ->where('renewable_item_id', $renewableItemId)
            ->delete();
    }

    public function insert(array $row): void
    {
        DB::table('renewables')->insert($row);
    }

    public function updateById(int $id, array $row): void
    {
        DB::table('renewables')
            ->where('id', $id)
            ->update($row);
    }
}
