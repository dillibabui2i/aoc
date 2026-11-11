<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocAuthorizationsMasterRepository
{
    private const TABLE_NAME = 'authorizations';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $authId, array $row): void
    {
        $row['updated_by'] = 1;
        DB::table(self::TABLE_NAME)
            ->where('auth_id', $authId)
            ->update($row);
    }

    public function upsertByAuthId(int $authId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('auth_id', $authId)
            ->exists();

        if ($exists) {
            $this->update($authId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByAuthId(int $authId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('auth_id', $authId)
            ->delete();
    }

    public function resolveAocFacilityId(?int $agencyBranchId): int
    {
        if ($agencyBranchId === null) {
            return 0;
        }

        $facility = DB::table('mssql_facilities')
            ->select('id')
            ->where('BillingFacilityID', $agencyBranchId)
            ->first();

        return $facility ? (int) $facility->id : 0;
    }
}
