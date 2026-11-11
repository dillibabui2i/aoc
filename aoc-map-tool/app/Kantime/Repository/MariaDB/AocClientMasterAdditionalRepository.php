<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClientMasterAdditionalRepository
{
    private const TABLE_NAME = 'client_master_additional_daas';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(string $clientAdditionalUniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('client_additional_unique_id', $clientAdditionalUniqueId)
            ->update($row);
    }

    public function upsertByClientAdditionalUniqueId(string $clientAdditionalUniqueId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('client_additional_unique_id', $clientAdditionalUniqueId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($clientAdditionalUniqueId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByClientAdditionalUniqueId(string $clientAdditionalUniqueId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('client_additional_unique_id', $clientAdditionalUniqueId)
            ->delete();
    }

    /**
     * Post-run cleanup: removes duplicate intake_id rows where client_client_id is NULL,
     * keeping only the row with a real client_client_id when duplicates exist.
     */
    public function deleteOrphanedNullClientIds(): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        DB::statement(<<<SQL
            DELETE cmad FROM client_master_additional_daas AS cmad
            WHERE cmad.intake_id IN (
                SELECT intake_id FROM (
                    SELECT cmad1.intake_id
                    FROM client_master_additional_daas AS cmad1
                    WHERE cmad1.client_additional_unique_id LIKE CONCAT(cmad1.intake_id, '%')
                    GROUP BY cmad1.intake_id
                    HAVING COUNT(cmad1.id) > 1
                ) AS subquery
            )
            AND cmad.client_client_id IS NULL
        SQL);
    }
}
