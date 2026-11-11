<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClaimsMasterRepository
{
    private const TABLE_NAME = 'claims_master';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $claimId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claim_id', $claimId)
            ->update($row);
    }

    public function upsertByClaimId(int $claimId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('claim_id', $claimId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($claimId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByClaimId(int $claimId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claim_id', $claimId)
            ->delete();
    }
}
