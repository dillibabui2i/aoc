<?php
namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class clinicianNoLongerOnCase
{
    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
            case 'clinician_no_longer_on_case':
                $dataTable = [
                    'clinicianName' => "Clinician",
                    'clientName' => "Client",
                    'Client_Coordinator' => 'Client Care Coordinator',
                    'latest_schedule' => 'Last Date Worked',
                    'Notes' => 'Notes',
                ];
                break;
            default:
                break;
        }
        
        return view('ccc.clinicianNoLongerOnCase_datatable',["type" => $type, 'dataTable' => $dataTable]);
    }

    
    public function getClinicianNoLongerOnCase($request, $count=0) {
        $search = $request->input('search.value');
        $q = DB::table('mssql_providers')->select(
            'mssql_providers.id',
            DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) AS clientName"),
            DB::raw("users.name AS Client_Coordinator"),
            DB::raw("DATE_FORMAT(mssql_providers.latest_schedule,'%m/%d/%Y') as latest_schedule"),
            DB::raw("(select notes.notes from notes where entity = 'clinician' and entity_id = mssql_providers.id order by notes.id desc limit 1) as Notes")
        )
        ->leftJoin('mssql_patients', 'mssql_providers.nurse_lost_patientid', '=', 'mssql_patients.PatientID')
        ->leftJoin('mssql_facilities', 'mssql_providers.FacilityID', '=', 'mssql_facilities.FacilityID')
        ->leftJoin('users', 'mssql_patients.ccc_id' , 'users.id');

        if (!empty($search)) {
            $q->where(function ($query) use ($search){
                $query->whereRaw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) LIKE ?", ['%'.$search.'%']);
                $query->orWhere('users.name', 'LIKE', "%{$search}%");
            });
        }

        $q->where('mssql_providers.nurse_lost_client', '1');
        $q->groupBy('mssql_providers.ProviderID');
        $state = [];
        $region = [];
        $branch = [];
        $ccc = [];
        $isMeMode = 0;

        if(isset($request['filters'])) {
            
            foreach($request['filters'] as $key => $value){
                
                if($value['name'] == 'no_longer_on_case_state') {
                    $state[] = $value['value'];
                }
                if($value['name'] == 'no_longer_on_case_region') {
                    $region[] = $value['value'];
                }
                if($value['name'] == 'no_longer_on_case_aoc_facility_id') {
                    $branch[] = $value['value'];
                }
                if($value['name'] == 'no_longer_on_case_clinical_care_manager'){
                    $ccc[] = $value['value'];
                }
                if($value['name'] == 'no_longer_on_case_default'){
                    $isMeMode = 1;
                }
            }
        }

        if(count($state)>0) {
            $q->whereIn("mssql_providers.State", $state);
        }

        if(count($region)>0) {
            $q->where(function($q) use ($region){
                foreach($region as $b) {
                    $q->orWhere("mssql_facilities.region","=",$b);
                }
            });
        }

        if(count($branch)>0) {
            $q->where(function($q) use ($branch){
                foreach($branch as $b) {
                    $q->orWhere("mssql_facilities.id", $b);
                }
            });
        }

        if(!empty($ccc) && !$isMeMode){
            $q->whereIn("mssql_patients.ccc_id", $ccc);
        } else if($isMeMode) {
            $q->where("mssql_patients.ccc_id", Auth::user()->id);
        }

        $q->where("mssql_providers.nurse_lost_client", '1');
        if($count==1) {
            return '('.$q->count().')';
        } else {
            return $q->get();
        }
    }
}
