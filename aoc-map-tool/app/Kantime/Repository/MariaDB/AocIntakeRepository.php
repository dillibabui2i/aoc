<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocIntakeRepository
{
    private const TABLE_NAME = 'intake';

    public function findByPatientAndLob(int $aocPatientId, mixed $lobId): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('aoc_patient_id', $aocPatientId)
            ->where('LOBID', $lobId)
            ->orderByDesc('id')
            ->first();
    }

    public function insert(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $id, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('id', $id)
            ->update($row);
    }

    public function syncIcIdFromPatients(): void
    {
        DB::statement(
            'UPDATE intake i
            LEFT JOIN mssql_patients mp ON mp.id = i.aoc_patient_id
            SET i.ic_id = mp.ic_id
            WHERE IFNULL(i.ic_id, 0) = 0
              AND IFNULL(mp.ic_id, 0) <> 0'
        );
    }

    public function markNonAdmitIntakes(): void
    {
        DB::statement(
            "UPDATE intake i
            LEFT JOIN mssql_patients mp ON mp.id = i.aoc_patient_id
            SET i.intake_completed = 1,
                i.intake_bucket = 'intake_nonadmit'
            WHERE mp.IsNonAdmit = 1"
        );
    }
}
