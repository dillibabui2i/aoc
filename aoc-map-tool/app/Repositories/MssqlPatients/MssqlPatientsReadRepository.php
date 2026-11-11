<?php

namespace App\Repositories\MssqlPatients;

use App\MssqlPatients;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MssqlPatientsReadRepository
{
    public function __construct(private MssqlPatients $patients)
    {
    }

    public function getById(int $patientId): ?object
    {
        return DB::table('mssql_patients')
            ->select('id', 'ccc_id', 'created_at', 'ccc_client_call_frequency')
            ->where('id', $patientId)
            ->first();
    }

    public function findById(int $patientId): ?MssqlPatients
    {
        return $this->patients->query()->find($patientId);
    }

    public function getEligibleForCccCalls(string $monthStartYmd): Collection
    {
        $exceptLineOfBusiness = ['PT', 'OT', 'ST'];

        return DB::table('mssql_patients')
            ->select('mssql_patients.*')
            ->leftJoin('mssql_facilities', function ($q) {
                $q->on('mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
            })
            ->leftJoin('lob', function ($q) {
                $q->on('lob.LOBID', '=', 'mssql_patients.LOBID');
            })
            ->whereNotIn('lob.LOBName', $exceptLineOfBusiness)
            ->whereNotIn('mssql_patients.aoc_facility_id', ['14', '43', '44'])
            ->where('isPRN', 0)
            ->whereRaw("mssql_patients.Search_Name not like '%Test%'")
            ->whereRaw("mssql_patients.Search_Name not like '%KanTime%'")
            ->whereRaw("mssql_patients.Search_Name not like '%PRN%'")
            ->whereRaw("(mssql_facilities.State <> 'CO' OR mssql_patients.LOBID = 1023)")
            ->where('mssql_patients.created_at', '<', $monthStartYmd . ' 00:00:00')
            ->whereIn('mssql_patients.Patient_CurrentStatusID', [1, 6, 7, 8])
            ->where('mssql_patients.IsLatestAdmit', '=', 1)
            ->whereRaw('ifnull(mssql_patients.PatientID,0) <> 0')
            ->get();
    }
}
