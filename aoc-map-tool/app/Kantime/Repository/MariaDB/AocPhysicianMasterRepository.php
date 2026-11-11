<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocPhysicianMasterRepository
{
    private const TABLE_NAME = 'physician_master';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $physicianId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('physician_id', $physicianId)
            ->update($row);
    }

    public function upsertByPhysicianId(int $physicianId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('physician_id', $physicianId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($physicianId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByPhysicianId(int $physicianId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('physician_id', $physicianId)
            ->delete();
    }
}
