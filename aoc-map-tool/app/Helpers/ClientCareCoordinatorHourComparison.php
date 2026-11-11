<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;

class ClientCareCoordinatorHourComparison
{
    public $buckets = [
        "schedules"=>["heading"=>"Schedules"]
    ];


    public function getBuckets(){
        return $this->buckets;
    }

    public function getBucketArray() {
        return ["schedules"=>"Schedules"];
    }
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        $dataTable = ['patient_name'=>'Client Name','FacilityName'=>'Branch','ccc_id'=>'CCC','status'=>'Status','reason'=>'Reason'];
        return view('ccc.hourComparisonDatatable',["type"=>$type,'dataTable'=>$dataTable]);
    }


    public function getClientCareCoordinatorHourComparison($type, $request = '',$showCount=0) {
        $bucket = DB::table("mssql_patients");
        $bucket->select('mssql_patients.PatientAccount','mssql_facilities.FacilityName',DB::raw("users.name as ccc_id"),
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
        DB::raw("(mssql_patients.authorization_status_current) AS status"),DB::raw("(mssql_patients.authorization_reason_current) AS reason"))->where('authorization_status_current','!=',
    'Good')->whereNotNull('authorization_status_current');
        $bucket->leftJoin("mssql_facilities",function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
		if (LD::checkFeatureFlag('client-new-changes')) {
            $bucket->where('mssql_patients.IsLatestAdmit', 1);
        }

        //Filters
        $filters = isset($request['filters']) ? $request['filters'] : [];
        if(!empty($filters)) {
            $branch = [];
            $state = [];
            $users = [];
            $isMeMode = 0;

            foreach($filters as $key => $value){

                if($value['name']=='hourComparisonState') {
                    $state[] = $value['value'];
                }
                if($value['name']=='hourComparisonBranch') {
                    $branch[] = $value['value'];
                }
                if($value['name']=='hourComparisonCccId'){
                    $users[] = $value['value'];
                }
                if($value['name']=='hourComparisonMeMode') {
                    $isMeMode = 1;
                }
            }

            if(count($state)>0) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $bucket->where(function($q) use ($facilities){
                    foreach($facilities as $facility) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(count($branch)>0) {
                $bucket->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }
            if(!empty($users) && !$isMeMode){
                $bucket->whereIn("mssql_patients.ccc_id",$users);
            } else if($isMeMode) {
                $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
            }
        }
        H::ExcelLog($bucket->toSql(),'cccSchedulesDashboard.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
    public function getSchedulesByWeekAndCompare()
    {
        $q = DB::table('mssql_schedulemaster')
			->select(
				'mssql_patients.id as id',
				'mssql_patients.AuthorizedWeeklyHours as AuthorizedWeeklyHours',
				DB::raw('DATE_ADD(CURDATE(), INTERVAL - WEEKDAY(CURDATE()) DAY) as monday'),
				DB::raw('sum(mssql_schedulemaster.PlannedHours) as PlannedHours')
			)
			->leftJoin('mssql_patients','mssql_patients.PatientID','=','mssql_schedulemaster.PatientID')
			->where('mssql_schedulemaster.ScheduleDate','>=',DB::raw('DATE_ADD(CURDATE(), INTERVAL - WEEKDAY(CURDATE()) DAY)'))
			->where('mssql_schedulemaster.ScheduleDate','<=',DB::raw('mssql_patients.authorization_to'))
			->groupBy('mssql_patients.PatientID',DB::raw('DATE_ADD(CURDATE(), INTERVAL - WEEKDAY(CURDATE()) DAY)'));
		if (LD::checkFeatureFlag('client-new-changes')) {
            $q->where('mssql_patients.IsLatestAdmit', 1);
        }
        $data = $q->get();
        $updateArrayForGood = [];
        $updateArrayForOver = [];
        $updateArrayForUnder = [];
        foreach ($data as $key => $value) {
            if($value->AuthorizedWeeklyHours == $value->PlannedHours){
                $updateArrayForGood[] = $value->id;
            }else if($value->AuthorizedWeeklyHours < $value->PlannedHours){
                $updateArrayForOver[] = $value->id;
            }else{
                $updateArrayForUnder[] = $value->id;
            }
        }
        if(!empty($updateArrayForGood)){
            DB::table('mssql_patients')->whereIn('id',$updateArrayForGood)->update(['authorization_status_current'=>'Good']);
        }
        if(!empty($updateArrayForOver)){
            DB::table('mssql_patients')->whereIn('id',$updateArrayForOver)->update(['authorization_status_current'=>'Over']);
        }
        if(!empty($updateArrayForUnder)){
            DB::table('mssql_patients')->whereIn('id',$updateArrayForUnder)->update(['authorization_status_current'=>'Under']);
        }
    }
}
