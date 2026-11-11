<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocClientMedicationMasterRepository
{
    private const TABLE_NAME = 'client_medication_master_daas';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(string $clientMedicationUniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('client_medication_unique_id', $clientMedicationUniqueId)
            ->update($row);
    }

    public function upsertByClientMedicationUniqueId(string $clientMedicationUniqueId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('client_medication_unique_id', $clientMedicationUniqueId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($clientMedicationUniqueId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByClientMedicationUniqueId(string $clientMedicationUniqueId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('client_medication_unique_id', $clientMedicationUniqueId)
            ->delete();
    }
}
