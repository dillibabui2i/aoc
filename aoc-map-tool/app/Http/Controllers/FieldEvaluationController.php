<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\FieldEvaluation;
use App\Helpers\H;
use App\MssqlProviders;
use App\Helpers\LD;
use DataTables;
use App\RightSignatureDocuments;

class FieldEvaluationController extends Controller
{
    public function getUnsentDocuments(Request $request)
    {

        if ($request->ajax()) {
            $data = H::getHelper("ClinicianCareManager")->getUnSentDocuments($request->input("type"), $request);
            return Datatables::of($data)
                ->addColumn('Action', function ($row) {
                    $btn = '';
                    return $btn;
                })->escapeColumns('Action')->make(true);
        }
    }

    public function reloadUnsentDocuments(Request $request)
    {

        return json_encode(["counter" => H::getHelper("ClinicianCareManager")->getUnSentDocuments($request->input("type"), $request, 1), "type" => $request->input("type")]);
    }

    private function getFieldEvaluation($request, $columns, $requestFrom)
    {
        $filters = [];
        $filtersInput = $request->input('filters');
        if ($filtersInput) {
            foreach ($filtersInput as $key => $value) {
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

        if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
            $q = FieldEvaluation::select(
                    'field_evaluations.id',
                    'field_evaluations.aoc_provider_id',
                    'mssql_facilities.Facilityname as BranchName',
                    'mssql_providers.ClinicianID',
                    'mssql_providers.ProviderName',
                    'mssql_providers.DisciplineName',
                    'field_evaluations.created_at as fcreated_at',
                    'users.name',
                    'field_evaluations.status',
                    DB::raw("DATE_FORMAT(IF(
                                (
                                    mssql_providers.RehireDate = '0000-00-00' ||
                                    mssql_providers.RehireDate = '1900-01-01' ||
                                    mssql_providers.RehireDate = '' ||
                                    mssql_providers.RehireDate is null
                                ),
                                mssql_providers.HiredDate,
                                coalesce(mssql_providers.RehireDate, mssql_providers.HiredDate)
                            ),'".config('constant')['mysqlsearchdateformat']."') as HiredDate")
                )
                ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')
                ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager');
        } else {
            $q = FieldEvaluation::select(
                'field_evaluations.id',
                'field_evaluations.aoc_provider_id',
                'mssql_facilities.Facilityname as BranchName',
                'mssql_providers.ClinicianID',
                'mssql_providers.ProviderName',
                'mssql_providers.DisciplineName',
                'field_evaluations.created_at as fcreated_at',
                'users.name',
                'field_evaluations.status',
                DB::raw("DATE_FORMAT(mssql_providers.HiredDate,'".config('constant')['mysqlsearchdateformat']."') as HiredDate")
            )
            ->leftJoin('mssql_providers', 'aoc_provider_id', '=', 'mssql_providers.id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager');
        }

        $q->addSelect(
            DB::raw("(select IF(aoc_documents.status='executed','Signed','Sent') from aoc_documents where aoc_documents.evaluation_id = field_evaluations.id and aoc_documents.entity_type='provider' and (aoc_documents.document_type='field_eval' OR aoc_documents.document_type='therapy_eval') order by id desc limit 1 ) as document_status")
        );
        $q->addSelect(
            DB::raw("(select document_filename from aoc_documents where aoc_documents.evaluation_id = field_evaluations.id and aoc_documents.entity_type='provider' and (aoc_documents.document_type='field_eval' OR aoc_documents.document_type='therapy_eval') order by id desc limit 1 ) as document_filename")
        );

        if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
            $q->addSelect(DB::raw('YEAR(field_evaluations.created_at) as evaluation_year'));
        }
        if (!empty($filters)) {
            if (isset($filters['signed']) &&  !isset($filters['status'])) {
                $filters['status'] = array_column(H::getHelper('ConstantHelper')->getFieldEvaluationStatus(), "value");
            } else if (isset($filters['signed']) &&  isset($filters['status'])) {
                $filters['status'][] = 'Completed';
            } else {
                if (!isset($filters['status'])) {
                    $q->where("field_evaluations.status", "!=", "Completed");
                }
            }

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
                $q->whereIn('field_evaluations.status', $filters['status']);
            }
            if (isset($filters['discipline'])) {
                $q->whereIn('mssql_providers.DisciplineName', $filters['discipline']);
            }
            if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
                if (isset($filters['evaluation_year'])) {
                    $q->whereIn(DB::raw('YEAR(field_evaluations.created_at)'), $filters['evaluation_year']);
                }
            }
        } else {
            $q->where("field_evaluations.status", "!=", "Completed");
        }
        if (LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
            $q->whereNested(function ($q) {
                $q->where('field_evaluations.is_deleted', 0)
                    ->orWhereNull('field_evaluations.is_deleted');
            });
        }
        $q->where('mssql_providers.is_deleted',0);
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function ($q) use ($search) {
                $q->where('mssql_facilities.Facilityname', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.ClinicianID', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_providers.ProviderName', 'LIKE', "%{$search}%")
                    ->orWhere('users.name', 'LIKE', "%{$search}%")
                    ->orWhere('field_evaluations.status', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('DATE_FORMAT(field_evaluations.created_at, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
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

    public function list(Request $request)
    {
        if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
            $columns = [
                ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
                ['name' => 'BranchName', 'label' => 'Branch', 'width' => '10%'],
                ['name' => 'ClinicianID', 'label' => 'Clinician', 'width' => '15%'],
                ['name' => 'ProviderName', 'label' => 'Provider', 'width' => '15%'],
                ['name' => 'DisciplineName', 'label' => 'Discipline', 'width' => '10%'],
                ['name' => 'HiredDate', 'label' => 'Hire Date', 'width' => '10%'],
                ['name' => 'fcreated_at', 'label' => 'Date Created', 'width' => '10%'],
                ['name' => 'evaluation_year', 'label' => 'Evaluation Year', 'width' => '10%'],
                ['name' => 'name', 'label' => 'Manager', 'width' => '15%'],
                ['name' => 'status', 'label' => 'Status', 'width' => '10%'],
                ['name' => 'document_status', 'label' => 'Document Status', 'width' => '10%'],
            ];
        }else{
            $columns = [
                ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
                ['name' => 'BranchName', 'label' => 'Branch', 'width' => '10%'],
                ['name' => 'ClinicianID', 'label' => 'Clinician', 'width' => '15%'],
                ['name' => 'ProviderName', 'label' => 'Provider', 'width' => '15%'],
                ['name' => 'DisciplineName', 'label' => 'Discipline', 'width' => '10%'],
                ['name' => 'HiredDate', 'label' => 'Hire Date', 'width' => '10%'],
                ['name' => 'fcreated_at', 'label' => 'Date Created', 'width' => '10%'],
                ['name' => 'name', 'label' => 'Manager', 'width' => '15%'],
                ['name' => 'status', 'label' => 'Status', 'width' => '10%'],
                ['name' => 'document_status', 'label' => 'Document Status', 'width' => '10%'],
            ];
        }
        if ($request->ajax()) {
            list($data, $totalData, $totalFiltered) = $this->getFieldEvaluation($request, $columns, 'list');
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }
        return view('fieldEvaluation.grid', ['columns' => $columns]);
    }

    private function DataProcess($data)
    {
        foreach ($data as $key => $value) {
            $value->fcreated_at = H::getFormatedDate($value->fcreated_at);
            $value->document_status = (empty($value->document_status) ? 'Not Sent' : $value->document_status);
        }
        return $data;
    }

    public function edit(Request $request)
    {
        $fieldEvaluationData = FieldEvaluation::find($request->input('id'));
        $clinicianData = DB::table("mssql_providers")->select("mssql_providers.*", "mssql_facilities.Facilityname as BranchName",DB::raw("mssql_facilities.State as branchState"))->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')->where('mssql_providers.id', $fieldEvaluationData->aoc_provider_id)->where('mssql_providers.is_deleted',0)->first();
        return view('fieldEvaluation.form', ['fieldEvaluationData' => $fieldEvaluationData, 'clinicianData' => $clinicianData]);
    }

    public function save(Request $request)
    {
        $userId = Auth::user()->id;
        H::ExcelLog("new Request from " . Auth::user()->name,'fieldEvaluationUpdate.log');
        H::ExcelLog(print_r($request->all(),true),'fieldEvaluationUpdate.log');
       
        try {
            if ($request->input('id') != '') {
                $data = $request->input('ratings');
                if (!empty($request->input('comments'))) {
                    $data['comments'] = $request->input('comments');
                }
                if (!empty($request->input('additional_comments'))) {
                    $data['additional_comments'] = $request->input('additional_comments');
                }
                if (!empty($request->input('productivity'))) {
                    $data['productivity'] = $request->input('productivity');
                }
                if (!empty($request->input('ie_re_time'))) {
                    $data['ie_re_time'] = $request->input('ie_re_time');
                }
                if (!empty($request->input('rv_sv_dc_time'))) {
                    $data['rv_sv_dc_time'] = $request->input('rv_sv_dc_time');
                }
                if (!empty($request->input('goals'))) {
                    $data['goals'] = $request->input('goals');
                }
                if ($request->input("action") == 'save') {
                    $data['status'] = 'Edited';
                }
                $data['updated_by'] = $userId;

                FieldEvaluation::where('id', $request->input('id'))->update($data);
               
                if(LD::checkFeatureFlag('field-evaluation-co-send-evaluation')) {
                    if(empty($request->input("is_therapy_evaluation")) && ($request->input("action") == 'submit') && ($request->input("branch_state") == 'CO') ) {
                       
                        if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
                            $fEvaluation = DB::table('field_evaluations')->select(
                                'mssql_providers.ProviderID',
                                'mssql_providers.External_Email',
                                'mssql_providers.DisciplineName',
                                DB::raw("IF(
                                    (
                                        mssql_providers.RehireDate = '0000-00-00' ||
                                        mssql_providers.RehireDate = '1900-01-01' ||
                                        mssql_providers.RehireDate = '' ||
                                        mssql_providers.RehireDate is null
                                    ),
                                    mssql_providers.HiredDate,
                                    coalesce(mssql_providers.RehireDate, mssql_providers.HiredDate)
                                ) as HiredDate"),
                                'mssql_providers.created_at as provider_created_at',
                                'field_evaluations.*',
                                'mssql_facilities.FacilityID',
                                'mssql_facilities.FacilityName',
                                'mssql_providers.ClinicianID',
                                DB::raw("DATE_FORMAT(field_evaluations.created_at,'%m/%d/%Y') as fcreated_at"),
                                'users.name',
                                DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),
                                'field_evaluations.status'
                            )
                            ->leftJoin('mssql_providers', 'field_evaluations.aoc_provider_id', '=', 'mssql_providers.id')
                            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                            ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager')
                            ->where('mssql_providers.id',$request->input("provider_id"));
                            if (LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
                                $fEvaluation->whereNested(function ($q) {
                                    $q->where('field_evaluations.is_deleted', 0)
                                        ->orWhereNull('field_evaluations.is_deleted');
                                });
                            }
                            $fEvaluation = $fEvaluation->first();
                        } else {
                            $fEvaluation = DB::table('field_evaluations')->select(
                                'mssql_providers.ProviderID',
                                'mssql_providers.External_Email',
                                'mssql_providers.DisciplineName',
                                'mssql_providers.HiredDate',
                                'mssql_providers.created_at as provider_created_at',
                                'field_evaluations.*',
                                'mssql_facilities.FacilityID',
                                'mssql_facilities.FacilityName',
                                'mssql_providers.ClinicianID',
                                DB::raw("DATE_FORMAT(field_evaluations.created_at,'%m/%d/%Y') as fcreated_at"),
                                'users.name',
                                DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),
                                'field_evaluations.status'
                            )
                            ->leftJoin('mssql_providers', 'field_evaluations.aoc_provider_id', '=', 'mssql_providers.id')
                            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                            ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager')
                            ->where('mssql_providers.id',$request->input("provider_id"));
                            if (LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
                                $fEvaluation->whereNested(function ($q) {
                                    $q->where('field_evaluations.is_deleted', 0)
                                        ->orWhereNull('field_evaluations.is_deleted');
                                });
                            }
                            $fEvaluation = $fEvaluation->first();
                        }


                        H::ExcelLog('Sending Evaluation for ' . $fEvaluation->clinicianName . ' to ' . $fEvaluation->External_Email,'sendFieldEvaluations.log');

                        $clinicianName = isset($fEvaluation->clinicianName)?$fEvaluation->clinicianName:'';
                        $disciplineName = isset($fEvaluation->DisciplineName)?$fEvaluation->DisciplineName:'';

                        $PDFData['name']=$clinicianName;

                        $hireDate = isset($fEvaluation->HiredDate)?$fEvaluation->HiredDate:'';
                        $PDFData['hiredate']=H::getFormatedDate($hireDate);
                        $PDFData['jobtitle']=$disciplineName;

                        $provderCreatedAt = isset($fEvaluation->provider_created_at)?$fEvaluation->provider_created_at:'';
                        //$PDFData['review_period']=H::getFormatedDate(date('Y-m-d', strtotime('first day of january previous year'))) . ' - ' . H::getFormatedDate(date('Y-m-d', strtotime('last day of december previous year')));
						$PDFData['review_period'] = H::getFormatedDate(
                            date('Y', strtotime("-1 year")) .'-'.
                            date('m-d', strtotime($fEvaluation->HiredDate))) . ' - ' .
                            H::getFormatedDate(date('Y') .'-'.
                            date('m-d', strtotime("-1 day", strtotime($fEvaluation->HiredDate)))
                        );
                        //$PDFData['review_period'] = '09/01/2022 - 08/31/2023';
                        
                        if(LD::checkFeatureFlag("rightsignature_ccm_manager")) {
                            $PDFData['manager'] =  H::getHelper("RightSignature")->getCCMFromProvider($fEvaluation->ProviderID,'2022-01-04','2023-01-03');
                        } else {
                            $PDFData['manager'] = isset($fEvaluation->name)?$fEvaluation->name:'';
                        }
                    
                        $fcreated_at = isset($fEvaluation->fcreated_at)?$fEvaluation->fcreated_at:'';
                        $PDFData['createdDate'] = $fcreated_at;

                        $PDFData['job_knowledge'] = $fEvaluation->item1_score;
                        $PDFData['job_knowledge_comment'] = $fEvaluation->item1_note;
                        $PDFData['work_quality'] = $fEvaluation->item2_score;
                        $PDFData['work_quality_commnet'] = $fEvaluation->item2_note;
                        $PDFData['attendance'] = $fEvaluation->item3_score;
                        $PDFData['attendance_comment'] = $fEvaluation->item3_note;
                        $PDFData['professionalism'] = $fEvaluation->item4_score;
                        $PDFData['professionalism_comment'] = $fEvaluation->item4_note;
                        //$PDFData['general_comment'] = $fEvaluation->comments;
                            
                    
                        $fileName = 'field_evaluation_'.$fEvaluation->aoc_provider_id.'_'.strtotime("now").'.pdf';
                        $template = 'fieldevaluation';

                        $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName,$PDFData,$template);
                        
                        if(is_file($filePath)) {
                            $documentType = 'field_eval';
                            $documentTitle = "Performance Evaluation";
                            $recipientName = $PDFData['name'];
                            $recipientEmail = isset($fEvaluation->External_Email)?$fEvaluation->External_Email:'';

                            $sendDocumentResponse = H::getHelper("RightSignature")->sendDocumentRequest($filePath,0,$fEvaluation->aoc_provider_id,"provider",$documentType,$documentTitle,$recipientName,$recipientEmail,$fEvaluation->id);
                            if (!empty($sendDocumentResponse)) {
                                FieldEvaluation::where('id', $request->input('id'))->update(['status' => '1st Attempt','updated_by' => $userId]);
                            }
                        }
                    } else {
                        if(LD::checkFeatureFlag("rightsignature-send-performance-evaluation")) {
                            if(!empty($request->input("is_therapy_evaluation")) && $request->input("is_therapy_evaluation")==1 && ($request->input("action") == 'submit')) {
                                $providerData = DB::table("mssql_providers")->select("mssql_providers.*", "mssql_facilities.Facilityname as BranchName")
                                ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                                ->where('mssql_providers.id',$request->input("provider_id"))
                                ->where('mssql_providers.is_deleted',0)->first();
                                $managerData = [];
                                $evData = FieldEvaluation::where('id', $request->input('id'))->first();
                                if (!empty($evData->manager)) {
                                    $managerData = DB::table("users")->where("id", $evData->manager)->first();
                                }

                                if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
                                    $providerData->HiredDate = (
                                        !empty($providerData->RehireDate) &&
                                        $providerData->RehireDate != '0000-00-00' &&
                                        $providerData->RehireDate != '1900-01-01'
                                    ) ? $providerData->RehireDate : $providerData->HiredDate;
                                }

                                $PDFData['name'] = $providerData->FirstName . ' ' . $providerData->LastName;
                                $PDFData['hiredate'] = H::getFormatedDate($providerData->HiredDate);
                                $PDFData['jobtitle'] = $providerData->DisciplineName;
                                //$PDFData['review_period']=H::getFormatedDate($providerData->created_at).' to '.H::getFormatedDate($providerData->created_at);
                                if(LD::checkFeatureFlag("rightsignature_ccm_manager")) {
                                    $PDFData['manager'] =  H::getHelper("RightSignature")->getCCMFromProvider($providerData->ProviderID,'2022-01-04','2023-01-03');
                                } else {
                                    $PDFData['manager'] = isset($managerData->name) ? $managerData->name : '';
                                }
                                
                                $PDFData['createdDate'] = H::getFormatedDate($evData->created_at);
                                $PDFData['review_period'] = H::getFormatedDate(date('Y', strtotime("-1 year")) . '-' . date('m-d', strtotime($providerData->HiredDate))) . ' - ' . H::getFormatedDate(date('Y') .'-'. date('m-d', strtotime("-1 day", strtotime($providerData->HiredDate))));
                                $PDFData['head_productivity'] = $request->input("productivity");
                                $PDFData['ie_re_time'] = $request->input("ie_re_time");
                                $PDFData['rv_sv_dc_time'] = $request->input("rv_sv_dc_time");
                                $PDFData['BranchName'] = $providerData->BranchName;
                                $ratings = $request->input("ratings");
                                $PDFData['clinical_quality'] = $ratings["item1_score"];
                                $PDFData['communication'] = $ratings["item2_score"];
                                $PDFData['productivity'] = $ratings["item3_score"];
                                $PDFData['resubmissiontime'] = $ratings["item4_score"];
                                $PDFData['routinesubmissiontime'] = $ratings["item5_score"];
                                $PDFData['onsitemeeting'] = $ratings["item6_score"];
                                $PDFData['additional_comments'] = $request->input("additional_comments");
                                $PDFData['goals'] = $request->input("goals");
                                $PDFData['comments'] = $request->input("comments");
        
                                $fileName = 'therapy_evaluation_' . $request->input("provider_id") . '_' . strtotime("now") . '.pdf';
                                if (LD::checkFeatureFlag('therapy-evaluation-changes')) {
                                    $template = 'therapyevaluationnew';
                                } else {
                                    $template = 'therapyevaluation';
                                }
        
                                $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName, $PDFData, $template);
                                
                                if (is_file($filePath)) {
                                    $documentType = 'therapy_eval';
                                    $documentTitle = "Therapy Evaluation";
                                    $recipientName = $PDFData['name'];
                                    $recipientEmail = $providerData->External_Email;

                                    $sendDocumentResponse = H::getHelper("RightSignature")->sendDocumentRequest($filePath, 0, $request->input("provider_id"), "provider", $documentType, $documentTitle, $recipientName, $recipientEmail, $request->input("id"));
                                    if (!empty($sendDocumentResponse)) {
                                        FieldEvaluation::where('id', $request->input('id'))->update(['status' => '1st Attempt','updated_by' => $userId]);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if(LD::checkFeatureFlag("rightsignature-send-performance-evaluation")) {
                        if(!empty($request->input("is_therapy_evaluation")) && $request->input("is_therapy_evaluation")==1 && ($request->input("action") == 'submit')) {
                            $providerData = DB::table("mssql_providers")->select("mssql_providers.*", "mssql_facilities.Facilityname as BranchName")->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')->where('mssql_providers.id',$request->input("provider_id"))->where('mssql_providers.is_deleted',0)->first();
                            $managerData = [];
                            $evData = FieldEvaluation::where('id', $request->input('id'))->first();
                            if (!empty($evData->manager)) {
                                $managerData = DB::table("users")->where("id", $evData->manager)->first();
                            }

                            if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
                                $providerData->HiredDate = (
                                    !empty($providerData->RehireDate) &&
                                    $providerData->RehireDate != '0000-00-00' &&
                                    $providerData->RehireDate != '1900-01-01'
                                ) ? $providerData->RehireDate : $providerData->HiredDate;
                            }
    
                            $PDFData['name'] = $providerData->FirstName . ' ' . $providerData->LastName;
                            $PDFData['hiredate'] = H::getFormatedDate($providerData->HiredDate);
                            $PDFData['jobtitle'] = $providerData->DisciplineName;
                            //$PDFData['review_period']=H::getFormatedDate($providerData->created_at).' to '.H::getFormatedDate($providerData->created_at);
                            if(LD::checkFeatureFlag("rightsignature_ccm_manager")) {
                                $PDFData['manager'] =  H::getHelper("RightSignature")->getCCMFromProvider($providerData->ProviderID,'2022-01-04','2023-01-03');
                            } else {
                                $PDFData['manager'] = isset($managerData->name) ? $managerData->name : '';
                            }
                            
                            $PDFData['createdDate'] = H::getFormatedDate($evData->created_at);
                            $PDFData['review_period'] = H::getFormatedDate(date('Y', strtotime("-1 year")) . '-' . date('m-d', strtotime($providerData->HiredDate))) . ' - ' . H::getFormatedDate(date('Y') .'-'. date('m-d', strtotime("-1 day", strtotime($providerData->HiredDate))));
                            $PDFData['head_productivity'] = $request->input("productivity");
                            $PDFData['ie_re_time'] = $request->input("ie_re_time");
                            $PDFData['rv_sv_dc_time'] = $request->input("rv_sv_dc_time");
                            $PDFData['BranchName'] = $providerData->BranchName;
                            $ratings = $request->input("ratings");
                            $PDFData['clinical_quality'] = $ratings["item1_score"];
                            $PDFData['communication'] = $ratings["item2_score"];
                            $PDFData['productivity'] = $ratings["item3_score"];
                            $PDFData['resubmissiontime'] = $ratings["item4_score"];
                            $PDFData['routinesubmissiontime'] = $ratings["item5_score"];
                            $PDFData['onsitemeeting'] = $ratings["item6_score"];
                            $PDFData['additional_comments'] = $request->input("additional_comments");
                            $PDFData['goals'] = $request->input("goals");
                            $PDFData['comments'] = $request->input("comments");
    
                            $fileName = 'therapy_evaluation_' . $request->input("provider_id") . '_' . strtotime("now") . '.pdf';
                            if (LD::checkFeatureFlag('therapy-evaluation-changes')) {
                                $template = 'therapyevaluationnew';
                            } else {
                                $template = 'therapyevaluation';
                            }
    
                            $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName, $PDFData, $template);
                            
                            if (is_file($filePath)) {
                                $documentType = 'therapy_eval';
                                $documentTitle = "Therapy Evaluation";
                                $recipientName = $PDFData['name'];
                                $recipientEmail = $providerData->External_Email;

                                $sendDocumentResponse = H::getHelper("RightSignature")->sendDocumentRequest($filePath, 0, $request->input("provider_id"), "provider", $documentType, $documentTitle, $recipientName, $recipientEmail, $request->input("id"));
                                if (!empty($sendDocumentResponse)) {
                                    FieldEvaluation::where('id', $request->input('id'))->update(['status' => '1st Attempt','updated_by' => $userId]);
                                }
                            }
                        }
                    }
                }

                
                


                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }

    private function filterArrayByKeys(array $input, array $column_keys)
    {
        $result = $finalResult = [];
        foreach($column_keys as $key => $value) {
            $finalResult[$value] = $input[$value];
        }
        $result[0] = (object) $finalResult;
        $finalData = (array) $this->DataProcess((object)$result);
        return (array) $finalData[0];
    }

    public function exportData(Request $request)
    {
        $columns = [
            ['name' => 'BranchName', 'label' => 'Branch'],
            ['name' => 'ClinicianID', 'label' => 'Clinician'],
            ['name' => 'ProviderName', 'label' => 'Provider'],
            ['name' => 'DisciplineName', 'label' => 'Discipline'],
            ['name' => 'fcreated_at', 'label' => 'Date Created'],
            ['name' => 'name', 'label' => 'Manager'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'document_status', 'label' => 'Document Status'],
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=evaluations.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        list($data, $totalData, $totalFiltered) = $this->getFieldEvaluation($request, $columns, 'export');
 
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
                if(LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
                    $evaluation = FieldEvaluation::find($request->input('id'));
                    $evaluationYear = \Carbon\Carbon::parse($evaluation->created_at)->format('Y');
                    $providerId = $evaluation->aoc_provider_id;
                    $noteText = $evaluationYear . " evaluation was deleted by " .
                                Auth::user()->name . ". Reason: " . $request->input('reason');
                    H::getHelper('ActivityNotes')->setActivityNotes([
                        'entity' => 'clinician',
                        'entity_id' => $providerId,
                        'notes' => $noteText
                    ]);
                    FieldEvaluation::where('id', $request->input('id'))->update([
                        'is_deleted' => '1',
                        'deleted_reason' => $request->input('reason'),
                        'deleted_date' => \Carbon\Carbon::now()->format('y-m-d'),
                        'deleted_by' => Auth::user()->id
                    ]);
                } else {
                    FieldEvaluation::where('id', $request->input('id'))->delete();
                }
                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }
}
