<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClaimsPaymentRepository
{
    private const TABLE_NAME = 'claims_payment';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $claimpaymentId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claimpayment_id', $claimpaymentId)
            ->update($row);
    }

    public function upsertByClaimPaymentId(int $claimpaymentId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('claimpayment_id', $claimpaymentId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($claimpaymentId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByClaimPaymentId(int $claimpaymentId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('claimpayment_id', $claimpaymentId)
            ->delete();
    }
}
