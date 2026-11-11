<?php
namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\H;

class SupplyRequests
{
    public $buckets = [];


    public function getBuckets(){
        return $this->buckets;
    }


    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        switch($type) {
            case 'supply_requests_new':
                $dataTable = ['client'=>'Client',
                'Client_Coordinator'=>"Client Care Coordinator",
                'FacilityName'=>'Branch',
                'created_at'=>'Date Requested',
                'items'=>'Items',
                'status'=>'Status',
                'notes'=>'Supply Request Notes'
                ];
                return view('supplies.datatable_widget',["type"=>$type,'dataTable'=>$dataTable]);
            case 'supply_requests':
                $dataTable = ['client'=>'Client','Client_Coordinator'=>"Client Care Coordinator",'FacilityName'=>'Branch','created_at'=>'Date Requested','items'=>'Items'];
                break;
            case 'oc_supply_requests':
                $dataTable = ['client'=>'Client','Client_Coordinator'=>"Client Care Coordinator",'FacilityName'=>'Branch','created_by'=>'Requested By','created_at'=>'Date Requested','items'=>'Items'];
                break;
            default:
            $dataTable = ['client'=>'Client','Client_Coordinator'=>"Client Care Coordinator",'FacilityName'=>'Branch','created_at'=>'Date Requested','items'=>'Items'];
                break;
        }
        return view('supplies.datatable',["type"=>$type,'dataTable'=>$dataTable]);
    }
    public function getSupplyRequestsByType($type, $request = '',$showCount=0) {
        $search = $request->input('search.value');
        $bucket = DB::table("supply_requests");
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","supply_requests.aoc_patient_id");
        });
        $bucket->leftJoin("users",function($query){
            $query->on("users.id","=","mssql_patients.ccc_id");
        });
        $bucket->leftJoin('users as userForCreatedBy', 'userForCreatedBy.id', '=', 'supply_requests.created_by');
        $bucket->leftJoin("mssql_facilities", function($query){
            $query->on("mssql_facilities.id","=","mssql_patients.aoc_facility_id");
        });
        $bucket->select('supply_requests.id as id','supply_requests.status as statusNum','userForCreatedBy.name as created_by','supply_requests.items as items',DB::raw("users.name AS Client_Coordinator"),"supply_requests.created_at",DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client"),'mssql_facilities.FacilityName as FacilityName',DB::raw("CASE WHEN
        supply_requests.status = 1 THEN 'Supplies Ordered'
        WHEN supply_requests.status = 2 THEN 'Supplies Delivered'
        ELSE 'New' END as status"), 'supply_requests.acknowledged_by',
        'supply_requests.notes',
        'supply_requests.notes_created_by',
        'supply_requests.notes_created_at',
        'mssql_patients.id as client_id'
        );

        //Getting the branch of the current user
        // $userBranch = H::getHelper('ConstantHelper')->getUserNameById(Auth::user()->id,'branchid');
        // if(!empty($userBranch)){
        //     $bucket->whereIn('mssql_facilities.branchid',explode(',',$userBranch));
        // }

        $bucket->where('supply_requests.status','!=',2);
        switch ($type) {
            case 'oc_supply_requests':
                $bucket->where('supply_requests.completed',0);
                break;
            case 'supply_requests':
                break;
            default:
                break;
        }

        if (!empty($search)) {
            $bucket->where(function ($query) use ($search){
                $query->where('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                $query->orWhere('supply_requests.items', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
            });
        }
        if (LD::checkFeatureFlag('supply-requests-add-filters')) {
            $filters = [];
            if(isset($request['filters'])) {
                foreach($request['filters'] as $fl) {
                    if(!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }
                foreach($filters as $fkey => $fval) {
                    if($fkey=='ocsupplystate' || $fkey=='ccsupplystate') {
                        $bucket->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_facilities.State",'=',$val);
                            }
                        });
                    } elseif($fkey=='ocsupplyregion' || $fkey=='ccsupplyregion') {
                        $bucket->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_facilities.region",'=',$val);
                            }
                        });

                    }elseif($fkey=='ocsupplybranch' || $fkey=='ccsupplybranch') {
                        $bucket->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                            }
                        });
                    }elseif($fkey=='ocsupplycreatedby' || $fkey=='ccsupplycreatedby') {
                        $bucket->where(function($query) use ($fval) {
                            foreach($fval as $val) {
                                $query->orWhere("supply_requests.created_by",'=',$val);
                            }
                        });
                    }elseif($fkey=='fromRequestedDate') {
                        $bucket->whereDate('supply_requests.created_at','>=',date('Y-m-d H:i:s',strtotime($fval[0])));
                    }elseif($fkey=='toRequestedDate') {
                        $bucket->whereDate('supply_requests.created_at','<=',date('Y-m-d H:i:s',strtotime($fval[0])));
                    }
                }
            }
            if($showCount) {
                return '('.$bucket->count().')';
            }
            H::ExcelLog('List: ' . $bucket->toSql(), 'getSupplyRequestsByType.log');
            if($type == 'oc_supply_requests') {
                return $bucket->get();
            }
            return $bucket->get();
        }else {
            if($showCount) {
                return '('.$bucket->count().')';
            }
            return $bucket;
        }
    }

    public static function notesFormate($username, $notes, $date){
        $date = date('m/d', strtotime($date));
        return ''.$date.' '.$username.' commented:'.$notes;
    }

    /**
     * Format item list for notes.
     *
     * @param array $iteamArray Array of items
     * @param string $mode Mode ('edit' or 'add')
     * @param string $str String to prepend
     * @param string $notes String to notes
     * @return string Formatted string
     */
    public static function formateIteamlistForNotes(array $iteamArray, string $mode,
    string $str, string $notes = null): string
    {
        $username = Auth::user()->name;
        $dateTime = H::getFormatedDateTime(Carbon::now()->toDateTimeString());
        $mode = $mode == 'add' ? 'made by' : 'edited by';
        $formattedString = "".$str." ".$mode." ".$username." ".$dateTime."<br>";
        if(!empty($notes)){
            $formattedString .= "".$notes."<br>";
        }
        $formattedString .= "<br><div class='p-2 w-150'>";
        foreach ($iteamArray as $iteam) {
            $formattedString .= "" . $iteam['item'] . " " .
                $iteam['description'] . " - " . $iteam['qty'] . "<br>";
        }
        $formattedString .= "</div>";
        return $formattedString;
    }

    public static function hasDirectNestedArray($array) {
        foreach ($array as $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    foreach ($value as $innerValue) {
                        if (is_array($innerValue) && !empty($innerValue)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }
}
