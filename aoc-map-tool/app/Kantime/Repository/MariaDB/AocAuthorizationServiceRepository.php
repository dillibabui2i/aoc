<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocAuthorizationServiceRepository
{
    private const TABLE_NAME = 'authorizations_service';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(string $authServiceUniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('auth_service_unique_id', $authServiceUniqueId)
            ->update($row);
    }

    public function upsertByAuthServiceUniqueId(string $authServiceUniqueId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('auth_service_unique_id', $authServiceUniqueId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($authServiceUniqueId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByAuthServiceUniqueId(string $authServiceUniqueId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('auth_service_unique_id', $authServiceUniqueId)
            ->delete();
    }
}
