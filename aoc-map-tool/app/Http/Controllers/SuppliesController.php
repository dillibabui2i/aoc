<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\Notifications;
use App\MssqlPatients;
use App\SuppliesRequest;
use App\Helpers\SupplyRequests;
use DB;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Illuminate\Support\Carbon;
use Exception;

class SuppliesController extends Controller
{
    public $columns = [
        ['name'=>'id','label'=>'ID','visible'=>false],
        ['name'=>'client','label'=>'Client'],
        ['name'=>'cccName','label'=>'Client Care Coordinator'],
        ['name'=>'FacilityName','label'=>'Branch'],
        ['name'=>'created_by','label'=>'Requested By'],
        ['name'=>'created_at','label'=>'Date Requested'],
        ['name'=>'items','label'=>'Items'],
        ['name'=>'status','label'=>'Status'],
        ['name'=>'completed_by','label'=>'Fulfilled By'],
        ['name'=>'completed_at','label'=>'Fulfilled Date']
    ];
    public function addSuppliesRequest(Request $request) {
        return view('supplies.form',['isViewOnly'=>false,'mod'=>'Add']);
    }
    public function getSuppliesRequestDetails(Request $request) {
        $id = $request->input('id');
        $supplyDetails = [];
        $patientDetails = [];
        $mod = $request->input('mode');
        if(isset($id) && $id != ''){
            $supplyDetails = SuppliesRequest::find($id)->toArray();
            if(isset($supplyDetails['aoc_patient_id']) && $supplyDetails['aoc_patient_id'] != ''){
                $patientDetails = MssqlPatients::find($supplyDetails['aoc_patient_id']);
            }
            $mod = isset($mod) && $mod != '' ? $mod : '';
        }
        return view('supplies.form',
        ['supplyDetails'=>$supplyDetails,
        'patientDetails'=>$patientDetails,
        'mod' => $mod,
        'items'=>json_decode($supplyDetails['items'],true),
        'isViewOnly'=>$mod == 'Edit' ? false : true]);
    }
    public function changeOutcomeToSupplyRequests(Request $request) {
        try {
            $id = $request->input('id');
            $supplyDetails = [];
            $patientDetails = [];
            if(isset($id) && $id != ''){
                $supplyDetails = SuppliesRequest::find($id)->toArray();
                if(isset($supplyDetails['aoc_patient_id']) && $supplyDetails['aoc_patient_id'] != ''){
                    $patientDetails = MssqlPatients::find($supplyDetails['aoc_patient_id']);
                }
                return view(
                    'supplies.form',
                    [
                        'supplyDetails'=>$supplyDetails,
                        'patientDetails'=>$patientDetails,
                        'items'=>json_decode($supplyDetails['items'],true),
                        'isViewOnly'=>true,
                        'isOutcomeForm'=>true
                    ]
                );
            }else{
                H::ExcelLog(
                    'SuppliesController::changeOutcomeToSupplyRequests:: requested id is not set',
                    'SuppliesRequest.log'
                );
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog('SuppliesController::changeOutcomeToSupplyRequests:: '.$e->getMessage(),'SuppliesRequest.log');
            return false;
        }
    }
    public function saveOutcomeForRequestSupplies(Request $request) {
        try {
            $reqData = $request->all();
            if(isset($reqData['supplyRequestId']) && $reqData['supplyRequestId'] != ''){
                $currentDate = date(config('constant')['longdatetimeformat']);
                $updateArr = [
                    'status'=>$reqData['status'],
                    'updated_at'=>$currentDate,
                    'updated_by'=>Auth::user()->id
                ];
                if($reqData['status'] == 1){
                    $updateArr['ordered_by'] = Auth::user()->id;
                    $updateArr['ordered_at'] = $currentDate;
                }elseif($reqData['status'] == 2){
                    $clientId = SuppliesRequest::where('id', $reqData['supplyRequestId'])->value('aoc_patient_id');
                    $ccmAndcccId = MssqlPatients::select('ccm_id', 'ccc_id',
                    'Patient_FirstName', 'Patient_LastName')
                    ->where('id', $clientId)->first();
                    $patientName = $ccmAndcccId->Patient_FirstName.' '.$ccmAndcccId->Patient_LastName;
                    $completedBy = ' by '.Auth::user()->name;
                    $title = 'A supply request has been completed for '.$patientName.$completedBy;
                    $clientCareIds = [$ccmAndcccId->ccm_id, $ccmAndcccId->ccc_id];
                    foreach($clientCareIds as $ids){
                        if(!empty($ids)){
                            Notifications::addNotification(['user_id'=>$ids,'title'=>$title,
                            'type'=>'supplyRequests','related_item'=>$reqData['supplyRequestId'],'notes'=>$title]);
                        }
                    }
                    $updateArr['completed_by'] = Auth::user()->id;
                    $updateArr['completed_at'] = $currentDate;
                    $updateArr['completed'] = 1;
                }
                SuppliesRequest::where('id',$reqData['supplyRequestId'])->update($updateArr);
                H::ExcelLog(
                    'SuppliesController::saveOutcomeForRequestSupplies:: update details'.print_r($updateArr,true),
                    'SuppliesRequest.log'
                );
                return response()->json(['status'=>true]);
            }else{
                H::ExcelLog(
                    'SuppliesController::saveOutcomeForRequestSupplies:: requested id is not set',
                    'SuppliesRequest.log'
                );
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog('SuppliesController::saveOutcomeForRequestSupplies:: '.$e->getMessage(),'SuppliesRequest.log');
            return response()->json(['status'=>false]);
        }
    }

    public function loadMoreSuppliesItemForm(Request $request) {
        return view('supplies._itemsForm',['count'=>$request->input('count')]);
    }
    public function save(Request $request) {
        try {
            $reqData = $request->all();
            $items = [];
            $text = "A supply request was";
            $note = null;
            $date = date('Y-m-d H:i:s');
            if(isset($reqData['id']) && $reqData['id'] != ''){
                if(isset($reqData['count']) && $reqData['count'] > 0){
                    for ($i=0; $i <= $reqData['count']; $i++) {
                        if(isset($reqData['item'.$i])){
                            $items[] = [
                                'item'=>$reqData['item'.$i],
                                'description'=>$reqData['description'.$i],
                                'qty'=>$reqData['qty'.$i]
                            ];
                        }
                    }
                }
                $checkItem = SupplyRequests::hasDirectNestedArray($items);
                if($checkItem == false){
                    return response()->json(['status'=>false]);
                }
                $updateArray = [
                    'items'=>json_encode($items),
                    'updated_at'=>$date,
                    'updated_by'=>Auth::user()->id,
                ];
                $note = $reqData['notes'];
                if(!empty($reqData['notes'])){
                    $oldNotes = SuppliesRequest::where('id', $reqData['id'])->value('notes');
                    if($reqData['notes'] != $oldNotes){
                        $updateArray['notes'] = $reqData['notes'];
                        $updateArray['notes_created_by'] = Auth::user()->id;
                        $updateArray['notes_created_at'] = $date;
                    }
                }else{
                    $updateArray['notes'] = $reqData['notes'];
                    $updateArray['notes_created_by'] = Auth::user()->id;
                    $updateArray['notes_created_at'] = $date;
                }
                DB::table('supply_requests')->where('id',$reqData['id'])->update($updateArray);
                if(!empty($items)){
                    $string = SupplyRequests::formateIteamlistForNotes($items, 'edit', $text, $note);
                    $notes = ['entity' => 'client','type'=>'supplies', 'entity_id' => $reqData['aoc_patient_id'],
                    'notes' => $string,'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notes);
                }
            }else{
                if(isset($reqData['count']) && $reqData['count'] > 0){
                    for ($i=0; $i <= $reqData['count']; $i++) {
                        if(isset($reqData['item'.$i])){
                            $items[] = [
                                'item'=>$reqData['item'.$i],
                                'description'=>$reqData['description'.$i],
                                'qty'=>$reqData['qty'.$i]
                            ];
                        }
                    }
                }
                $checkItem = SupplyRequests::hasDirectNestedArray($items);
                if($checkItem == false){
                    return response()->json(['status'=>false]);
                }
                $insertArray = [
                    'aoc_patient_id'=>$reqData['aoc_patient_id'],
                    'items'=>json_encode($items),
                    'created_at'=>$date,
                    'created_by'=>Auth::user()->id,
                    'updated_at'=>$date,
                    'updated_by'=>Auth::user()->id,
                ];
                if(!empty($reqData['notes'])){
                    $insertArray['notes'] = $reqData['notes'];
                    $insertArray['notes_created_by'] = Auth::user()->id;
                    $insertArray['notes_created_at'] = $date;
                    $note = $reqData['notes'];
                }
                SuppliesRequest::insertGetId($insertArray);
                if(!empty($items)){
                    $string = SupplyRequests::formateIteamlistForNotes($items, 'add', $text, $note);
                    $notes = ['entity' => 'client','type'=>'supplies', 'entity_id' => $reqData['aoc_patient_id'],
                    'notes' => $string,'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notes);
                }
            }

            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Save Supplies Request:'.$e->getMessage(),'SuppliesRequest.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function getSupplyRequestsDashboard(Request $request) {
        if ($request->ajax()) {
            $data = H::getHelper("SupplyRequests")->getSupplyRequestsByType($request->input("type"), $request);
            return Datatables::of($data)
            ->addColumn('Action', function ($row) use ($request){
                $btn = '';
                if($request->input("type") == 'supply_requests_new'){
                    if($row->statusNum != '2'){
                        $btn .= '<a href="javascript:void(0)"
                        onclick="changeOutcomeToSupplyRequests('.$row->id.')" >
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                        </a> | ';
                    }
                    if($row->statusNum == '0'){
                        $btn .= '<a href="javascript:void(0)"
                        onclick="editSupplyRequests('.$row->id.')" >
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                        </a> | ';
                    }
                    if($row->acknowledged_by != 0){
                        $btn .= '<i class="fa fa-check-square-o" aria-hidden="true"
                        title = "Acknowledged By '.H::getUserNameById($row->acknowledged_by).'"
                        style="color: green;"></i> |';
                    }else{
                        $btn .= '<a href="javascript:void(0)" onclick="acknowledgeRequest('.$row->id.')" >
                        <i class="fa fa-check-square-o" aria-hidden="true" title = "Acknowledge Receipt"></i></a> |';
                    }
                }

                $btn .= '<a href="javascript:void(0)" onclick="getSupplyRequestsDetails('.$row->id.')" >
                <i class="fa fa-eye" aria-hidden="true"></i></a>';

                if($request->input("type") == 'oc_supply_requests'){
                    $btn .= '| <a href="javascript:void(0)" onclick="processSupplyRequests('.$row->id.')" >
                    <i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                }

                if($row->client_id != null){
                    $btn .= '| <a href="javascript:void(0)" onclick="getClientDetails('.$row->client_id.')" >
                    <i class="fa fa-user" aria-hidden="true"></i></a>';
                }
                
                if($row->statusNum == '0'){
                    $btn .= '| <a href="javascript:void(0)"
                    onclick="supplyReqDelete('.$row->id.', \'bucket\')" >
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </a>';
                }

                return $btn;
            })
            ->editColumn('created_at', function ($row) {
                return [
                   'display' => e(H::getFormatedDateTime($row->created_at)),
                   'timestamp' => strtotime($row->created_at)
                ];
             })
            ->editColumn('items', function ($request) {
                $items = json_decode($request->items,true);
                $itemsStr = '<div>';
                foreach($items as $k => $v){
                    //$itemsStr .= '<div><strong>'.$v['item'].'</strong>('.$v['qty'].') <p class="pl-2">'.$v['description'].'</p></div>';
                    $itemsStr .= '<div>'.$v['item'].' ('.$v['qty'].')</div>';
                }
                return $itemsStr;
            })
            ->editColumn('notes', function ($row){
                if(!empty($row->notes)){
                    $userName = H::getUserNameById($row->notes_created_by);
                    $note = SupplyRequests::notesFormate($userName, $row->notes, $row->notes_created_at);
                    $str = '';
                    if (strlen($note) > 35) {
                        $str = substr($note, 0, 35) . '...';
                    }else{
                        $str = $note;
                    }
                    return "<span data-toggle='tooltip' title='" .
                            mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "'>" .
                            mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                            '</span>';
                }
                return "-";
            })
            ->addIndexColumn()->escapeColumns('Action')->make(true);
        }
    }
    public function reloadCountSupplyRequests(Request $request) {
        return json_encode(["counter"=>H::getHelper('SupplyRequests')->getSupplyRequestsByType($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }

    private function getFilterData($request,$type='list'){
        if($type == 'list'){
            $columns = $this->columns;
            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');
        }

        $q = DB::table('supply_requests')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'supply_requests.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->leftJoin('users as userForCreatedBy', 'userForCreatedBy.id', '=', 'supply_requests.created_by')
            ->leftJoin('users as userForCompletedBy', 'userForCompletedBy.id', '=', 'supply_requests.completed_by')
            ->leftJoin('users as ccc', 'ccc.id', '=', 'mssql_patients.ccc_id')
            ->select(
                DB::raw(
                    "DATE_FORMAT(
                        supply_requests.completed_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "'
                    ) as completed_at"
                ),
                'supply_requests.id as id','userForCreatedBy.name as created_by',
                'supply_requests.status as statusNum',
                'ccc.name as cccName','userForCompletedBy.name as completed_by',
                'supply_requests.items as items',
                DB::raw(
                    "DATE_FORMAT(
                        supply_requests.created_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "'
                    ) as created_at"
                ),
                DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client"),
                'mssql_facilities.FacilityName as FacilityName',
                DB::raw("CASE WHEN
                    supply_requests.status = 1 THEN 'Supplies Ordered'
                    WHEN supply_requests.status = 2 THEN 'Supplies Delivered'
                    ELSE 'New' END as status"
                ),
                'mssql_patients.id as client_id'
            );
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->where(function ($query) use ($search){
                $query->where('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                $query->orWhere('supply_requests.items', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }
        $filters = [];
        $isIncludedCompleted = false;
        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
            foreach($filters as $fkey => $fval) {
                if($fkey=='state') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_facilities.State",'=',$val);
                        }
                    });
                }elseif($fkey=='aoc_facility_id') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_patients.aoc_facility_id",'=',$val);
                        }
                    });
                }elseif($fkey=='created_by') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("supply_requests.created_by",'=',$val);
                        }
                    });
                }elseif($fkey=='meMode') {
                    $q->where(function($q) {
                        $q->where("mssql_patients.ccc_id",'=', Auth::user()->id);
                    });
                }elseif($fkey=='region') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_facilities.region",'=',$val);
                        }
                    });
                }elseif($fkey=='Payer') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_patients.Payer",'=',$val);
                        }
                    });
                }elseif($fkey=='payer_group') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_patients.payer_group",'=',$val);
                        }
                    });
                }elseif($fkey=='ClientCareCoordinator') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("mssql_patients.ccc_id",'=',$val);
                        }
                    });
                }elseif($fkey=='include_completed'){
                    $isIncludedCompleted = true;
                }
            }
        }
        if(!$isIncludedCompleted){
            $q->where('supply_requests.status','!=', '2');
        }
        $totalFiltered = $q->count();
        H::ExcelLog('List: ' . $q->toSql(), 'SuppliesRequest.log');
        if($type == 'list'){
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        }else{
            return $q->get();
        }
        return ['data'=>$data,'totalData'=>intval($totalData),'totalFiltered'=>intval($totalFiltered)];
    }
    public function list(Request $request){
        $columns = $this->columns;
        if ($request->ajax()) {
            $data = $this->getFilterData($request);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($data['totalData']),
                'recordsFiltered' => intval($data['totalFiltered']),
                'data'            => $data['data']
            ]);
        }
        $states = DB::table('mssql_patients')->select(DB::raw('DISTINCT Patient_State as state'))->where('Patient_State','!=','')->get()->toArray();
        return view('supplies.grid',['columns' => $columns,'states' => array_column($states,'state')]);
    }
    public function exportSupplies(Request $request){
        try{
            $dataTableFields = array_combine(
                array_column($this->columns, 'name'), array_column($this->columns, 'label')
            );
            $data = $this->getFilterData($request,'export');

            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=supplies.csv",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $callback = function () use ($data, $dataTableFields) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($dataTableFields));

                foreach ($data as $tableData) {
                    $temp = [];
                    foreach ($dataTableFields as $key => $value) {
                        if($key == 'items'){
                            $items = json_decode($tableData->{$key},true);
                            $itemStr = '';
                            foreach ($items as $item) {
                                foreach ($item as $key => $value) {
                                    $itemStr .= $key.': '.$value."\n";
                                }
                                $itemStr .= "\n\n";
                            }
                            $temp[] = $itemStr;
                        }else{
                            $temp[] = $tableData->{$key};
                        }

                    }
                    fputcsv($file, $temp);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            H::ExcelLog('SuppliesController::exportSupplies:'.$e->getMessage(),'SuppliesRequest.log');
        }
    }

    public function fulfillSupplyRequests(Request $request){
        $id = $request->input('id');
        $currentDate = date('Y-m-d H:i:s');
        if(isset($id) && $id != ''){
            if(DB::table('supply_requests')->where('id',$id)->update([
                'completed'=>1,
                'completed_at'=>$currentDate,
                'completed_by'=>Auth::user()->id
            ])){
                $supplyDetails = SuppliesRequest::find($id)->toArray();
                if(isset($supplyDetails['aoc_patient_id']) && $supplyDetails['aoc_patient_id'] != ''){
                    $patientDetails = MssqlPatients::find($supplyDetails['aoc_patient_id']);
                    //sending notification to the CCC
                    if(isset($patientDetails->ccc_id) && $patientDetails->ccc_id != '' && $patientDetails->ccc_id != 0){
                        $title = 'The supply request for '.$patientDetails->Patient_FirstName.' '.$patientDetails->Patient_LastName.' has been fulfilled by '.Auth::user()->name.' on '.H::getFormatedDateTime($currentDate);
                        Notifications::addNotification(['user_id'=>$patientDetails->ccc_id,'title'=>$title,'type'=>'supplyRequests','related_item'=>$id,'notes'=>$title]);
                    }
                    //Adding the notes
                    $items = json_decode($supplyDetails['items'],true);
                    $itemsStr = '<br><div>';
                    foreach($items as $k => $v){
                        $itemsStr .= '<div>'.$v['item'].' ('.$v['qty'].')<p class="pl-2">'.$v['description'].'</p></div>';
                    }
                    $itemsStr .= '<div>';
                    $notes = ['entity' => 'client','type'=>'supplies', 'entity_id' => $supplyDetails['aoc_patient_id'], 'notes' =>'A supply request for '.$patientDetails->Patient_FirstName.' '.$patientDetails->Patient_LastName.' has been fulfilled by '.Auth::user()->name.$itemsStr, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notes);
                }
                return response()->json(['status'=>true]);
            }else{
                return response()->json(['status'=>false]);
            }
        }else{
            return response()->json(['status'=>false]);
        }
    }

    public function acknowledgeSupplyRequests(Request $request){
        $id = $request->id;
        if(!empty($id)){
            $loginUser = H::getLoggedInUser();
            $data = [
                'acknowledged' => 1,
                'acknowledged_at' => now(),
                'acknowledged_by' => $loginUser['id']
            ];
            SuppliesRequest::where('id', $id)->update($data);
            return response()->json(['status'=>true]);
        }
    }
    public function validateSupplyRequestsForSelectedClient(Request $request){
        try {
            $clientId = $request->input('clientId');
            $supplyDetails = SuppliesRequest::where('aoc_patient_id',$clientId)
            ->where('status',0)
            ->where('completed',0)
            ->first();
            if(!empty($supplyDetails)){
                return response()->json(['status'=>true,'supplyId'=>$supplyDetails->id]);
            }else{
                return response()->json(['status'=>true,'supplyId'=>'']);
            }
        } catch (\Exception $e) {
            H::ExcelLog('validateSupplyRequestsForSelectedClient:'.$e->getMessage(),'SuppliesRequest.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function checkCompleted(Request $request){
        try {
            $clientId = $request->input('clientId');
            $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();
            $now = Carbon::now()->endOfDay();
            $supplyDetails = SuppliesRequest::where('aoc_patient_id',$clientId)
            ->where('completed', 1)
            ->whereBetween('completed_at', [$thirtyDaysAgo, $now])
            ->first();
            if(!empty($supplyDetails)){
                return response()->json(['status'=>true]);
            }else{
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function delete(Request $request){
        try{
            $id = $request->id;
            DB::table('supply_requests')->where('id', $id)->delete();
            return response()->json(['success' => 1]);
        }catch(Exception $e){
            return response()->json(['success' => 0, 'message' => 'Something went wrong']);
        }
    }
}
