<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocServiceCodeRatesRepository
{
    const TABLE_NAME = 'service_code_rates';
    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $uniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('serviceRateId', $uniqueId)
            ->update($row);
    }

    public function upsertByServiceRateId(int $uniqueId, array $row): void
    {
        $dbRecord = DB::table(self::TABLE_NAME)
            ->where('serviceRateId', $uniqueId)
            ->first();

        if ($dbRecord) {
            $this->update($uniqueId, $row);
            return;
        }

        $this->create($row);
    }
}
