<?php

namespace App\Repositories\MssqlPatients;

use Illuminate\Support\Facades\DB;

class MssqlPatientsWriteRepository
{
    public function updateClientCallFrequency(int $patientId, int $freq, ?string $updatedAt = null): int
    {
        return DB::table('mssql_patients')
            ->where('id', $patientId)
            ->update([
                'ccc_client_call_frequency' => $freq,
                'updated_at' => $updatedAt ?? now(),
            ]);
    }
}
