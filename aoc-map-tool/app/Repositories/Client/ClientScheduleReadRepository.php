<?php

namespace App\Repositories\Client;

use App\Helpers\LD;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientScheduleReadRepository
{
    const LIMIT = 15;
    const FROM_ADD_NTH = 'addnth';
    const FROM_ADD_INTAKE = 'addIntake';

    /**
     * @param string $term
     * @return Collection
     * @throws Exception
     */
    public function getClientSchedule(string $term): Collection
    {
        $clientlist = DB::table('mssql_patients')
            ->select(
                'mssql_patients.id',
                'mssql_patients.Patient_FirstName',
                'mssql_patients.Patient_LastName',
                'mssql_facilities.FacilityName',
                'mssql_facilities.BranchName',
                'mssql_patients.PatientAccount',
                'mssql_patients.Patient_City',
                'mssql_patients.Patient_State',
                'mssql_patients.Patient_Phone',
                'mssql_patients.Payer',
                'mssql_patientstatus.PatientStatus',
                'mssql_patients.CF_Skills',
                'mssql_patients.AdmitNo',
            )
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=', 'mssql_patients.Patient_CurrentStatusID');

        if (empty($request->from)) {////todo check and move the statuses to the enum
            $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [2, 3, 5]);
        } else {
            if (isset($request->from) && $request->from == self::FROM_ADD_NTH) {
                if (LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {
                    $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [2]);
                } else {
                    $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.Patient_CurrentStatusID,0)'), [2, 5]);
                }
            }
        }

        if(!isset($request->from) || $request->from != self::FROM_ADD_INTAKE){
            $clientlist->whereNotIn(DB::raw('IFNULL(mssql_patients.LOBID,0)'), [1004,1006,1003,1005]);
        }

        $clientlist->where('mssql_patients.IsLatestAdmit', 1);
        $clientlist->addselect('mssql_patients.Patient_Address1',
            'mssql_patients.Patient_Address2',
            'mssql_patients.Patient_Zip',
            'mssql_patients.RespitePayer',
            'mssql_patients.aoc_facility_id',
            'mssql_patients.LOBID',
            'mssql_patients.CF_Specialized',
            'mssql_patients.ic_id');

        $clientlist->addselect('mssql_patients.CF_SchoolName',
            'mssql_patients.CF_SchoolDistrict');

       $clientlist = $clientlist->where(
            function ($query) use($term) {
                $query->where('mssql_patients.Search_Name', 'like', '%' . $term . '%')
                    ->orWhere('mssql_patients.PatientAccount', 'like', '%' . $term . '%');
            }
        )
        ->limit(self::LIMIT)
        ->get();

        return $clientlist;
    }
}
