<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\OfficeEvaluation;
use App\Helpers\H;
use App\MssqlProviders;
use App\Helpers\LD;
use App\Helpers\Notifications;
use DataTables;
use App\RightSignatureDocuments;

class OfficeEvaluationController extends Controller
{
    public function getOfficeEvaluationDocuments(Request $request)
    {
        $user = H::getLoggedInUser();
        $userId = $user['id'];

        if ($request->ajax()) {
            
            $data = H::getHelper("OfficeEvaluation")->getUnSentDocuments($request->input("type"), $request, 0);
            if(LD::checkFeatureFlag('add-new-field-furthest-step-in-office-evaluations')) {
                return Datatables::of($data)
                ->addColumn('is_supervisor', function ($row) use ($userId) {
                    return $row->supervisor_id == $userId;
                })
                ->addColumn('is_manager', function ($row) use ($userId) {
                    return $row->manager_id == $userId;
                })
                ->editColumn('manager', function ($row) {
                    if($row->supervisor_id=='156') {
                        return '-';
                    } else {
                        return $row->manager;
                    }
                })
                ->editColumn('status2', function ($row) {
                    switch ($row->status) {
                        case 'Not Started':
                            return '1/6';
                        case 'Pending Supervisor Submission':
                            return '2/6';
                        case 'Needs Changes':
                            return '2/6';
                        case 'Pending Manager Approval':
                            return '3/6';
                        case 'Pending HR Approval':
                            return '4/6';
                        case 'Pending Employee Review':
                            return '5/6';
                        case 'Employee Signed':
                            return '6/6';
                        default:
                            return '-';
                    }
                })    
                ->addIndexColumn()->escapeColumns('Action')->make(true);
            } else {
                return Datatables::of($data)
                ->addColumn('is_supervisor', function ($row) use ($userId) {
                    return $row->supervisor_id == $userId;
                })
                ->addColumn('is_manager', function ($row) use ($userId) {
                    return $row->manager_id == $userId;
                })
                ->editColumn('manager', function ($row) {
                    if($row->supervisor_id=='156') {
                        return '-';
                    } else {
                        return $row->manager;
                    }
                })
                ->addIndexColumn()->escapeColumns('Action')->make(true);
            }
            
        }
    }

    public function reloadOfficeEvaluationDocuments(Request $request)
    {
        return json_encode(
            [
                "counter" => H::getHelper("OfficeEvaluation")->getUnSentDocuments($request->input("type"), $request, 1),
                "type" => $request->input("type")
            ]
        );
    }

    private function getOfficeEvaluation($request, $columns, $requestFrom)
    {
        $filters = [];
        $filtersInput = $request->input('filters');
        if ($filtersInput) {
            foreach ($filtersInput as $value) {
                if ($value['value'] != '') {
                    $filters[$value['name']][] = $value['value'];
                }
            }
        }
        $search = $request->input('search.value');
        $limit = $start = $order = $dir = '';
        if ($requestFrom == 'list') {
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');
        }

        $q = OfficeEvaluation::select(
            'office_evaluations.id',
            'office_evaluations.aoc_provider_id',
            DB::Raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) as EmployeeName"),
            'mssql_facilities.BranchName',
            'office_evaluations.updated_at as fupdated_at',
            DB::Raw("(SELECT name from users where users.id = u.recruiting_manager) as supervisor"),
            DB::Raw("(SELECT name from users where users.id = u.manager_id) as manager"),
            'office_evaluations.status',
            DB::Raw('u.recruiting_manager as supervisor_id'),
            DB::Raw("IFNULL(self_evaluations.id,'') as selfid")
        )
           
            ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')
            ->leftJoin('users as u' , 'mssql_providers.AOC_Email', '=', 'u.email')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->leftJoin('self_evaluations', function($join)
			{
				$join->on('self_evaluations.aoc_provider_id', '=', 'office_evaluations.aoc_provider_id')
				->whereRaw("year(self_evaluations.created_at) = year(office_evaluations.created_at)")
				->whereRaw("(self_evaluations.created_at) <> (self_evaluations.updated_at)");
			});
        $q->addSelect(
            DB::raw("(select document_filename from aoc_documents where aoc_documents.evaluation_id = office_evaluations.id and aoc_documents.entity_type='provider' and aoc_documents.document_type='office_eval' order by id desc limit 1 ) as document_filename")
        );
        if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
            $q->addSelect(DB::raw('YEAR(office_evaluations.created_at) as evaluation_year'));
        }
        $isMeMode = 0;
        if (!empty($filters)) {
            if (isset($filters['state'])) {
                $q->whereIn('mssql_facilities.State', $filters['state']);
            }
            if (isset($filters['region'])) {
                $q->whereIn('mssql_facilities.region', $filters['region']);
            }
            if (isset($filters['branch'])) {
                $q->whereIn('mssql_facilities.id', $filters['branch']);
            }
            if (isset($filters['status'])) {
                $q->whereIn('office_evaluations.status', $filters['status']);
            }
            if (isset($filters['supervisor'])) {
                $q->whereIn('u.recruiting_manager',$filters['supervisor']);
            }
            if (isset($filters['manager'])) {
                $q->whereIn('u.manager_id',$filters['manager']);
            }
            if (isset($filters['default'])) {
                $isMeMode = 1;
            }
            if(!H::canPermissions(['officeevaluation.list'])) {
                $isMeMode = 1;
            }
            if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
                if (isset($filters['evaluation_year'])) {
                    $q->whereIn(DB::raw('YEAR(office_evaluations.created_at)'), $filters['evaluation_year']);
                }
            }
        }

        $user = H::getLoggedInUser();
        $userId = $user['id'];
        //don't show Jessica's evaluations unless it's Jessica
        if($userId!=156){
            $q->where('u.recruiting_manager','<>','156');
        }
        if ($isMeMode && !H::getHelper("OfficeEvaluation")->isEvaluationHR($userId)) {
            $userArray[] = $userId;
            $q->where(function ($q) use ($userArray) {
                $q->whereRaw("(SELECT id from users where users.id IN (SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) = ?", [$userArray]);
                if(H::getHelper("OfficeEvaluation")->isManagerIgnored($userArray[0])) {
                    $q->orWhereRaw("(SELECT id from users where users.id IN (SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) = ?", [$userArray]);
                }
            });
        }
        $q->where('mssql_providers.is_deleted',0);
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function ($q) use ($search) {
                $searchParam = "%{$search}%";
                $q->whereRaw(DB::Raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) LIKE ?"), [$searchParam])
                    ->orWhere('mssql_facilities.BranchName', 'LIKE', $searchParam)
                    ->orwhereRaw(DB::Raw("(SELECT name from users where users.id = u.recruiting_manager) LIKE ?"), [$searchParam])
                    ->orwhereRaw(DB::Raw("(SELECT name from users where users.id = u.manager_id) LIKE ?"), [$searchParam])
                    ->orWhere(DB::raw('DATE_FORMAT(office_evaluations.created_at, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', $searchParam);
            });

            $totalFiltered = $q->count();
        }

        if ($requestFrom == 'list') {
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        } else {
            $data = $q->get()->toArray();
        }

        return [$data, $totalData, $totalFiltered];
    }

    private function filterArrayByKeys(array $input, array $column_keys)
    {
        $result = $finalResult = [];
        $result[0] = (object) $input;
        $finalData = (array) $this->dataProcess((object)$result);
        foreach($column_keys as $value) {
            $finalResult[$value] = $finalData[0]->{$value};
        }
        return $finalResult;
    }

    public function list(Request $request)
    {
        if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
            $columns = [
                ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
                ['name' => 'EmployeeName', 'label' => 'Employee Name', 'width' => '15%'],
                ['name' => 'BranchName', 'label' => 'Branch', 'width' => '15%'],
                ['name' => 'supervisor', 'label' => 'Supervisor Name', 'width' => '20%'],
                ['name' => 'manager', 'label' => 'Manager', 'width' => '20%'],
                ['name' => 'evaluation_year', 'label' => 'Evaluation Year', 'width' => '10%'],
                ['name' => 'status', 'label' => 'Status', 'width' => '15%'],
                ['name' => 'fupdated_at', 'label' => 'Last Updated', 'width' => '15%'],
            ];
        }else{
            $columns = [
                ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
                ['name' => 'EmployeeName', 'label' => 'Employee Name', 'width' => '15%'],
                ['name' => 'BranchName', 'label' => 'Branch', 'width' => '15%'],
                ['name' => 'supervisor', 'label' => 'Supervisor Name', 'width' => '20%'],
                ['name' => 'manager', 'label' => 'Manager', 'width' => '20%'],
                ['name' => 'status', 'label' => 'Status', 'width' => '15%'],
                ['name' => 'fupdated_at', 'label' => 'Last Updated', 'width' => '15%'],
            ];
        }
        if ($request->ajax()) {

            list($data, $totalData, $totalFiltered) = $this->getOfficeEvaluation($request, $columns, 'list');

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->dataProcess($data),
            ]);
        }
        return view('officeEvaluation.grid', ['columns' => $columns]);
    }

    private function dataProcess($data)
    {
        $user = H::getLoggedInUser();
        $userId = $user['id'];
        foreach ($data as $value) {
            if($value->supervisor_id==$userId) {
                $value->is_supervisor = 1;
            } else {
                $value->is_supervisor = 0;
            }
            $value->selfevaluation = '';
            if (!empty($value->selfid)) {
                $value->selfevaluation = 'Yes';
            } else {
                $value->selfevaluation = 'No';
            }
            $value->fupdated_at = H::getFormatedDate($value->fupdated_at);
        }
        return $data;
    }

    public function edit(Request $request)
    {
        $selfEvaluation = [];
       
        $officeEvaluationData = OfficeEvaluation::find($request->input('id'));
        $evaluationnotes = DB::table("notes")->select("notes.*", "users.name")
            ->leftJoin("users", "users.id", "=", "notes.created_by")
            ->where("entity_id", $request->input("id"))->where("entity", "office_evaluation")->orderBy("created_at", "desc")->get();

        $clinicianData = DB::table("mssql_providers")
        ->select("mssql_providers.*", DB::Raw("(SELECT name from users where users.id IN (
            
                SELECT manager_id from users where mssql_providers.AOC_Email = users.email and status = 1)
            ) as manager"),
        DB::Raw("(SELECT id from users where users.id IN (
           
                SELECT manager_id from users where mssql_providers.AOC_Email = users.email and status = 1)
            ) as manager_id"),
        DB::Raw("(SELECT name from users where users.id IN (
                SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email and status = 1
            )) as supervisor_name"),
        DB::Raw("(SELECT id from users where users.id IN (
            
                SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email and status = 1
            )) as supervisor_id"),
			
		DB::Raw("(SELECT title from users where mssql_providers.AOC_Email = users.email and status = 1) as user_title")
       
        )
        ->where('mssql_providers.id', $officeEvaluationData->aoc_provider_id)->where('mssql_providers.is_deleted',0)->first();
        
        $selfEvaluation = DB::table("self_evaluations")
					->select("*")
					->where("aoc_provider_id", $officeEvaluationData->aoc_provider_id)
					->whereRaw('year(self_evaluations.created_at) = year(\''.$officeEvaluationData->created_at.'\')')
					->first();
        $statuses = [];
        if(LD::checkFeatureFlag('add-visual-tracker-to-top-of-Office-evaluation')){
            if($request->input("mode") == 'view'){
                $currentStatus = $officeEvaluationData->status;

                if(LD::checkFeatureFlag('officeevaluation-remove-hr-steps')) {
                    $allStatuses = [
                        'Not Started',
                        'Pending Supervisor Submission',
                        'Pending Manager Approval',
                        'Pending Employee Review',
                        'Employee Signed',
                    ];
                } else {
                    $allStatuses = [
                        'Not Started',
                        'Pending Supervisor Submission',
                        'Pending Manager Approval',
                        'Pending HR Approval',
                        'Pending Employee Review',
                        'Employee Signed',
                    ];
                }
               

                $statusFound = false;

                foreach ($allStatuses as $status) {
                    if($currentStatus == 'Needs Changes' || $currentStatus == 'Pending Supervisor Submission'){
                        if($status == 'Pending Supervisor Submission'){
                            $status = 'Pending Supervisor Approval';
                            $statuses[] = ['status' => $status, 'class' => 'red'];
                            $statusFound = true;
                        }elseif(!$statusFound){
                            $status = ($status == 'Pending Supervisor Submission') ? 'Pending Supervisor Approval' : $status;
                            $statuses[] = ['status' => $status, 'class' => 'light-blue'];
                        }else{
                            $status = ($status == 'Pending Supervisor Submission') ? 'Pending Supervisor Approval' : $status;
                            $statuses[] = ['status' => $status, 'class' => 'dark-blue'];
                        }
                    } elseif ($status === $currentStatus) {
                        $status = ($status == 'Pending Supervisor Submission') ? 'Pending Supervisor Approval' : $status;
                        $statuses[] = ['status' => $status, 'class' => 'red'];
                        $statusFound = true;
                    } elseif (!$statusFound) {
                        $status = ($status == 'Pending Supervisor Submission') ? 'Pending Supervisor Approval' : $status;
                        $statuses[] = ['status' => $status, 'class' => 'light-blue'];
                    } else {
                        $status = ($status == 'Pending Supervisor Submission') ? 'Pending Supervisor Approval' : $status;
                        $statuses[] = ['status' => $status, 'class' => 'dark-blue'];
                    }
                }

            }
        }
        return view('officeEvaluation.form', ["evaluationnotes" => $evaluationnotes, "mode" => $request->input("mode"), 'officeEvaluationData' => $officeEvaluationData, 'clinicianData' => $clinicianData,'selfevaluation'=>$selfEvaluation, 'statuses' => $statuses]);
    }

    public function getNotesForm(Request $request)
    {

        return view('officeEvaluation.evaluationnotes', ["data" => $request->all()]);
    }

    public function saveNotesForm(Request $request)
    {
        $reqData = $request->all();
        $user = H::getLoggedInUser();
        $userId = $user['id'];
        H::ExcelLog("new Request from " . $user['name'],'officeEvaluationUpdate.log');
        H::ExcelLog(print_r($reqData,true),'officeEvaluationUpdate.log');
        if (!empty($reqData['evaluation_id'])) {
            $noteData = [];
            $noteData['entity'] = 'office_evaluation';
            $noteData['entity_id'] = $reqData['evaluation_id'];

            $noteData['notes'] =$reqData['notes'];

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            OfficeEvaluation::where("id",$request->input('evaluation_id'))->update(["status"=>'Needs Changes',"updated_by"=>$userId]);

            $officeEV =OfficeEvaluation::where("id",$request->input('evaluation_id'))->first();
            $providerData = DB::table("mssql_providers")->select("mssql_providers.*",DB::Raw("(SELECT CONCAT(id, '|' , name) from users where users.id IN (SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) as supervisor"))->where('mssql_providers.id',$officeEV->aoc_provider_id)->where('mssql_providers.is_deleted',0)->first();
            $employeeName = $providerData->FirstName.' '.$providerData->LastName;
            $supervisor = explode("|",$providerData->supervisor);
           
            if(isset($supervisor[0]) && !empty($supervisor[0])) {
                $notification['user_id'] = $supervisor[0];
                $notification['title'] = 'Action Required – Performance Evaluation Changes';
                $notification['type'] = 'office_eval_edit';
                $notification['related_item'] = $request->input('evaluation_id');
                
                $notification['notes'] = $supervisor[1].', please review the comments made by '.$user['name'].' on '.$employeeName.'’s Annual Performance Evaluation as it has been sent back to you for necessary changes. Please make the required revisions as soon as possible.';
               
               Notifications::addNotification($notification); 
            }

            return response()->json(["status" => true]);
        }
        return response()->json(["status" => false]);
    }

    public function getApprovalForm(Request $request)
    {
        return view('officeEvaluation.approveevaluation', ["data" => $request->all()]);
    }

    public function processOfficeEvals(Request $request) {
        $processRecords = DB::table("aoc_documents")->where("document_type","office_eval")->where("status","pending")->whereDate("created_at","<",date("Y-m-d"))->get();
       
        foreach($processRecords as $record) {
                $officeEV = OfficeEvaluation::where('id', $record->evaluation_id)->first();
                $pdfData = [];
                $pdfData['item1_score'] = $officeEV->item1_score;
                $pdfData['item2_score'] = $officeEV->item2_score;
                $pdfData['item3_score'] = $officeEV->item3_score;
                $pdfData['item4_score'] = $officeEV->item4_score;
                $pdfData['item5_score'] = $officeEV->item5_score;
                $pdfData['item6_score'] = $officeEV->item6_score;
                $pdfData['item7_score'] = $officeEV->item7_score;
                $pdfData['item8_score'] = $officeEV->item8_score;
                $pdfData['item9_score'] = $officeEV->item9_score;
                $pdfData['item10_score'] = $officeEV->item10_score;
                $pdfData['item11_score'] = $officeEV->item11_score;
                $pdfData['item12_score'] = $officeEV->item12_score;
                $pdfData['item13_score'] = $officeEV->item13_score;

                if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) {
                    $pdfData['item14_score'] = $officeEV->item14_score;
                }

                $pdfData['strengths'] = $officeEV->strengths;
                $pdfData['development_areas'] = $officeEV->development_areas;
                if (LD::checkFeatureFlag('add-input-goal-setting-professional-development-office-evaluation')) {
                    $pdfData['goal_setting_professional_development'] = $officeEV->goal_setting_professional_development;
                }
                $pdfData['completed_all_training'] = $officeEV->completed_all_training;
                $pdfData['subject_of_compliance'] = $officeEV->subject_of_compliance;
                $pdfData['commitment_to_compliance'] = $officeEV->commitment_to_compliance;
                $pdfData['overall_performance'] = $officeEV->overall_performance;
                $pdfData['overall_performance_score'] = $officeEV->overall_performance_score;

                $providerData = DB::table("mssql_providers")->select("mssql_providers.*",  
                DB::Raw("(SELECT name from users where users.id IN (
                    SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) as manager"),
                    DB::Raw("(SELECT title from users where mssql_providers.AOC_Email = users.email) as user_title"),
                    DB::Raw("(SELECT name from users where users.id IN (
                        SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) as supervisor")
                    )->where('mssql_providers.id',$record->entity_id)->where('mssql_providers.is_deleted',0)->first();

                $pdfData['name'] = $providerData->FirstName.' '.$providerData->LastName;
                $pdfData['manager'] = $providerData->manager;
                $pdfData['supervisor'] = $providerData->supervisor;
                $pdfData['positionTitle'] = $providerData->user_title;
				$startDate = date('Y-m-d', strtotime((date("Y",strtotime($officeEV->created_at)) - 1) . '-06-01'));
				$endDate = date('Y-m-d', strtotime(date("Y",strtotime($officeEV->created_at)) . '-05-31'));
                if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
                    $providerData->HiredDate = (
                        !empty($providerData->RehireDate) &&
                        $providerData->RehireDate != '0000-00-00' &&
                        $providerData->RehireDate != '1900-01-01'
                    ) ? $providerData->RehireDate : $providerData->HiredDate;
                }
                $currentDate = date('Y-m-d', strtotime($providerData->HiredDate));

                $pdfData['reviewPeriod'] = (($currentDate >= $startDate) && ($currentDate <= $endDate)) ?
					H::getFormatedDate($currentDate) . ' - ' . H::getFormatedDate($endDate) :
					H::getFormatedDate($endDate) . ' - ' . H::getFormatedDate($endDate);
                if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) {
                    $pdfData['comp_average'] = round(($officeEV->item1_score+$officeEV->item2_score+$officeEV->item3_score+$officeEV->item4_score+$officeEV->item5_score+$officeEV->item6_score+$officeEV->item7_score+$officeEV->item8_score+$officeEV->item9_score+$officeEV->item14_score)/10,2);
                } else {
                    $pdfData['comp_average'] = round(($officeEV->item1_score+$officeEV->item2_score+$officeEV->item3_score+$officeEV->item4_score+$officeEV->item5_score+$officeEV->item6_score+$officeEV->item7_score+$officeEV->item8_score+$officeEV->item9_score)/9,2);
                }
                $pdfData['manage_average'] = round(($officeEV->item10_score+$officeEV->item11_score+$officeEV->item12_score+$officeEV->item13_score)/4,2);
                $pdfData['currentDate'] = date("m/d/Y");
                $fileName = 'office_evaluation_'.$record->entity_id.'_'.strtotime("now").'.pdf';
                $template = 'officeevaluation';
                    $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName,$pdfData,$template);
                if(is_file($filePath)) {
                    $documentType = 'office_eval';
                    $documentTitle = "Office Evaluation";
                    $recipientName = $pdfData['name'];
                    $recipientEmail = $providerData->AOC_Email;
                    H::getHelper("RightSignature")->sendDocumentRequest($filePath,0,$record->entity_id,"provider",$documentType,$documentTitle,$recipientName,$recipientEmail,$record->evaluation_id);
                }
                DB::table("aoc_documents")->where("id",$record->id)->delete();

        }
        echo "finished";
        exit;

    }
    public function saveApprovalForm(Request $request)
    {
        $user = H::getLoggedInUser();
        $userId = $user['id'];
        $requestData = $request->all();
        H::ExcelLog("new Approveal Request from " . $user['name'],'officeEvaluationUpdate.log');
        H::ExcelLog(print_r($requestData,true),'officeEvaluationUpdate.log');
        $isManager = H::getHelper("OfficeEvaluation")->isEvaluationManager($userId);
        $isHR = H::getHelper("OfficeEvaluation")->isEvaluationHR($userId);
        
       
      H::ExcelLog(print_r($requestData,true),'OfficeEvaluationApproval.log');
        
        H::ExcelLog('is manager: ' . $isManager,'OfficeEvaluationApproval.log');
        H::ExcelLog('is hr: ' . $isHR,'OfficeEvaluationApproval.log');
         if($isHR){
            $data = [
                'status' => 'Pending Employee Review',
                'hr_id' => $userId,
                'hr_datetime' => date('Y-m-d H:i:s'),
                'hr_submitted' => 1
            ];
        } elseif($isManager) {
            if(LD::checkFeatureFlag('officeevaluation-remove-hr-steps')) {
                $status = 'Pending Employee Review';
            } else {
                $status = 'Pending HR Approval';
            }
            
            $data = [
                'status'=>$status,
                'manager_id'=>$userId,
                'manager_datetime'=>date('Y-m-d H:i:s'),
                'manager_submitted'=>1
            ];
        }
 
        if(!empty($requestData['evaluation_id']) && !empty($data)) {
            OfficeEvaluation::where("id",$requestData['evaluation_id'])->update($data);
            return response()->json(["status" => true]);
        }
        return response()->json(["status" => false]);
    }

    public function save(Request $request)
    {
        $user = H::getLoggedInUser();
        $requestData = $request->all();
        H::ExcelLog("new Request from " . $user['name'], 'officeEvaluationUpdate.log');
        H::ExcelLog(print_r($requestData, true), 'officeEvaluationUpdate.log');
        try {
            $data = [];
            if ($request->input('id') != '') {
                $data = $request->input('evaluation');
                $data['strengths'] = $request->input('strengths')??'';
                $data['development_areas'] = $request->input('development_areas')??'';
                $data['goal_setting_professional_development'] = $request->input('goal_setting_professional_development')??'';
                $data['completed_all_training'] = $request->input('completed_all_training')??'';
                $data['subject_of_compliance'] = $request->input('subject_of_compliance')??'';
                $data['commitment_to_compliance'] = $request->input('commitment_to_compliance')??'';
                $data['overall_performance'] = $request->input('overall_performance')??'';
                $data['overall_performance_score'] = $request->input('overall_performance_score')??'';
                $data['updated_by'] = $user['id'];

                if ($request->input("action") == 'submit') {
                    $data['supervisors_submitted'] = 1;
                    $data['supervisor_datetime'] = date('Y-m-d H:i:s');

                    $officeEV = OfficeEvaluation::where('id', $request->input('id'))->first();
                    $user = H::getLoggedInUser();
                    $loggedInUser = $user['id'];

                    if ($officeEV->manager_submitted) {
                        if(LD::checkFeatureFlag('officeevaluation-remove-hr-steps')) {
                            $data['status'] = 'Pending Employee Review';
                        } else {
                            $data['status'] = ($loggedInUser == '156') ? 'Pending Employee Review' : 'Pending HR Approval';
                        }
                       
                    } else {
                        $providerData = DB::table("mssql_providers")
                            ->select(
                                "mssql_providers.*",
                                DB::Raw("(SELECT CONCAT(id, '|' , name) from users where users.id IN (SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) as manager")
                            )
                            ->where('mssql_providers.id', $request->input("provider_id"))
                            ->where('mssql_providers.is_deleted', 0)
                            ->first();

                        $manager = explode("|", $providerData->manager);

                        if (isset($manager[0]) && !empty($manager[0])) {
                            if (H::getHelper("OfficeEvaluation")->isManagerIgnored($manager[0])) {
                                $employeeName = $providerData->FirstName . ' ' . $providerData->LastName;
                                $data['status'] = 'Pending Manager Approval';

                                $notes['user_id'] = $manager[0];
                                $notes['title'] = 'Action Required - Performance Evaluation Approval';
                                $notes['type'] = 'office_eval_view';
                                $notes['related_item'] = $request->input('id');

                                $noteMsg = $manager[1] . ', ' . $employeeName . "'s Annual Performance Evaluation is ready for your approval. ";
                                $noteMsg .= 'Please review and provide your feedback or approval as soon as possible.';
                                $notes['notes'] = $noteMsg;
                                Notifications::addNotification($notes);
                            } else {
                                if(LD::checkFeatureFlag('officeevaluation-remove-hr-steps')) {
                                    $data['status'] = 'Pending Employee Review';
                                } else {
                                    $data['status'] = ($loggedInUser == '156') ? 'Pending Employee Review' : 'Pending HR Approval';
                                }
                               
                            }
                        }
                    }
                } elseif ($request->input("action") == 'save') {
                    $data['status'] = 'Pending Supervisor Submission';
                } elseif ($request->input("action") == 'signature') {
                    $pdfData = $data;

                    $officeEV = OfficeEvaluation::where('id', $request->input('id'))->first();

                    $pdfData['item1_score'] = $officeEV->item1_score;
                    $pdfData['item2_score'] = $officeEV->item2_score;
                    $pdfData['item3_score'] = $officeEV->item3_score;
                    $pdfData['item4_score'] = $officeEV->item4_score;
                    $pdfData['item5_score'] = $officeEV->item5_score;
                    $pdfData['item6_score'] = $officeEV->item6_score;
                    $pdfData['item7_score'] = $officeEV->item7_score;
                    $pdfData['item8_score'] = $officeEV->item8_score;
                    $pdfData['item9_score'] = $officeEV->item9_score;
                    $pdfData['item10_score'] = $officeEV->item10_score;
                    $pdfData['item11_score'] = $officeEV->item11_score;
                    $pdfData['item12_score'] = $officeEV->item12_score;
                    $pdfData['item13_score'] = $officeEV->item13_score;

                    if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) {
                        $pdfData['item14_score'] = $officeEV->item14_score;
                    }

                    $pdfData['strengths'] = $officeEV->strengths;
                    $pdfData['development_areas'] = $officeEV->development_areas;
                    if (LD::checkFeatureFlag('add-input-goal-setting-professional-development-office-evaluation')) {
                        $pdfData['goal_setting_professional_development'] = $officeEV->goal_setting_professional_development;
                    }
                    $pdfData['completed_all_training'] = $officeEV->completed_all_training;
                    $pdfData['subject_of_compliance'] = $officeEV->subject_of_compliance;
                    $pdfData['commitment_to_compliance'] = $officeEV->commitment_to_compliance;
                    $pdfData['overall_performance'] = $officeEV->overall_performance;
                    $pdfData['overall_performance_score'] = $officeEV->overall_performance_score;

                    $providerData = DB::table("mssql_providers")->select("mssql_providers.*",
                        DB::Raw("(SELECT name from users where users.id IN (
                                SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) as manager"),
                        DB::Raw("(SELECT title from users where mssql_providers.AOC_Email = users.email) as user_title"),
                        DB::Raw("(SELECT name from users where users.id IN (
                                    SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) as supervisor")
                    )->where('mssql_providers.id', $request->input("provider_id"))->where('mssql_providers.is_deleted', 0)->first();
                    $pdfData['name'] = $providerData->FirstName . ' ' . $providerData->LastName;
                    $pdfData['manager'] = $providerData->manager;
                    $pdfData['supervisor'] = $providerData->supervisor;
                    $pdfData['positionTitle'] = $providerData->user_title;
					$startDate = date('Y-m-d', strtotime((date("Y",strtotime($officeEV->created_at)) - 1) . '-06-01'));
                    $endDate = date('Y-m-d', strtotime(date("Y",strtotime($officeEV->created_at)) . '-05-31'));
                    if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
                        $providerData->HiredDate = (
                            !empty($providerData->RehireDate) &&
                            $providerData->RehireDate != '0000-00-00' &&
                            $providerData->RehireDate != '1900-01-01'
                        ) ? $providerData->RehireDate : $providerData->HiredDate;
                    }
                    $currentDate = date('Y-m-d', strtotime($providerData->HiredDate));

                    $pdfData['reviewPeriod'] = (
                        ($currentDate >= $startDate) && ($currentDate <= $endDate)
                    ) ? H::getFormatedDate($currentDate) . ' - ' . H::getFormatedDate($endDate) :
					H::getFormatedDate($startDate) . ' - ' . H::getFormatedDate($endDate);
                    if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) {
                        $pdfData['comp_average'] = round(
                            (
                                $officeEV->item1_score +
                                $officeEV->item2_score +
                                $officeEV->item3_score +
                                $officeEV->item4_score +
                                $officeEV->item5_score +
                                $officeEV->item6_score +
                                $officeEV->item7_score +
                                $officeEV->item8_score +
                                $officeEV->item9_score +
                                $officeEV->item14_score
                            ) / 10,
                        2);
                    } else {
                        $pdfData['comp_average'] = round(
                            (
                                $officeEV->item1_score +
                                $officeEV->item2_score +
                                $officeEV->item3_score +
                                $officeEV->item4_score +
                                $officeEV->item5_score +
                                $officeEV->item6_score +
                                $officeEV->item7_score +
                                $officeEV->item8_score +
                                $officeEV->item9_score
                            ) / 9,
                        2);
                    }
                    $pdfData['manage_average'] = round(
                        (
                            $officeEV->item10_score +
                            $officeEV->item11_score +
                            $officeEV->item12_score +
                            $officeEV->item13_score
                        ) / 4,
                    2);
                    $pdfData['currentDate'] = date("m/d/Y");
                    $fileName = 'office_evaluation_' . $request->input("provider_id") . '_' . strtotime("now") . '.pdf';
                    $template = 'officeevaluation';
                    $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName, $pdfData, $template);

                    if (is_file($filePath)) {
                        $documentType = 'office_eval';
                        $documentTitle = "Office Evaluation";
                        $recipientName = $pdfData['name'];
                        $recipientEmail = $providerData->AOC_Email;
                        H::getHelper("RightSignature")->sendDocumentRequest(
                            $filePath,
                            0,
                            $request->input("provider_id"),
                            "provider",
                            $documentType,
                            $documentTitle,
                            $recipientName,
                            $recipientEmail,
                            $request->input("id")
                        );
                    } 
                    $data['status'] = 'Pending Employee Signature';
                }
                if (!empty($data)) {
                   
                    if ($request->input("action") == 'signature') {
                       unset($data['strengths']);
                       unset($data['development_areas']);
                       unset($data['goal_setting_professional_development']);
                       unset($data['completed_all_training']);
                       unset($data['subject_of_compliance']);
                       unset($data['commitment_to_compliance']);
                       unset($data['overall_performance']);
                       unset($data['overall_performance_score']);
                    }
                    $id = (int) $request->input('id');
                    OfficeEvaluation::where('id', $id)->update($data);
                }
                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog('ERROR: ' . $e->getMessage(),'saveofficeEva.log');
            return response()->json(['status' => false]);
        }
    }

    public function downloadpdf(Request $request)
    {
        $officeEV = OfficeEvaluation::where('id', $request->input('id'))->first();
        $pdfData = json_decode(json_encode($officeEV),true);
        $providerData = DB::table("mssql_providers")->select("mssql_providers.*",  DB::Raw("(SELECT name from users where users.id IN (
            SELECT manager_id from users where mssql_providers.AOC_Email = users.email)) as manager"),
            DB::Raw("(SELECT name from users where users.id IN (
                SELECT recruiting_manager from users where mssql_providers.AOC_Email = users.email)) as supervisor"),
                DB::Raw("(SELECT title from users where mssql_providers.AOC_Email = users.email) as user_title"))
            ->where('mssql_providers.id',$pdfData['aoc_provider_id'])->where('mssql_providers.is_deleted',0)->first();
        $pdfData['name'] = $providerData->FirstName.' '.$providerData->LastName;
        $pdfData['manager'] = $providerData->manager;
        $pdfData['supervisor'] = $providerData->supervisor;
        $pdfData['positionTitle'] = $providerData->user_title;
		$startDate = date('Y-m-d', strtotime((date("Y",strtotime($pdfData['created_at'])) - 1) . '-06-01'));
		$endDate = date('Y-m-d', strtotime(date("Y",strtotime($pdfData['created_at'])) . '-05-31'));
        if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
            $providerData->HiredDate = (
                !empty($providerData->RehireDate) &&
                $providerData->RehireDate != '0000-00-00' &&
                $providerData->RehireDate != '1900-01-01'
            ) ? $providerData->RehireDate : $providerData->HiredDate;
        }
		$currentDate = date('Y-m-d', strtotime($providerData->HiredDate));

        $pdfData['reviewPeriod'] = (($currentDate >= $startDate) && ($currentDate <= $endDate)) ?
			H::getFormatedDate($currentDate) . ' - ' . H::getFormatedDate($endDate) :
			H::getFormatedDate($startDate). ' - ' . H::getFormatedDate($endDate);
        if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) {
            $pdfData['comp_average'] = round(($pdfData['item1_score'] + $pdfData['item2_score'] + $pdfData['item3_score'] + $pdfData['item4_score'] + $pdfData['item5_score'] + $pdfData['item6_score'] + $pdfData['item7_score'] + $pdfData['item8_score'] + $pdfData['item9_score'] + $pdfData['item14_score']) / 10, 2);
        } else {
            $pdfData['comp_average'] = round(($pdfData['item1_score'] + $pdfData['item2_score'] + $pdfData['item3_score'] + $pdfData['item4_score'] + $pdfData['item5_score'] + $pdfData['item6_score'] + $pdfData['item7_score'] + $pdfData['item8_score'] + $pdfData['item9_score']) / 9, 2);
        }
        $pdfData['manage_average'] = round(($pdfData['item10_score'] + $pdfData['item11_score'] + $pdfData['item12_score'] + $pdfData['item13_score']) / 4, 2);
        $pdfData['currentDate'] = date("m/d/Y");
        $fileName = 'office_evaluation_' . $pdfData['aoc_provider_id'] . '_' . strtotime("now") . '.pdf';

        $template = 'officeevaluation';

        return H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName, $pdfData, $template, true);
    }

    public function exportData(Request $request)
    {
        $columns = [
            ['name' => 'EmployeeName', 'label' => 'Employee Name'],
            ['name' => 'BranchName', 'label' => 'Branch'],
            ['name' => 'supervisor', 'label' => 'Supervisor Name'],
            ['name' => 'manager', 'label' => 'Manager'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'fupdated_at', 'label' => 'Last Updated'],
            ['name' => 'selfevaluation', 'label' => 'Self Evaluation'],
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=evaluations.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        list($data) = $this->getOfficeEvaluation($request, $columns, 'export');

        $callback = function () use ($data, $columns) {
            $columnLabel =  array_map(function ($value) {
                return  $value['label'];
            }, $columns);

            $file = fopen('php://output', 'w');
            fputcsv($file, $columnLabel);

            foreach ($data as $tableData) {
                $tableData = $this->filterArrayByKeys($tableData, array_column($columns, 'name'));
                fputcsv($file, array_values($tableData));
            }

            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function delete(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                OfficeEvaluation::where('id', $request->input('id'))->delete();
                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }
}
