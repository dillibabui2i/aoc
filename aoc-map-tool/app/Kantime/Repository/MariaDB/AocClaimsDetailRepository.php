<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClaimsDetailRepository
{
    private const TABLE_NAME = 'claims_detail';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $claimDetailId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claim_detail_id', $claimDetailId)
            ->update($row);
    }

    public function upsertByClaimDetailId(int $claimDetailId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('claim_detail_id', $claimDetailId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($claimDetailId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByClaimDetailId(int $claimDetailId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claim_detail_id', $claimDetailId)
            ->delete();
    }
}
