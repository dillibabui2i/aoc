<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClientPhysicianMasterRepository
{
    private const TABLE_NAME = 'client_physician_master_daas';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $internalId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('internal_id', $internalId)
            ->update($row);
    }

    public function upsertByInternalId(int $internalId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('internal_id', $internalId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($internalId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByInternalId(int $internalId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('internal_id', $internalId)
            ->delete();
    }
}
