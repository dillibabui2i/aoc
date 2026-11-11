<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClaimsAdjustmentRepository
{
    private const TABLE_NAME = 'claims_adjustment';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $adjustmentId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('adjustment_id', $adjustmentId)
            ->update($row);
    }

    public function upsertByAdjustmentId(int $adjustmentId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('adjustment_id', $adjustmentId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($adjustmentId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByAdjustmentId(int $adjustmentId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('adjustment_id', $adjustmentId)
            ->delete();
    }
}
