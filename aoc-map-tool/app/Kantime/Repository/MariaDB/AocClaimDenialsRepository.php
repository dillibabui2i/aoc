<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClaimDenialsRepository
{
    private const TABLE_NAME = 'claim_denials';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $denialId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('denial_id', $denialId)
            ->update($row);
    }

    public function upsertByDenialId(int $denialId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('denial_id', $denialId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($denialId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByDenialId(int $denialId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('denial_id', $denialId)
            ->delete();
    }
}
