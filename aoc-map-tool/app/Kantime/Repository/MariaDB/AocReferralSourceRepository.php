<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocReferralSourceRepository
{
    const TABLE_NAME = 'referralsource_master';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $uniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('source_id', $uniqueId)
            ->update($row);
    }

    public function upsertByReferralSourceId(int $uniqueId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $dbRecord = DB::table(self::TABLE_NAME)
            ->where('source_id', $uniqueId)
            ->first();

        if ($dbRecord) {
            $row['updated_by'] = 1;
            $this->update($uniqueId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByReferralSourceId(int $uniqueId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('source_id', $uniqueId)
            ->delete();
    }

}
