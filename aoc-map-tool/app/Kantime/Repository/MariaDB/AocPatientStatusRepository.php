<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocPatientStatusRepository
{
    private const TABLE_NAME = 'mssql_patientstatus';

    public function getStatusIdByName(string $status): int
    {
        $record = DB::table(self::TABLE_NAME)
            ->select('PatientStatusID')
            ->where('PatientStatus', $status)
            ->first();

        return isset($record->PatientStatusID) ? (int) $record->PatientStatusID : 0;
    }
}
