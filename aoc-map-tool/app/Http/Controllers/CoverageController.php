<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\LD;
use DB;
use Illuminate\Support\Facades\Auth;
use App\MssqlPatients;
use App\MssqlProviders;
use App\User;
use Illuminate\Support\Facades\Schema;
use App\RecruitingSchedule;
class CoverageController extends Controller
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT = 'Y-m-d';
    const ERROR_MSG = 'Something went wrong, Please try again.';
    const TIME_FORMAT = 'h:i A';

    public function validateDisciplineName(Request $request) {

        $providerId = $request->input('providerId');
        $coverageMapRowIds = $request->input('coverageMapRowIds') != '' ? explode(',',$request->input('coverageMapRowIds')) : [];
        if(!empty($providerId)){
            $providerCount = DB::table('mssql_providers')->select('id')->where('id',$providerId)->where('DisciplineName','RN')->get();
            if($providerCount->count() > 0){
                $coverageNeeds = DB::table('maps_coverageneeds')->select(DB::raw('group_concat(Service) as Service'))->whereIn('id',$coverageMapRowIds)->where('Service','like','%RN%')->get();
                if($coverageNeeds->count() != count($coverageMapRowIds)){
                    return response()->json(['status'=>false]);
                }
            }
        }
        return response()->json(['status'=>true]);
    }
    public function save(Request $request){
        $reqData = $request->all();
         try {
            $coverageMapRowIds = explode(',',$reqData['selectedCoverageRows']);
            $coverageNeeds = DB::table('maps_coverageneeds')->select('*')->whereIn('id',$coverageMapRowIds)->get()->toArray();
            $coverageInsertArr = [];
            foreach ($coverageNeeds as $key => $value) {
                $coverageInsertArr[$key] = json_decode(json_encode($value), true);
                unset($coverageInsertArr[$key]['id']);
                unset($coverageInsertArr[$key]['last_activity']);
                unset($coverageInsertArr[$key]['last_activity_by']);
                unset($coverageInsertArr[$key]['last_activity_date']);

                $coverageInsertArr[$key]['aoc_provider_id'] = $reqData['aoc_provider_id'];
                $coverageInsertArr[$key]['aoc_patient_id'] = H::getHelper('ConstantHelper')->getPatientIdByAccountId($coverageInsertArr[$key]['Patient_ID']);
                $coverageInsertArr[$key]['shift_date'] = date(
                    CoverageController::DATE_FORMAT,
                    strtotime($coverageInsertArr[$key]['Date'])
                );
                unset($coverageInsertArr[$key]['Date']);
                $coverageInsertArr[$key]['filterDate'] = date(
                    CoverageController::DATE_FORMAT,
                    strtotime($coverageInsertArr[$key]['filterDate'])
                );
                $coverageInsertArr[$key]['escalated_pay_rate'] = $reqData['escalated_pay_rate'];
                $coverageInsertArr[$key]['approved_by'] = $reqData['approved_by'];
                $coverageInsertArr[$key]['gp'] = $reqData['gp'];
                $coverageInsertArr[$key]['filled_by'] = Auth::user()->id;
                $coverageInsertArr[$key]['date_filled'] = date(CoverageController::DATE_TIME_FORMAT);
                $coverageInsertArr[$key]['created_by'] = Auth::user()->id;
                $coverageInsertArr[$key]['updated_by'] = Auth::user()->id;
                $coverageInsertArr[$key]['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
            }
            DB::table('coverage_tracker')->insert($coverageInsertArr);
            DB::table('maps_coverageneeds')->select('*')->whereIn('id',$coverageMapRowIds)->delete();
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog('Save coverage:'.$e->getMessage(),'coverage.log');
            return response()->json(['status'=>false]);
        }
    }

    public function showCoverageTrackerDeletePopup(Request $request){
        $r = DB::table('coverage_tracker')->select("*")->where('id',$request->id)->first();
        return view('coverage.coverageDelete',["coverage"=>$request->id,"coverageData"=>$r]);
    }

    public function deleteCoverageTrackerRecord(Request $request){
        $id = $request->input('id');

        if(isset($id) && $id != ''){
            $r = DB::table('coverage_tracker')->where('id',$id);

            $deletedData =  json_decode(json_encode($r->first()),true);

            $deletedData['coverage_tracker_id'] = $deletedData['id'];

            $deletedData['reason']=$request->reason;
            $deletedData['reason_category']=$request->reason_category;
            unset($deletedData['id']);
            $deletedData['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
            $deletedData['updated_at'] = date(CoverageController::DATE_TIME_FORMAT);

            DB::table("coverage_tracker_log")->insert($deletedData);

            H::ExcelLog('Record Deleted.'.print_r($r->first(), true),'deletedCoverage.log');
            $r->delete();
            //TICKET-3888
            if(LD::checkFeatureFlag('coverage-edit-delete-fix-ccr')) {
                try {
                    //Set CCR outcome to Cancelled
                    H::ExcelLog('Outcome Set to Cancelled for coverage
                    id-'.$deletedData['coverage_tracker_id'],'deletedCoverage.log');
                    $scheduleData['outcome'] = 'Cancelled';
                    $scheduleData['coverage_id'] = 0;
                    RecruitingSchedule::where('coverage_id',$deletedData['coverage_tracker_id'])->update($scheduleData);
                    //if there is any future coverage shift for clinician/client combination
                    $this->coverageCcrProcess($deletedData['aoc_patient_id'],$deletedData['aoc_provider_id']);
                } catch (\Throwable $th) {
                    H::ExcelLog('Throwable.'.$th->getMessage(),'deletedCoverage.log');
                }
            }
            return response()->json(['status'=>true]);
        }else{
            return response()->json(['status'=>false]);
        }
    }
    public function coverageList(Request $request){
        $columns = [
            ['name'=>'date_filled','label'=>'Date Filled'],
            ['name'=>'filled_by_name','label'=>'Coordinator'],
            ['name'=>'branch_name','label'=>'Coordinator Branch'],
            ['name'=>'FacilityName','label'=>'Client Location'],
            ['name'=>'shift_date','label'=>'Visit Date'],
            ['name'=>'PatientAccount','label'=>'ClientID'],
            ['name'=>'PatientName','label'=>'Client Name'],
            ['name'=>'Payer','label'=>'Payer'],
            ['name'=>'payer_group','label'=>'Special Payer'],
            ['name'=>'Hours','label'=>'Planned Hours'],
            ['name'=>'ProviderName','label'=>'Clinician'],
            ['name'=>'DisciplineName','label'=>'Discipline'],
            ['name'=>'escalated_pay_rate','label'=>'Escalated Pay Rate'],
            ['name'=>'approved_by','label'=>'Approved By'],
            ['name'=>'gp','label'=>'GP %'],
            ['name'=>'created_manually','label'=>'Source']
        ];
        if(LD::checkFeatureFlag('add-intake-coverage-tracker-show-all-page')) {
            $pos = array_search("PatientName",array_column($columns, 'name'));
            $pos = $pos+1;
            $insert = [['name'=>'intake','label'=>"Intake"]];
            $columns = array_merge(
                array_slice($columns, 0, $pos),
                $insert,
                array_slice($columns, $pos)
            );
        }
        if(LD::checkFeatureFlag('add-delete-icon-coverage-tracker-show-all-page') || LD::checkFeatureFlag('edit-icon-coverage-tracker-show-all-page')){
            $columns[] = ['name'=>'action','label'=>'Action'];
        }
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');
            if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
                
                $q = DB::table('coverage_tracker')
                ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
                ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
                ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
                ->leftJoin('users', 'users.id', '=', 'coverage_tracker.created_by')
                ->select(
                    'coverage_tracker.id',
                    'users.name as filled_by_name',
                    'users.branchid',
                    'mssql_facilities.FacilityName',
                    'mssql_patients.PatientAccount',
                    'mssql_patients.PatientName',
                    'mssql_patients.Payer',
                    'mssql_patients.payer_group',
                    'coverage_tracker.Hours',
                    DB::raw('CASE WHEN coverage_tracker.aoc_provider_id = 0 THEN coverage_tracker.new_clinician_name ELSE mssql_providers.ProviderName END as ProviderName'),
                    'mssql_providers.DisciplineName',
                    'coverage_tracker.escalated_pay_rate',
                    'coverage_tracker.approved_by',
                    'coverage_tracker.gp',
                    'mssql_patients.id as patient_id',
                    DB::raw("IF(coverage_tracker.created_manually = 1, 'Manual Add', 'Map') AS created_manually"),
                    DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
                    DB::raw("DATE_FORMAT(coverage_tracker.date_filled,'%m/%d/%Y') AS date_filled"),
                    DB::raw("(select GROUP_CONCAT(branch) from branch_locations where FIND_IN_SET(branch_locations.id, users.branchid)) as branch_name")
                );
            }else{
                $q = DB::table('coverage_tracker')
                ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
                ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
                ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
                ->leftJoin('users', 'users.id', '=', 'coverage_tracker.filled_by')
                ->select(
                    'coverage_tracker.id',
                    'users.name as filled_by_name',
                    'users.branchid',
                    'mssql_facilities.FacilityName',
                    'mssql_patients.PatientAccount',
                    'mssql_patients.PatientName',
                    'mssql_patients.Payer',
                    'mssql_patients.payer_group',
                    'coverage_tracker.Hours',
                    DB::raw('CASE WHEN coverage_tracker.aoc_provider_id = 0 THEN coverage_tracker.new_clinician_name ELSE mssql_providers.ProviderName END as ProviderName'),
                    'mssql_providers.DisciplineName',
                    'coverage_tracker.escalated_pay_rate',
                    'coverage_tracker.approved_by',
                    'coverage_tracker.gp',
                    'mssql_patients.id as patient_id',
                    DB::raw("IF(coverage_tracker.created_manually = 1, 'Manual Add', 'Map') AS created_manually"),
                    DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
                    DB::raw("DATE_FORMAT(coverage_tracker.date_filled,'%m/%d/%Y') AS date_filled"),
                    DB::raw("(select GROUP_CONCAT(branch) from branch_locations where FIND_IN_SET(branch_locations.id, users.branchid)) as branch_name")
                );
            }

            if(LD::checkFeatureFlag('add-intake-coverage-tracker-show-all-page')) {
                $q->addSelect('coverage_tracker.intake');
            }
            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->where(function ($query) use ($search) {
                    $query->where('mssql_patients.PatientName', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_patients.Payer', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                    $query->orWhere('coverage_tracker.new_clinician_name', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_providers.ProviderName', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            $filters = [];
            if (isset($request['filters'])) {
                foreach ($request['filters'] as $fl) {
                    if (!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }
                foreach ($filters as $fkey => $fval) {
                    if ($fkey == 'state') {
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("mssql_patients.Patient_State", '=', $val);
                            }
                        });
                    } elseif ($fkey == 'aoc_facility_id') {
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("mssql_patients.aoc_facility_id", '=', $val);
                            }
                        });
                    } elseif ($fkey == 'ClientCareCoordinator') {
                        if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("coverage_tracker.created_by", '=', $val);
                                }
                            });
                        } else {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("coverage_tracker.filled_by", '=', $val);
                                }
                            });
                        }
                        
                    } elseif ($fkey == 'Payer') {
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("mssql_patients.Payer", '=', $val);
                            }
                        });
                    } elseif ($fkey == 'payer_group') {
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("mssql_patients.payer_group", '=', $val);
                            }
                        });
                    } elseif($fkey == 'ctmemode'){
                        if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
                            $q->where(function ($query) use ($fval) {
                                if($fval){
                                    $query->orWhere("coverage_tracker.created_by", '=', Auth::user()->id);
                                }
                            });
                        } else {
                            $q->where(function ($query) use ($fval) {
                                if($fval){
                                    $query->orWhere("coverage_tracker.filled_by", '=', Auth::user()->id);
                                }
                            });
                        }
                       
                    }elseif($fkey == 'intake'){
                        $q->where("coverage_tracker.intake",$fval);
                    }
                }

                if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                    $q->whereBetween('coverage_tracker.date_filled', [date('Y-m-d 00:00:00', strtotime($filters['from_date'][0])), date('Y-m-d 23:59:59', strtotime($filters['to_date'][0]))]);
                }
                $totalFiltered = $q->count();
            }
            $totalHoursCollection = clone $q;
            $totalHoursData = $totalHoursCollection->get()->toArray();
            $totalHours = array_sum(array_column($totalHoursData,'Hours'));
            H::ExcelLog('Coverage List: ' . $q->toSql(), 'coverageList.log');
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
            $data = H::objToArr($data);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
                'counthours'     => $totalHours,
            ]);
        }
        return view('coverage.grid',['columns' => $columns]);
    }

    public function exportCoverageData(Request $request)
    {
        $columns = [
            ['name'=>'date_filled', 'label'=>'Date Filled'],
            ['name'=>'filled_by_name','label'=>'Coordinator'],
            ['name'=>'branch_name','label'=>'Coordinator Branch'],
            ['name'=>'FacilityName','label'=>'Client Location'],
            ['name'=>'shift_date','label'=>'Visit Date'],
            ['name'=>'PatientAccount','label'=>'ClientID'],
            ['name'=>'PatientName','label'=>'Client Name'],
            ['name'=>'Payer','label'=>'Payer'],
            ['name'=>'payer_group','label'=>'Special Payer'],
            ['name'=>'Hours','label'=>'Planned Hours'],
            ['name'=>'ProviderName','label'=>'Clinician'],
            ['name'=>'DisciplineName','label'=>'Discipline'],
            ['name'=>'escalated_pay_rate','label'=>'Escalated Pay Rate'],
            ['name'=>'approved_by','label'=>'Approved By'],
            ['name'=>'gp','label'=>'GP %'],
        ];

        $dataTableFields = [
            'date_filled' => 'Date Filled',
            'filled_by_name' => 'Coordinator',
            'branch_name' => 'Coordinator Branch',
            'FacilityName' => 'Client Location',
            'shift_date' => 'Visit Date',
            'PatientAccount' => 'ClientID',
            'PatientName' => 'Client Name',
            'Payer' => 'Payer',
            'payer_group' => 'Special Payer',
            'Hours' => 'Planned Hours',
            'ProviderName' => 'Clinician',
            'DisciplineName' => 'Discipline',
            'escalated_pay_rate' => 'Escalated Pay Rate',
            'approved_by' => 'Approved By',
            'gp' => 'GP %',
            'created_manually' => 'Source',
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=manageuserData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $filters = H::getFilterInputArr($request->all());

        $search = $request->input('search.value');
        if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
            $q = DB::table('coverage_tracker')
        ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
        ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
        ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
        ->leftJoin('users', 'users.id', '=', 'coverage_tracker.created_by')
        ->select(
            DB::raw("DATE_FORMAT(coverage_tracker.date_filled,'%m/%d/%Y') AS date_filled"),
            'users.name as filled_by_name',
            DB::raw("(select GROUP_CONCAT(branch) from branch_locations where FIND_IN_SET(branch_locations.id, users.branchid)) as branch_name"),
            'mssql_facilities.FacilityName',
            DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
            'mssql_patients.PatientAccount',
            'mssql_patients.PatientName',
            'mssql_patients.Payer',
            'mssql_patients.payer_group',
            'coverage_tracker.Hours',
            'mssql_providers.ProviderName',
            'mssql_providers.DisciplineName',
            'coverage_tracker.escalated_pay_rate',
            'coverage_tracker.approved_by',
            'coverage_tracker.gp',
            DB::raw("IF(coverage_tracker.created_manually = 1, 'Manual Add', 'Map') AS created_manually"),
        );
        } else {
            $q = DB::table('coverage_tracker')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'coverage_tracker.aoc_provider_id')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'coverage_tracker.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'coverage_tracker.filled_by')
            ->select(
                DB::raw("DATE_FORMAT(coverage_tracker.date_filled,'%m/%d/%Y') AS date_filled"),
                'users.name as filled_by_name',
                DB::raw("(select GROUP_CONCAT(branch) from branch_locations where FIND_IN_SET(branch_locations.id, users.branchid)) as branch_name"),
                'mssql_facilities.FacilityName',
                DB::raw("DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y') AS shift_date"),
                'mssql_patients.PatientAccount',
                'mssql_patients.PatientName',
                'mssql_patients.Payer',
                'mssql_patients.payer_group',
                'coverage_tracker.Hours',
                'mssql_providers.ProviderName',
                'mssql_providers.DisciplineName',
                'coverage_tracker.escalated_pay_rate',
                'coverage_tracker.approved_by',
                'coverage_tracker.gp',
                DB::raw("IF(coverage_tracker.created_manually = 1, 'Manual Add', 'Map') AS created_manually"),
            );
        }
        
        if (!empty($search)) {
            $q->where(function ($query) use ($search) {
                $query->where('mssql_patients.PatientName', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_patients.Payer', 'LIKE', "%{$search}%");
                $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
            });
        }
        $filters = [];
        if (isset($request['filters'])) {
            foreach ($request['filters'] as $fl) {
                if (!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
            foreach ($filters as $fkey => $fval) {
                if ($fkey == 'state') {
                    $q->where(function ($query) use ($fval) {
                        foreach ($fval as $val) {
                            $query->orWhere("mssql_patients.Patient_State", '=', $val);
                        }
                    });
                } elseif ($fkey == 'aoc_facility_id') {
                    $q->where(function ($query) use ($fval) {
                        foreach ($fval as $val) {
                            $query->orWhere("mssql_patients.aoc_facility_id", '=', $val);
                        }
                    });
                } elseif ($fkey == 'ClientCareCoordinator') {
                    if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("coverage_tracker.created_by", '=', $val);
                            }
                        });
                    } else {
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("coverage_tracker.filled_by", '=', $val);
                            }
                        });
                    }
                    
                } elseif ($fkey == 'Payer') {
                    $q->where(function ($query) use ($fval) {
                        foreach ($fval as $val) {
                            $query->orWhere("mssql_patients.Payer", '=', $val);
                        }
                    });
                } elseif ($fkey == 'payer_group') {
                    $q->where(function ($query) use ($fval) {
                        foreach ($fval as $val) {
                            $query->orWhere("mssql_patients.payer_group", '=', $val);
                        }
                    });
                } elseif($fkey == 'ctmemode'){
                    if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')){
                        $q->where(function ($query) use ($fval) {
                            if($fval){
                                $query->orWhere("coverage_tracker.created_by", '=', Auth::user()->id);
                            }
                        });
                    } else {
                        $q->where(function ($query) use ($fval) {
                            if($fval){
                                $query->orWhere("coverage_tracker.filled_by", '=', Auth::user()->id);
                            }
                        });
                    }
                    
                }
            }
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $q->whereBetween('coverage_tracker.date_filled', [date('Y-m-d 00:00:00', strtotime($filters['from_date'][0])), date('Y-m-d 23:59:59', strtotime($filters['to_date'][0]))]);
            }
        }
        $data = json_decode(json_encode($q->get()->toArray()), true);
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=CoverageData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($dataTableFields));

            foreach ($data as $tableData) {
                fputcsv($file, $tableData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function getCoverageTrackerStepForm(Request $request) {
        $type = $request->input("type");
        $title = H::getHelper("ClientCareCoordinatorCoverage")->getBucketTitle($request->input("type"));
        $ids = $request->input("ids");
        $fieldLabelArray = config('constant')['coverageTrackerStepLabel'];
        $fieldLabel = $fieldLabelArray[$type];
        return view('ccc.stepsform', compact('title', 'type', 'ids', 'fieldLabel'));
    }
    public function saveCoverageTrackerStep(Request $request) {
        try {
            $params = $request->all();
            $buckets =H::getHelper("ClientCareCoordinatorCoverage")->getBucketArray();
            $type = $params['type'];
            $nextStepKey = 0;
            $currentStep = array_search($type, array_keys($buckets));
            $nextStep = $currentStep+1;
            $values = array_values($buckets);
            $keys = array_keys($buckets);
            if ($nextStep < count($values)) {
                $nextStepKey = $keys[$currentStep + 1];
            }
            $lastStepKey = $keys[count($keys)-1];
            $createdByName = H::getLoggedInUser()['name'];
            $checklistArray = explode(",",$params['ids_'.$type]);
            foreach($checklistArray as $checklistId){
                $bucket = DB::table("maps_coverageneeds");
                $checklistDetail  = $bucket->where('id',$checklistId)->first();
                $checklistDetail  = H::objToArr($checklistDetail);
                if($lastStepKey != $type) {
                    $updateAry['last_activity'] = $nextStepKey;
                }
                $updateAry[$keys[$currentStep]."_activity_date"] = date(CoverageController::DATE_TIME_FORMAT);
                $updateAry[$keys[$currentStep]."_number"] = $params['no_of_nurses_contacted'];
                $updateAry['last_activity_by'] = Auth::user()->id;
                $bucket->where('id',$checklistId)->update($updateAry);
                //Saving activity notes
                $note = $this->getNoteMessage($type);
                $str = ['[user_name]', '[selected_count]'];
                $rplc = [$createdByName, $params['no_of_nurses_contacted']];
                $finalnote = str_replace($str, $rplc, $note);

                $notesData = ['entity' => 'coverage', 'notes' => $finalnote, 'entity_id' => $checklistId, 'type' => 'coverageStep'];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                if (isset($params['escalated_rate']) && $params['escalated_rate'] > 0) {
                    $escalatedRateNote = $createdByName.' escalated the rate to $'.$params['escalated_rate'];
                    $escalatedRateNotesData = ['entity' => 'coverage', 'notes' => $escalatedRateNote, 'type' => 'coverageStep','entity_id' => $checklistId,'misc1' => 'TextBlastEscalatedRate','misc2'=> $params['escalated_rate']];
                    H::getHelper('ActivityNotes')->setActivityNotes($escalatedRateNotesData);
                }

                if (isset($params['content']) && $params['content'] != '') {
                    $contentNotes = $createdByName.' updated the content of the Text Blast:'.$params['content'];
                    $contentNotesData = ['entity' => 'coverage', 'notes' => $contentNotes, 'type' => 'coverageStep','entity_id' => $checklistId,'misc1' => 'content','misc2'=> $params['content']];
                    H::getHelper('ActivityNotes')->setActivityNotes($contentNotesData);
                }
            }
            $nextStepCounter = H::getHelper('ClientCareCoordinatorCoverage')->getClientCareCoverageTracker($nextStepKey,'',1);
            return response()->json(['success' => 1,'next_step_counter' => $nextStepCounter, 'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            return response()->json([
                'success' => 0,
                'message' => $ex->getLine().$ex->getMessage().CoverageController::ERROR_MSG
            ]);
        }
    }

    public function getNoteMessage($type) {
        $note = '';
        switch ($type) {
            case 'text_call_client_schedule_regulars':
                $note = '[user_name] texted and called [selected_count] schedule regulars.';
                break;
            case 'text_call_record_clinicians':
                $note = '[user_name] texted and called [selected_count] record clinicians.';
                break;
            case 'text_call_prn_clinicians_from_local_prn_schedule':
                $note = '[user_name] texted and called [selected_count] PRN clinicians.';
                break;
            case 'text_blast_180_mile_radius_from_text_blast_map':
                $note = '[user_name] text [selected_count] clinicians in 180-mile radius from the text blast map';
                break;
            case 'call_nurses_from_text_blast_map':
                $note = '[user_name] called [selected_count] clinicians in 180 mile radius from the text blast map';
                break;
            case 'check_cover_nurses_availability':
                $note = '[user_name] checked cover nurse availability';
                break;
            default:
                break;
        }
        return $note;
    }

    public function getClientIntakeOutcom(Request $request)
    {
        try {
            $attemptCount = 0;
            if ($request->input('id') != '') {
                $clientDetails = MssqlPatients::select("mssql_patients.*", "mssql_facilities.FacilityName", DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')->where("mssql_patients.id", $request->input('id'))->first();
                if($clientDetails->PatientName == ''){
                    $clientDetails->setAttribute('PatientName', $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName);
                }
                //Check if Work force exist
                $is_workForce = 0;
                if (substr_count(strtolower($clientDetails->CF_WorkForceDevProj), 'yes')) {
                    $is_workForce = 1;
                }
                $attemptCount = $clientDetails->attempt_count;
                $clientDetails->setAttribute('is_workForce', $is_workForce);
                $skillArray = $skillAddedArray = [];
                $skillDisplayValue = '<div class="tags-cnt">';
                if ($clientDetails->CF_Skills != '') {
                    $skillAddedArray = explode(",", $clientDetails->CF_Skills);
                    foreach ($skillAddedArray  as $skillValue) {
                        if (strtolower($skillValue) == 'trach' || strtolower($skillValue) == 'vent') {
                            $skillArray[] = $skillValue;
                        }
                    }
                    if (!empty($skillArray)) {
                        foreach ($skillArray as $skillData) {
                            $skillDisplayValue .= '<span class="tag tag-grey">' . $skillData . '</span>';
                        }
                    }
                }
                $skillDisplayValue .= '</div>';
                $clientDetails->setAttribute('skillDisplayValue', $skillDisplayValue);
                return view('ccc.clientintakeoutcom', ["attemptCount"=>$attemptCount,'clientDetails' => $clientDetails]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'getClientIntakeOutcom.log');
            return false;
        }
    }
    public function saveClientIntakeOutcom(Request $request) {
        try {
            $params = $request->all();

            $createdByName = H::getLoggedInUser()['name'];
            $updateAry = [];
            $finalNote = $middleNote ='';

            if($params['outcome']=='Attempt 1' || $params['outcome']=='Attempt 2' || $params['outcome']=='Attempt 3') {

                $patientCount = DB::table("mssql_patients")->select("*")->where('id',"=",$request->aoc_patient_id)->first();
                $patientCount->attempt_count = $patientCount->attempt_count + 1;

                DB::table("mssql_patients")->where('id',"=",$request->aoc_patient_id)
                ->update(
                    [
                        "attempt_count"=>$patientCount->attempt_count,
                        "outcome"=>$params['outcome'],
                        'ccc_intro_call_made_at'=>date(CoverageController::DATE_TIME_FORMAT)
                    ]);
               // $weeklyFollowupText = 'Monthly follow up call for '.$patientName. ' ('.$reqData['outcome'].')';
               $finalNote = $createdByName.' made an ('.$params['outcome'].') to the client introductory call on '.date('m/d/Y H:i:s').'<br> Comment :'.$params['contact_comment'];

            } else if($params['outcome']=='Requested Call back') {
                DB::table("mssql_patients")->where('id',"=",$request->aoc_patient_id)
                ->update(
                    [
                        "requested_callback"=>$params['requested_callback'],
                        "outcome"=>$params['outcome'],
                        'ccc_intro_call_made_at'=>date(CoverageController::DATE_TIME_FORMAT)
                    ]);

                $finalNote = $createdByName.' Requested call back to the client introductory call <br> Requested Call Back: '.$params['requested_callback'];

            } else if($params['outcome']=='Document Call'){
                $finalNote = $createdByName.' completed the client introductory call on '.date('m/d/Y H:i:s').'. '.$params['comment'];
                $updateAry['ccc_intro_call'] = 1;
                $updateAry['outcome'] = $params['outcome'];
                $updateAry['ccc_intro_call_made_at'] = date(CoverageController::DATE_TIME_FORMAT);
                $updateAry['ccc_intro_call_made_by'] = Auth::user()->id;
                DB::table("mssql_patients")->where('id',$params['aoc_patient_id'])->update($updateAry);
            }

           /* if($params['outcome'] == 1) {
                $updateAry['ccc_intro_call'] = 1;
                $finalNote = $createdByName.' completed the client introductory call on '.date('m/d/Y H:i:s').'. '.$params['comment'];
            }else{
                if($params['outcome'] == 2) {
                    $middleNote = ' and left a voice message';
                }elseif($params['outcome'] == 3) {
                    $middleNote = ' and was unable to leave voice message';
                }elseif($params['outcome'] == 4) {
                    $middleNote = ' and was asked to call back later';
                }
                $finalNote = $createdByName.' attempted the client introductory call on '.date('m/d/Y H:i:s').$middleNote.'. '.$params['comment'];
            }*/

            $notesData = ['entity' => 'client', 'notes' => $finalNote, 'entity_id' => $params['aoc_patient_id'], 'type' => 'introcall'];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            return response()->json(['success' => 1, 'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => CoverageController::ERROR_MSG]);
        }
    }

    public function getCoverageDetails(Request $request){
        $id = $request->input('id');
        $patientsDetails = [];
        $coverageDetail = [];
        if($id != ''){
            $coverageDetail = DB::table("maps_coverageneeds")->select('maps_coverageneeds.*',DB::raw("ifnull(maps_coverageneeds.last_activity,'text_call_client_schedule_regulars') as last_activity"))->where('id',$id)->first();
            if(isset($coverageDetail->aoc_patient_id) && $coverageDetail->aoc_patient_id != ''){
                $patientsDetails = MssqlPatients::find($coverageDetail->aoc_patient_id);
            }
            return view('ccc.coverageDetail', ['coverageDetail'=>$coverageDetail,'patientsDetails'=>$patientsDetails]);
        }
    }

    public function getCoverageShiftTrackerForm(Request $request) {
        
        $approvedByList = H::getHelper('ConstantHelper')->getUsersByRoles(['Client Care Coordinator','Coverage Coordinator','Client Care Manager','Director of Branch Operations','Regional Director of Operations','State VP','Executive Team']);
        //Sort by name
        $keys = array_column($approvedByList, 'text');
        array_multisort($keys, SORT_ASC, $approvedByList);
        if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc')) {
            return view('ccc.coverageshifttrackerformnew',['approvedByList' => $approvedByList, 'request' => $request->all()]);
        } else {
            return view('ccc.coverageshifttrackerform',['approvedByList' => $approvedByList]);
        }
    }

    private function addRecruitingSchedule($requestData, $lastCoverageTrackerId, $appointmentDate) {
        H::ExcelLog('Inside','addRecruitingSchedule.log');
        $date = \Carbon\Carbon::today()->subDays(30);
        $today = \Carbon\Carbon::today();
        $lastScheduleData = DB::table("recruiting_schedule");
        $lastScheduleData->where("aoc_patient_id", $requestData['aoc_patient_id']);
        $lastScheduleData->where("aoc_provider_id", $requestData['aoc_provider_id']);
        $lastScheduleData->where("recruiting_schedule.outcome",'<>','Cancelled');
        $lastScheduleData->where("type_id", 2);
        $lastScheduleData->whereBetween("scheduled_date",[$date, $today]);
        if (
            isset($requestData['need_to_add_ccr']) &&
            $requestData['need_to_add_ccr'] == 1 &&
            $lastScheduleData->count() == 0) {
            H::ExcelLog('need_to_add_ccr','addRecruitingSchedule.log');
            $ccrDate = date(CoverageController::DATE_FORMAT, strtotime('-1 day', strtotime($appointmentDate)));
            if ($ccrDate < date(CoverageController::DATE_FORMAT)) {
                $ccrDate = date(CoverageController::DATE_FORMAT);
            }
            $scheduleData = [];
            $scheduleData['type_id'] = 2;
            $scheduleData['appointment_date'] = $ccrDate;
            $scheduleData['appointment_time'] = '';
            $scheduleData['aoc_patient_id'] = $requestData['aoc_patient_id'];
            $scheduleData['aoc_provider_id'] = $requestData['aoc_provider_id'];
            $scheduleData['pay_rate'] = $requestData['escalated_pay_rate'];
            $scheduleData['gp'] = $requestData['gp'];
            $scheduleData['gp_approved_by'] = $requestData['gp_approved_by'];
            $scheduleData['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
            $scheduleData['updated_at'] = date(CoverageController::DATE_TIME_FORMAT);
            $scheduleData['scheduled_date'] = date(CoverageController::DATE_FORMAT);
            $scheduleData['outcome'] = '';
            $scheduleData['created_by'] = Auth::user()->id;
            $scheduleData['updated_by'] = Auth::user()->id;
            $scheduleData['coverage_id'] = $lastCoverageTrackerId;
            H::ExcelLog('Record Added ===','addRecruitingSchedule.log');
            H::ExcelLog(print_r($scheduleData, true),'addRecruitingSchedule.log');
            RecruitingSchedule::create($scheduleData);
        }
    }
    public function saveCoverageShiftTrackerForm(Request $request) {
        try {
            $requestData = $request->all();
            
            if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc')) {
                if ($requestData['source'] == 'dashboard_buckets_add_form') {
                    $coverageInsertArr = [];
                    $coverageInsertArr['aoc_patient_id'] = $requestData['aoc_patient_id'];
                    $coverageInsertArr['aoc_provider_id'] = $requestData['aoc_provider_id'];
                    $coverageInsertArr['shift_date'] = date(
                        CoverageController::DATE_FORMAT,
                        strtotime($requestData['shift_date'])
                    );
                    $coverageInsertArr['Schedule_Time'] = date(
                        CoverageController::TIME_FORMAT,
                        strtotime($requestData['start_time'])
                    ).' - '.
                    date(
                        CoverageController::TIME_FORMAT,
                        strtotime($requestData['end_time'])
                    );
                    $coverageTrackerDetail = DB::table('maps_coverageneeds')->where('shift_date',$coverageInsertArr['shift_date'])->where('Schedule_Time',$coverageInsertArr['Schedule_Time'])->where('aoc_patient_id',$coverageInsertArr['aoc_patient_id'])->first();
                    if(!empty($coverageTrackerDetail)) {
                        return response()->json(['success' => 0, 'message' => 'This shift is already on the Coverage Needs map. Please remove coverage shifts from the map instead.']);
                    }
                    if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                        if($coverageInsertArr['aoc_provider_id'] == 0){
                            $coverageInsertArr['new_clinician_name'] = $requestData['candidate_name'];
                        }
                    }
                    $coverageInsertArr['Name'] = $requestData['clientName'];
                    $coverageInsertArr['approved_by'] = $requestData['gp_approved_by'];
                    $coverageInsertArr['gp'] = $requestData['gp'];
                    $coverageInsertArr['Hours'] = $requestData['Hours'];
                    $coverageInsertArr['escalated_pay_rate'] = $requestData['escalated_pay_rate'];
                    $coverageInsertArr['filled_by'] = $requestData['filled_by'];
                    $coverageInsertArr['date_filled'] = date(CoverageController::DATE_TIME_FORMAT);
                    $coverageInsertArr['created_by'] = Auth::user()->id;
                    $coverageInsertArr['updated_by'] = Auth::user()->id;
                    $coverageInsertArr['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
                    $clientDetails = DB::table('mssql_patients')->select('mssql_patients.Patient_FirstName','mssql_patients.Patient_LastName')->where('mssql_patients.id',$requestData['aoc_patient_id'])->first();
                    $coverageInsertArr['Client_First_Name'] = $clientDetails->Patient_FirstName;
                    $coverageInsertArr['Client_Last_Name'] = $clientDetails->Patient_LastName;
                    $coverageInsertArr['created_manually'] = 1;

                    
                        
                    if(LD::checkFeatureFlag('rightsignature-snsform-pdf-trigger') 
                    && !empty($requestData['aoc_patient_id']) 
                    && !empty($requestData['aoc_provider_id'])) {

                        $providerDetailsRightSignature = MssqlProviders::find($requestData['aoc_provider_id']);
                        $patientDetailsRightSignature = MssqlPatients::find($requestData['aoc_patient_id']);
                        

                        $signedDocuments = DB::table("aoc_documents")
                        ->select("*")
                        ->where("entity_id",$requestData['aoc_provider_id'])
                        ->where("entity_type","provider")
                        ->where("status","executed")->get();

                        $isAlreadySigned = false;

                        foreach($signedDocuments as $signed) {
                            $extraData = json_decode($signed->extra_data,true);
                            if(!empty($extraData) 
                            && (number_format((float)$requestData['escalated_pay_rate'], 2, '.', '')==$extraData['pay_rate'])
                            && ($patientDetailsRightSignature->PatientID==$extraData['client_id'])) {
                                $isAlreadySigned = true;
                            }
                        }
                        
                        
                        if(!$isAlreadySigned) {
                            $pp = $requestData['aoc_provider_id'];
                            $fileName = "pay_rate_provider_".$pp.'_'.strtotime("now").'.pdf';
                            $entityId = $requestData['aoc_provider_id'];
                            $entityType = 'provider';
                            $pdfEmail= $providerDetailsRightSignature->AOC_Email;
                            $note = "Pay Rate Agreement Sent to the Provider";

                        
                            $facilityDetails = DB::table("mssql_facilities")
                            ->select("*")
                            ->where("FacilityID",$providerDetailsRightSignature->FacilityID)
                            ->first();
                    
                            $escPay = $requestData['escalated_pay_rate'];
                            $pdfData['emp_name'] = $providerDetailsRightSignature->fullName;
                            $pdfData['client_name'] = $patientDetailsRightSignature->Patient_FirstName.'
                             '.$patientDetailsRightSignature->Patient_LastName;
                            $pdfData['case_number'] = $patientDetailsRightSignature->PatientID;
                            $pdfData['case_rate'] = number_format((float)$escPay, 2, '.', '');
                            $pdfData['days_shift'] = $requestData['shift_date'].', '.
                            H::getFormatedTime($requestData['start_time']).' - '.H::getFormatedTime($requestData['end_time']);
                            $pdfData['minimum_wage'] = number_format((float)H::getHelper("RightSignature")->getMinimumWage($facilityDetails->id), 2, '.', '');
                            $pdfData['training_rate'] = number_format((float)H::getHelper("RightSignature")->getTrainingRate($providerDetailsRightSignature->DisciplineName,$facilityDetails->id), 2, '.', '');
                            $pdfData['skill'] = $providerDetailsRightSignature->DisciplineName;
                            $pdfData['prepared_user'] = Auth::user()->name;
                            $pdfData['approved_user'] =$requestData['gp_approved_by'];

                            $filePath = H::getHelper("RightSignature")
                            ->generateRightSignaturePDFTemplete($fileName,$pdfData,"pay_rate_agreement");

                            if(is_file($filePath)) {
                                $documentType = 'pay_rate';
                                $documentTitle = "Pay Rate Agreement";
                                $recipientName = $providerDetailsRightSignature->fullName;
                                $recipientEmail = $pdfEmail;

                                $senderId = Auth::user()->id;
                                $extraData['client_id'] = $patientDetailsRightSignature->PatientID;
                                $extraData['pay_rate']=number_format((float)$escPay, 2, '.', '');
                                $extraData['schedule'] = $requestData['shift_date'].', '.
                                H::getFormatedTime($requestData['start_time']).' - '.H::getFormatedTime($requestData['end_time']);
                                $extraData['approved_by'] =$requestData['gp_approved_by'];
                                $extraJson = json_encode($extraData);

                                H::getHelper("RightSignature")
                                ->sendDocumentRequest(
                                $filePath,0,$entityId,
                                $entityType,$documentType,$documentTitle,
                                $recipientName,$recipientEmail,'',$senderId,$extraJson);
                            }

                        } else {
                            $escPay = $requestData['escalated_pay_rate'];
                            $noteData = [];
                            $note = 'New Pay Rate Agreement not 
                            needed. '.$providerDetailsRightSignature->fullName.'
                                already agreed to the Pay Rate
                                 '.number_format((float)$escPay, 2, '.', '').'
                                for Client '.$patientDetailsRightSignature->PatientID;
                            $noteData['entity'] = 'clinician';
                            $noteData['entity_id'] = $requestData['aoc_provider_id'];
                            $noteData['notes'] =$note;

                            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                        }
                }

                    
                    if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr')) {
                        $lastCoverageTrackerId = DB::table('coverage_tracker')->insertGetId($coverageInsertArr);
                        $this->addRecruitingSchedule(
                            $requestData,
                            $lastCoverageTrackerId,
                            date(CoverageController::DATE_FORMAT,strtotime($requestData['shift_date']))
                        );
                    } else {
                        DB::table('coverage_tracker')->insert($coverageInsertArr);
                    }


                    H::ExcelLog('Source : '.$requestData['source'].PHP_EOL.' Input:' . print_r($coverageInsertArr, true),'saveCoverageShiftTrackerForm.log');
                } else {
                    if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') && isset($requestData['bucket']) && $requestData['bucket']=='map_coverageclinician') {
                        
                        $daysAvailables = explode(",",$requestData['days_available']);
                        $coverageMapRowIds = explode(',',$requestData['selectedCoverageRows']);
                        $shiftType = explode(',',$requestData['shift_type']);

                        


                        foreach($daysAvailables as $k => $daysAvailable) {
                            $coverageNeeds = DB::table('maps_covclin')->select("id","Days_Available")->where('id',$coverageMapRowIds[$k])->get()->toArray();
                            
                            $coverageInsertArr = [];
                            foreach ($coverageNeeds as $key => $value) {
                                $coverageInsertArr[$key] = json_decode(json_encode($value), true);
                                $dateObject = \DateTime::createFromFormat("m/d/Y" , $daysAvailable);
                                $daysAvailable = $dateObject->format("Y-m-d");
                                
                                $id = $coverageInsertArr[$key]['id'];
                                $dbDaysAvailable = explode(",",$coverageInsertArr[$key]['Days_Available']);
                               
                                $coverageInsertArr[$key]['aoc_map_coverageclinician_id'] = $coverageInsertArr[$key]['id'];
                                unset($coverageInsertArr[$key]['id']);
                                unset($coverageInsertArr[$key]['custom_sort_prn']);

                                $coverageInsertArr[$key]['shift_type'] = $shiftType[$k];
                                $coverageInsertArr[$key]['Days_Available']=date(
                                    CoverageController::DATE_FORMAT,strtotime($daysAvailable));


                                $coverageInsertArr[$key]['aoc_provider_id'] = $requestData['aoc_provider_id'];
                                $coverageInsertArr[$key]['aoc_patient_id'] = $requestData['aoc_patient_id'];

                                $coverageInsertArr[$key]['Schedule_Time'] = (
                                    (!empty($requestData['start_time']) &&
                                    !empty($requestData['end_time'])
                                ) ? date(CoverageController::TIME_FORMAT, strtotime($requestData['start_time'])).' - '.
                                date(CoverageController::TIME_FORMAT, strtotime($requestData['end_time'])) : '');
                                $coverageInsertArr[$key]['Hours'] = (
                                    (!empty($requestData['Hours'])) ? $requestData['Hours'] : ''
                                );
                                $coverageInsertArr[$key]['shift_date'] = date(
                                    CoverageController::DATE_FORMAT,
                                    strtotime($daysAvailable)
                                );

                                if(!empty($requestData['clientName'])) {
                                    $clientName = explode(" ",$requestData['clientName']);
                                    $coverageInsertArr[$key]['Client_First_Name'] = $clientName[0];
                                    $coverageInsertArr[$key]['Client_Last_Name'] = $clientName[1];
                                    $coverageInsertArr[$key]['Name'] = $requestData['clientName'];
                                }
                                
                                
                                
                                $coverageInsertArr[$key]['escalated_pay_rate'] = $requestData['escalated_pay_rate'];
                                $coverageInsertArr[$key]['approved_by'] = $requestData['gp_approved_by'];
                                $coverageInsertArr[$key]['gp'] = $requestData['gp'];
                                $coverageInsertArr[$key]['filled_by'] = $requestData['filled_by'];
                                $coverageInsertArr[$key]['date_filled'] = date(CoverageController::DATE_TIME_FORMAT);
                                $coverageInsertArr[$key]['created_by'] = Auth::user()->id;
                                $coverageInsertArr[$key]['updated_by'] = Auth::user()->id;
                                $coverageInsertArr[$key]['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
                                if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                                    if($coverageInsertArr[$key]['aoc_provider_id'] == 0){
                                        $coverageInsertArr[$key]['new_clinician_name'] = $requestData['candidate_name'];
                                    }
                                }
                                $daysAvailableTemp = $coverageInsertArr[$key]['Days_Available'];
                                unset($coverageInsertArr[$key]['Days_Available']);
                                
                                //self::matchTableFiledAndCreate($coverageInsertArr, 'coverage_tracker');
                                if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr')) {
                                    $lastCoverageTrackerId = 0;
                                    foreach ($coverageInsertArr as $coverageInsert) {
                                        $lastCoverageTrackerId = DB::table('coverage_tracker')->insertGetId($coverageInsert);
                                    }
                                    $this->addRecruitingSchedule(
                                        $requestData,
                                        $lastCoverageTrackerId,
                                        date(CoverageController::DATE_FORMAT,strtotime($coverageInsertArr[0]['shift_date']))
                                    );
                                } else {
                                    DB::table('coverage_tracker')->insert($coverageInsertArr);
                                }
                                
                                foreach($dbDaysAvailable as $dbkey => $dbDays){
                                    if(strtotime($dbDays)==strtotime($daysAvailableTemp)) {
                                        unset($dbDaysAvailable[$dbkey]);
                                    }
                                }
                               
                                if(count($dbDaysAvailable)) {
                                    $stringDaysAvailable = implode(",",$dbDaysAvailable);
                                    DB::table('maps_covclin')->where('id',$coverageMapRowIds)->update(['Days_Available'=>$stringDaysAvailable]);
                                } else {
                                    DB::table('maps_covclin')->where('id',$coverageMapRowIds)->delete();
                                }

                                H::ExcelLog('Source : '.$requestData['source'].PHP_EOL.' Input:' . print_r($coverageInsertArr, true),'saveCoverageShiftTrackerForm.log');

                            }
                           
                            
                        }



                        if(LD::checkFeatureFlag('rightsignature-snsform-pdf-trigger') && !empty($requestData['aoc_patient_id']) && !empty($requestData['aoc_provider_id'])) {

                            $providerDetailsRightSignature = MssqlProviders::find($requestData['aoc_provider_id']);
                            $patientDetailsRightSignature = MssqlPatients::find($requestData['aoc_patient_id']);
                            

                            $signedDocuments = DB::table("aoc_documents")
                            ->select("*")
                            ->where("entity_id",$requestData['aoc_provider_id'])
                            ->where("entity_type","provider")
                            ->where("status","executed")->get();

                            $isAlreadySigned = false;

                            foreach($signedDocuments as $signed) {
                                $extraData = json_decode($signed->extra_data,true);
                                $escPay = $requestData['escalated_pay_rate'];
                                if(!empty($extraData) 
                                && (number_format((float)$escPay, 2, '.', '')==$extraData['pay_rate'])
                                && ($patientDetailsRightSignature->PatientID==$extraData['client_id'])) {
                                    $isAlreadySigned = true;
                                }
                            }
                            
                            
                            if(!$isAlreadySigned) {
                                $pprov = $requestData['aoc_provider_id'];
                                $fileName = "pay_rate_provider_".$pprov.'_'.strtotime("now").'.pdf';
                                $entityId = $requestData['aoc_provider_id'];
                                $entityType = 'provider';
                                $providerData = MssqlProviders::find($entityId);
                                $pdfEmail= $providerDetailsRightSignature->AOC_Email;
                                $note = "Pay Rate Agreement Sent to the Provider";

                            
                                $facilityDetails = DB::table("mssql_facilities")
                                ->select("*")
                                ->where("FacilityID",$providerDetailsRightSignature->FacilityID)
                                ->first();
                        
                                $shift = '';
                                if(isset($requestData['shift_date'])) {
                                    $shift.=$requestData['shift_date'];
                                }
                                if(isset($requestData['start_time'])) {
                                    $shift.=", ".H::getFormatedTime($requestData['start_time']);
                                }

                                if(isset($requestData['end_time'])) {
                                    $shift.=" - ".H::getFormatedTime($requestData['end_time']);
                                }

                                $escPay = $requestData['escalated_pay_rate'];
                                $pdfData['emp_name'] = $providerDetailsRightSignature->fullName;
                                $pdfData['client_name'] = $patientDetailsRightSignature->Patient_FirstName.' '.$patientDetailsRightSignature->Patient_LastName;
                                $pdfData['case_number'] = $patientDetailsRightSignature->PatientID;
                                $pdfData['case_rate'] = number_format((float)$escPay, 2, '.', '');
                                $pdfData['days_shift'] = $shift;
                                $pdfData['minimum_wage'] = number_format((float)H::getHelper("RightSignature")
                                ->getMinimumWage($facilityDetails->id), 2, '.', '');
                                $pdfData['training_rate'] = number_format((float)H::getHelper("RightSignature")
                                ->getTrainingRate($providerDetailsRightSignature->DisciplineName,$facilityDetails->id), 2, '.', '');
                                $pdfData['skill'] = $providerDetailsRightSignature->DisciplineName;
                                $pdfData['prepared_user'] = Auth::user()->name;
                                $pdfData['approved_user'] =$requestData['gp_approved_by'];

                                $filePath = H::getHelper("RightSignature")
                                ->generateRightSignaturePDFTemplete($fileName,$pdfData,"pay_rate_agreement");
                                
                                if(is_file($filePath)) {
                                    $escPay = $requestData['escalated_pay_rate'];
                                    $documentType = 'pay_rate';
                                    $documentTitle = "Pay Rate Agreement";
                                    $recipientName = $providerDetailsRightSignature->fullName;
                                    $recipientEmail = $pdfEmail;
        
                                    $senderId = Auth::user()->id;
                                    $extraData['client_id'] = $patientDetailsRightSignature->PatientID;
                                    $extraData['pay_rate']=number_format((float)$escPay, 2, '.', '');
                                    $extraData['schedule'] = $shift;
                                    $extraData['approved_by'] =$requestData['gp_approved_by'];
                                    $extraJson = json_encode($extraData);
        
                                    H::getHelper("RightSignature")
                                    ->sendDocumentRequest(
                                    $filePath,0,$entityId,
                                    $entityType,$documentType,$documentTitle,
                                    $recipientName,$recipientEmail,'',$senderId,$extraJson);
                                }

                            } else {
                                $escPay = $requestData['escalated_pay_rate'];
                                $noteData = [];
                                $note = 'New Pay Rate Agreement not needed. '.$providerDetailsRightSignature->fullName.'
                                    already agreed to the Pay Rate '.number_format((float)$escPay, 2, '.', '').'
                                    for Client '.$patientDetailsRightSignature->PatientID;
                                $noteData['entity'] = 'clinician';
                                $noteData['entity_id'] = $requestData['aoc_provider_id'];
                                $noteData['notes'] =$note;

                                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                            }
                        }






                    } else {
                            $coverageMapRowIds = explode(',',$requestData['selectedCoverageRows']);
                            
                            $coverageNeeds = DB::table('maps_coverageneeds')->select('*')->whereIn('id',$coverageMapRowIds)->get()->toArray();
                            $coverageInsertArr = [];
                            foreach ($coverageNeeds as $key => $value) {
                                $coverageInsertArr[$key] = json_decode(json_encode($value), true);
                                unset($coverageInsertArr[$key]['id']);
                                unset($coverageInsertArr[$key]['last_activity']);
                                unset($coverageInsertArr[$key]['last_activity_by']);
                                unset($coverageInsertArr[$key]['last_activity_date']);

                                $coverageInsertArr[$key]['aoc_provider_id'] = $requestData['aoc_provider_id'];
                                $coverageInsertArr[$key]['aoc_patient_id'] = $requestData['aoc_patient_id'];
                                $coverageInsertArr[$key]['Schedule_Time'] = (
                                    (!empty($requestData['start_time']) &&
                                    !empty($requestData['end_time'])
                                ) ? date(CoverageController::TIME_FORMAT, strtotime($requestData['start_time'])).' - '.
                                date(CoverageController::TIME_FORMAT, strtotime($requestData['end_time'])) : '');
                                $coverageInsertArr[$key]['Hours'] = (
                                    (!empty($requestData['Hours'])) ? $requestData['Hours'] : ''
                                );
                                $coverageInsertArr[$key]['shift_date'] = date(
                                    CoverageController::DATE_FORMAT,
                                    strtotime($coverageInsertArr[$key]['Date'])
                                );
                                unset($coverageInsertArr[$key]['Date']);
                                $coverageInsertArr[$key]['filterDate'] = date(
                                    CoverageController::DATE_FORMAT,
                                    strtotime($coverageInsertArr[$key]['filterDate'])
                                );
                                $coverageInsertArr[$key]['escalated_pay_rate'] = $requestData['escalated_pay_rate'];
                                $coverageInsertArr[$key]['approved_by'] = $requestData['gp_approved_by'];
                                $coverageInsertArr[$key]['gp'] = $requestData['gp'];
                                $coverageInsertArr[$key]['filled_by'] = $requestData['filled_by'];
                                $coverageInsertArr[$key]['date_filled'] = date(CoverageController::DATE_TIME_FORMAT);
                                $coverageInsertArr[$key]['created_by'] = Auth::user()->id;
                                $coverageInsertArr[$key]['updated_by'] = Auth::user()->id;
                                $coverageInsertArr[$key]['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
                                if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                                    if($coverageInsertArr[$key]['aoc_provider_id'] == 0){
                                        $coverageInsertArr[$key]['new_clinician_name'] = $requestData['candidate_name'];
                                    }
                                }
                            }
                            self::matchTableFiledAndCreate($coverageInsertArr, 'coverage_tracker');
                            if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr')) {
                                $lastCoverageTrackerId = 0;
                                foreach ($coverageInsertArr as $coverageInsert) {
                                    $lastCoverageTrackerId = DB::table('coverage_tracker')->insertGetId($coverageInsert);
                                }
                                $this->addRecruitingSchedule(
                                    $requestData,
                                    $lastCoverageTrackerId,
                                    date(CoverageController::DATE_FORMAT,strtotime($coverageInsertArr[0]['shift_date']))
                                );
                            } else {
                                DB::table('coverage_tracker')->insert($coverageInsertArr);
                            }
                            if(LD::checkFeatureFlag('rightsignature-snsform-pdf-trigger') && !empty($requestData['aoc_patient_id']) && !empty($requestData['aoc_provider_id'])) {

                                $providerDetailsRightSignature = MssqlProviders::find($requestData['aoc_provider_id']);
                                $patientDetailsRightSignature = MssqlPatients::find($requestData['aoc_patient_id']);
                                

                                $signedDocuments = DB::table("aoc_documents")
                                ->select("*")
                                ->where("entity_id",$requestData['aoc_provider_id'])
                                ->where("entity_type","provider")
                                ->where("status","executed")->get();

                                $isAlreadySigned = false;

                                foreach($signedDocuments as $signed) {
                                    $extraData = json_decode($signed->extra_data,true);
                                    $escPay = $requestData['escalated_pay_rate'];
                                    if(!empty($extraData) 
                                    && (number_format((float)$escPay, 2, '.', '')==$extraData['pay_rate'])
                                    && ($patientDetailsRightSignature->PatientID==$extraData['client_id'])) {
                                        $isAlreadySigned = true;
                                    }
                                }
                                
                                
                                if(!$isAlreadySigned) {
                                    $pprov = $requestData['aoc_provider_id'];
                                    $fileName = "pay_rate_provider_".$pprov.'_'.strtotime("now").'.pdf';
                                    $entityId = $requestData['aoc_provider_id'];
                                    $entityType = 'provider';
                                    $providerData = MssqlProviders::find($entityId);
                                    $pdfEmail= $providerDetailsRightSignature->AOC_Email;
                                    $note = "Pay Rate Agreement Sent to the Provider";

                                
                                    $facilityDetails = DB::table("mssql_facilities")
                                    ->select("*")
                                    ->where("FacilityID",$providerDetailsRightSignature->FacilityID)
                                    ->first();
                            
                                    $shift = '';
                                    if(isset($requestData['shift_date'])) {
                                        $shift.=$requestData['shift_date'];
                                    }
                                    if(isset($requestData['start_time'])) {
                                        $shift.=", ".H::getFormatedTime($requestData['start_time']);
                                    }

                                    if(isset($requestData['end_time'])) {
                                        $shift.=" - ".H::getFormatedTime($requestData['end_time']);
                                    }

                                    $escPay = $requestData['escalated_pay_rate'];
                                    $pdfData['emp_name'] = $providerDetailsRightSignature->fullName;
                                    $pdfData['client_name'] = $patientDetailsRightSignature->Patient_FirstName.' '.$patientDetailsRightSignature->Patient_LastName;
                                    $pdfData['case_number'] = $patientDetailsRightSignature->PatientID;
                                    $pdfData['case_rate'] = number_format((float)$escPay, 2, '.', '');
                                    $pdfData['days_shift'] = $shift;
                                    $pdfData['minimum_wage'] = number_format((float)H::getHelper("RightSignature")
                                    ->getMinimumWage($facilityDetails->id), 2, '.', '');
                                    $pdfData['training_rate'] = number_format((float)H::getHelper("RightSignature")
                                    ->getTrainingRate($providerDetailsRightSignature->DisciplineName,$facilityDetails->id), 2, '.', '');
                                    $pdfData['skill'] = $providerDetailsRightSignature->DisciplineName;
                                    $pdfData['prepared_user'] = Auth::user()->name;
                                    $pdfData['approved_user'] =$requestData['gp_approved_by'];

                                    $filePath = H::getHelper("RightSignature")
                                    ->generateRightSignaturePDFTemplete($fileName,$pdfData,"pay_rate_agreement");
                                    
                                    if(is_file($filePath)) {
                                        $escPay = $requestData['escalated_pay_rate'];
                                        $documentType = 'pay_rate';
                                        $documentTitle = "Pay Rate Agreement";
                                        $recipientName = $providerDetailsRightSignature->fullName;
                                        $recipientEmail = $pdfEmail;
            
                                        $senderId = Auth::user()->id;
                                        $extraData['client_id'] = $patientDetailsRightSignature->PatientID;
                                        $extraData['pay_rate']=number_format((float)$escPay, 2, '.', '');
                                        $extraData['schedule'] = $shift;
                                        $extraData['approved_by'] =$requestData['gp_approved_by'];
                                        $extraJson = json_encode($extraData);
            
                                        H::getHelper("RightSignature")
                                        ->sendDocumentRequest(
                                        $filePath,0,$entityId,
                                        $entityType,$documentType,$documentTitle,
                                        $recipientName,$recipientEmail,'',$senderId,$extraJson);
                                    }

                                } else {
                                    $escPay = $requestData['escalated_pay_rate'];
                                    $noteData = [];
                                    $note = 'New Pay Rate Agreement not needed. '.$providerDetailsRightSignature->fullName.'
                                        already agreed to the Pay Rate '.number_format((float)$escPay, 2, '.', '').'
                                        for Client '.$patientDetailsRightSignature->PatientID;
                                    $noteData['entity'] = 'clinician';
                                    $noteData['entity_id'] = $requestData['aoc_provider_id'];
                                    $noteData['notes'] =$note;

                                    H::getHelper("ActivityNotes")->setActivityNotes($noteData);
                                }
                            }
                        
                            DB::table('maps_coverageneeds')->select('*')->whereIn('id',$coverageMapRowIds)->delete();
                            H::ExcelLog('Source : '.$requestData['source'].PHP_EOL.' Input:' . print_r($coverageInsertArr, true),'saveCoverageShiftTrackerForm.log');
        

                    }
               }

            } else {
                $coverageInsertArr = [];
                $coverageInsertArr['aoc_patient_id'] = $requestData['aoc_patient_id'];
                $coverageInsertArr['aoc_provider_id'] = $requestData['aoc_provider_id'];
                $coverageInsertArr['shift_date'] = date(
                    CoverageController::DATE_FORMAT,
                    strtotime($requestData['shift_date'])
                );
                $coverageInsertArr['Schedule_Time'] = date(
                    CoverageController::TIME_FORMAT,
                    strtotime($requestData['start_time'])
                ).' - '.
                date(
                    CoverageController::TIME_FORMAT,
                    strtotime($requestData['end_time'])
                );
                $coverageTrackerDetail = DB::table('maps_coverageneeds')->where('shift_date',$coverageInsertArr['shift_date'])->where('Schedule_Time',$coverageInsertArr['Schedule_Time'])->where('aoc_patient_id',$coverageInsertArr['aoc_patient_id'])->first();
                if(!empty($coverageTrackerDetail)) {
                    return response()->json(['success' => 0, 'message' => 'This shift is already on the Coverage Needs map. Please remove coverage shifts from the map instead.']);
                }
                $coverageInsertArr['Name'] = $requestData['clientName'];
                $coverageInsertArr['approved_by'] = $requestData['gp_approved_by'];
                $coverageInsertArr['gp'] = $requestData['gp'];
                $coverageInsertArr['Hours'] = $requestData['Hours'];
                $coverageInsertArr['escalated_pay_rate'] = $requestData['escalated_pay_rate'];
                $coverageInsertArr['filled_by'] = $requestData['filled_by'];
                $coverageInsertArr['date_filled'] = date(CoverageController::DATE_TIME_FORMAT);
                $coverageInsertArr['created_by'] = Auth::user()->id;
                $coverageInsertArr['updated_by'] = Auth::user()->id;
                $coverageInsertArr['created_at'] = date(CoverageController::DATE_TIME_FORMAT);
                $clientDetails = DB::table('mssql_patients')->select('mssql_patients.Patient_FirstName','mssql_patients.Patient_LastName')->where('mssql_patients.id',$requestData['aoc_patient_id'])->first();
                $coverageInsertArr['Client_First_Name'] = $clientDetails->Patient_FirstName;
                $coverageInsertArr['Client_Last_Name'] = $clientDetails->Patient_LastName;
                $coverageInsertArr['created_manually'] = 1;
                self::matchTableFiledAndCreate($coverageInsertArr, 'coverage_tracker');
                DB::table('coverage_tracker')->insert($coverageInsertArr);
                H::ExcelLog('Source : '.$requestData['source'].PHP_EOL.'Input:' . print_r($coverageInsertArr, true),'saveCoverageShiftTrackerForm.log');
            }
            return response()->json(['success' => 1,'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            H::ExcelLog('Save CoverageShiftTrackerForm:'.$ex->getMessage(),'saveCoverageShiftTrackerForm.log');
            return response()->json(['success' => 0, 'message' => CoverageController::ERROR_MSG.$ex->getMessage()]);
        }
    }
    public function getCoverageTrackerActivity(Request $request) {
        $comments = H::getHelper("ActivityNotes")->getCoverageTrackerActivity($request->input("id"));
        $data = [];
        $merge = [];
        foreach($comments as $note) {
            $data["activity"][] = ['type'=>'activity','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes,'created_at'=>$note->created_at];
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });
        return view('components.trackerhistory',['data'=>$merge]);
    }

    public function getEditCoverageTrackerRecord(Request $request){
        $id = $request->input('id');
        $approvedByList = H::getHelper('ConstantHelper')->getUsersByRoles(['Client Care Coordinator','Coverage Coordinator','Client Care Manager','Director of Branch Operations','Regional Director of Operations','State VP','Executive Team']);
        //Sort by name
        $keys = array_column($approvedByList, 'text');
        array_multisort($keys, SORT_ASC, $approvedByList);
        $coverageTrackerDetail = DB::table('coverage_tracker')->where('id',$id)->first();
        $coverageTrackerDetail = H::objToArr($coverageTrackerDetail);
        $coverageTrackerDetail['start_time'] =  $coverageTrackerDetail['end_time'] =  $coverageTrackerDetail['candidate_name'] = $coverageTrackerDetail['clientName'] = '';
        if($coverageTrackerDetail['Schedule_Time'] != '') {
            $scheduleTimeArr = explode("-",$coverageTrackerDetail['Schedule_Time']);
            $coverageTrackerDetail['start_time'] =  H::getFormatedTime($scheduleTimeArr[0]);
            $coverageTrackerDetail['end_time'] =  H::getFormatedTime($scheduleTimeArr[1]);
        }
        if($coverageTrackerDetail['aoc_provider_id'] != '' && $coverageTrackerDetail['aoc_provider_id'] > 0) {
            $candidateDetails = DB::table('mssql_providers')->select('mssql_providers.FirstName','mssql_providers.LastName','ClinicianID')->where('mssql_providers.id',$coverageTrackerDetail['aoc_provider_id'])->first();
            $coverageTrackerDetail['candidate_name'] = $candidateDetails->FirstName.' '.$candidateDetails->LastName;
            if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker')){
                $coverageTrackerDetail['ClinicianID'] = $candidateDetails->ClinicianID;
            }
        }else{
            $coverageTrackerDetail['candidate_name'] = $coverageTrackerDetail['new_clinician_name'];
        }
        if($coverageTrackerDetail['aoc_patient_id'] != '' && $coverageTrackerDetail['aoc_patient_id'] > 0) {
            $clientDetails = DB::table('mssql_patients')->select('mssql_patients.Patient_FirstName','mssql_patients.Patient_LastName')->where('mssql_patients.id',$coverageTrackerDetail['aoc_patient_id'])->first();
            $coverageTrackerDetail['clientName'] = $clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName;
        }

        if(LD::checkFeatureFlag('coverage-tracker-view-action')){
            $isViewMode = false;
            if($request->input('isViewMode') == 'true'){
                $isViewMode = true;
            }
            return view('ccc.coverageshifttrackerform',['approvedByList' => $approvedByList,'coverageTrackerDetail' => $coverageTrackerDetail,'isViewMode'=>$isViewMode]);
        }else{
            return view('ccc.coverageshifttrackerform',['approvedByList' => $approvedByList,'coverageTrackerDetail' => $coverageTrackerDetail]);
        }

    }

    public function updateCoverageShiftTracker(Request $request) {
        try {
            $requestData = $request->all();
            $coverageUpdateArr = [];
            $coverageUpdateArr['aoc_patient_id'] = $requestData['aoc_patient_id'];
            $coverageUpdateArr['aoc_provider_id'] = $requestData['aoc_provider_id'];
            $coverageUpdateArr['shift_date'] = date(
                CoverageController::DATE_FORMAT,
                strtotime($requestData['shift_date'])
            );
            $coverageUpdateArr['Schedule_Time'] = date(
                CoverageController::TIME_FORMAT,
                strtotime($requestData['start_time'])
            ) . ' - '. date(CoverageController::TIME_FORMAT, strtotime($requestData['end_time']));
            $coverageTrackerDetail = DB::table('maps_coverageneeds')->where('shift_date',$coverageUpdateArr['shift_date'])->where('Schedule_Time',$coverageUpdateArr['Schedule_Time'])->where('aoc_patient_id',$coverageUpdateArr['aoc_patient_id'])->first();
            if(!empty($coverageTrackerDetail)) {
                return response()->json(['success' => 0, 'message' => 'This shift is already on the Coverage Needs map. Please remove coverage shifts from the map instead.']);
            }
            $coverageUpdateArr['Name'] = $requestData['clientName'];
            $coverageUpdateArr['approved_by'] = $requestData['gp_approved_by'];
            $coverageUpdateArr['gp'] = $requestData['gp'];
            $coverageUpdateArr['Hours'] = $requestData['Hours'];
            $coverageUpdateArr['escalated_pay_rate'] = $requestData['escalated_pay_rate'];
            if(LD::checkFeatureFlag('ticket-6281_edit_intakecoordinator')) {
                $coverageUpdateArr['created_by'] = $requestData['filled_by']; 
            }
            $coverageUpdateArr['filled_by'] = $requestData['filled_by'];
            $coverageUpdateArr['updated_by'] = Auth::user()->id;
            $coverageUpdateArr['updated_at'] = date(CoverageController::DATE_TIME_FORMAT);
            $clientDetails = DB::table('mssql_patients')->select('mssql_patients.Patient_FirstName','mssql_patients.Patient_LastName')->where('mssql_patients.id',$requestData['aoc_patient_id'])->first();
            $coverageUpdateArr['Client_First_Name'] = $clientDetails->Patient_FirstName;
            $coverageUpdateArr['Client_Last_Name'] = $clientDetails->Patient_LastName;
            //TICKET-3888
            $existingCoverage = [];
            if(LD::checkFeatureFlag('coverage-edit-delete-fix-ccr')) {
                $existingCoverage = DB::table('coverage_tracker')
                ->where('id',$requestData['coverage_tracker_id'])->first();
            }
            DB::table('coverage_tracker')->where('id',$requestData['coverage_tracker_id'])->update($coverageUpdateArr);
            if(LD::checkFeatureFlag('coverage-edit-delete-fix-ccr')) {
                if($existingCoverage->aoc_provider_id != $requestData['aoc_provider_id']){
                    try {
                        //Set CCR outcome to Cancelled
                        H::ExcelLog('Outcome Set to Cancelled for coverage id
                        '.$requestData['coverage_tracker_id'],'updateCoverageShiftTracker.log');
                        $scheduleData['outcome'] = 'Cancelled';
                        $scheduleData['coverage_id'] = 0;
                        RecruitingSchedule::where('coverage_id',$requestData['coverage_tracker_id'])
                        ->update($scheduleData);
                        $clientId = $existingCoverage->aoc_patient_id;
                        H::ExcelLog('clientId-'.$clientId,'updateCoverageShiftTracker.log');
                        //Check future coverage shift of existing nurse/clinician
                        $existingProviderId = $existingCoverage->aoc_provider_id;
                        H::ExcelLog('existingProviderId-'.$existingProviderId,'updateCoverageShiftTracker.log');
                        $this->coverageCcrProcess($clientId,$existingProviderId);
                        //Check future coverage shift of new nurse/clinician
                        $newProviderId = $requestData['aoc_provider_id'];
                        H::ExcelLog('newProviderId-'.$newProviderId,'updateCoverageShiftTracker.log');
                        $this->coverageCcrProcess($clientId,$newProviderId);
                    } catch (\Throwable $th) {
                        H::ExcelLog('Throwable.'.$th->getMessage(),'updateCoverageShiftTracker.log');
                    }
                }
            }
            return response()->json(['success' => 1,'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            H::ExcelLog('Save CoverageShiftTrackerForm:'.$ex->getMessage(),'updateCoverageShiftTracker.log');
            return response()->json(['success' => 0, 'message' => CoverageController::ERROR_MSG]);
        }
    }
    private function coverageCcrProcess($clientId,$providerId) {
        H::ExcelLog('Inside','coverageCcrProcess.log');
        //if there is any future coverage shift for clinician/client combination
        $futureCoverageTracker = DB::table('coverage_tracker')
        ->where('aoc_provider_id',$providerId)
        ->where('aoc_patient_id',$clientId)
        ->whereDate("coverage_tracker.shift_date", ">",date('Y-m-d'))->first();
        if(!empty($futureCoverageTracker)) {

            H::ExcelLog('futureCoverageTracker','coverageCcrProcess.log');
            H::ExcelLog(print_r($futureCoverageTracker, true),'coverageCcrProcess.log');
            $pastDate = date('Y-m-d',strtotime('-30 days'));
            $currentDate = date('Y-m-d');
            H::ExcelLog('pastDate='.$pastDate,'coverageCcrProcess.log');
            H::ExcelLog('currentDate='.$currentDate,'coverageCcrProcess.log');
            //if the nurse has worked with the client in the last 30 days
            $lastScheduleData = DB::table("mssql_schedulemaster");
            $lastScheduleData->leftJoin("mssql_patients", function ($query) {
                $query->on("mssql_patients.PatientID", "=", "mssql_schedulemaster.PatientID");
            });
            $lastScheduleData->leftJoin("mssql_providers", function ($query) {
                $query->on("mssql_providers.ProviderID", "=", "mssql_schedulemaster.ProviderID");
            });
            $lastScheduleData->where("mssql_patients.id", $clientId)
            ->where("mssql_providers.id", $providerId)
            ->whereBetween("mssql_schedulemaster.ScheduleDate",[$pastDate, $currentDate]);
            $lastScheduleDataExist =  $lastScheduleData->count();
            H::ExcelLog('lastScheduleDataExist='.$lastScheduleDataExist,'coverageCcrProcess.log');

            if($lastScheduleDataExist == 0) {
                $scheduleData['aoc_patient_id'] = $clientId;
                $scheduleData['aoc_provider_id'] = $providerId;
                $scheduleData['escalated_pay_rate'] = $futureCoverageTracker->escalated_pay_rate;
                $scheduleData['gp'] = $futureCoverageTracker->escalated_pay_rate;
                $scheduleData['gp_approved_by'] = $futureCoverageTracker->approved_by;
                $scheduleData['need_to_add_ccr'] = 1;
                H::ExcelLog(print_r($scheduleData, true),'coverageCcrProcess.log');
                $this->addRecruitingSchedule(
                    $scheduleData,
                    $futureCoverageTracker->id,
                    date(CoverageController::DATE_FORMAT,strtotime($futureCoverageTracker->shift_date))
                );
            }
        }
    }
    public function matchTableFiledAndCreate($arrayField, $tableName){
        $tableColumns = Schema::getColumnListing($tableName);
        $tableName2 = 'coverage_tracker_log';
        $missingColumns = array_diff(array_keys($arrayField[0]), $tableColumns);
        foreach ($missingColumns as $missingColumn) {
            Schema::table($tableName, function ($table) use ($missingColumn) {
                $table->string($missingColumn)->nullable();
            });

            Schema::table($tableName2, function ($table) use ($missingColumn) {
                $table->string($missingColumn)->nullable();
            });
        }
    }

    public function makeCoverageCompleted(Request $request){
        if(!empty($request->id)){
            $updateAry = [
                'completed_by' => auth()->user()->id,
                'completed_at' => now(),
            ];
            DB::table('coverage_tracker')->where('id', $request->id)->update($updateAry);
            return response()->json(['success'=>1]);
        }
    }
}
