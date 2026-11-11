<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocClientPaymentSourceRepository
{
    private const TABLE_NAME = 'client_payment_source';

    public function getByUniqueId(string $uniqueId): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('cs_unique_id', $uniqueId)
            ->first();
    }

    public function deleteByUniqueId(string $uniqueId): int
    {
        return DB::table(self::TABLE_NAME)
            ->where('cs_unique_id', $uniqueId)
            ->delete();
    }

    public function updateByUniqueId(string $uniqueId, array $row): int
    {
        return DB::table(self::TABLE_NAME)
            ->where('cs_unique_id', $uniqueId)
            ->update($row);
    }

    public function insert(array $row): bool
    {
        return DB::table(self::TABLE_NAME)->insert($row);
    }
}
