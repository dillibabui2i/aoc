<?php

namespace App\Http\Controllers;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Helpers\H;
use App\Helpers\LD;
use App\Models\ClaimAdjustmentHistory;
use App\MssqlPatients;
use App\Notes;
use App\Services\Adjustment\AdjustmentService;
use App\Services\Adjustment\AdjustmentWorkflowService;
use App\Services\Billing\BillingService;
use App\Services\Dashboard\Buckets\Billing\UnbilledSchedules\UnbilledSchedulesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BillingController extends Controller
{
    public function __construct(
        private UnbilledSchedulesService $service,
        private AdjustmentService $adjustmentService,
        private BillingService $billingService,
        private AdjustmentWorkflowService $workflowService
    ) {}

    public function reloadUnbilledSchedulesData(Request $request){
        return json_encode(["counter"=>H::getHelper("Billing")->getUnbilledSchedulesData($request->input("type"),
            $request->input("filters"),1,$request),"type"=>$request->input("type")]);
    }

    public function getUnbilledSchedulesData(Request $request){
        $showCount=0;

        H::ExcelLog('Controller type: ' . $request->input("type"), 'getBillingOrder.log');
        H::ExcelLog('Controller showCount: ' . $showCount, 'getBillingOrder.log');
        if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
            $data = H::getHelper("Billing")->getCombineUnbilledSchedulesData($request->input("type"),
                $request->input("filters"),$showCount,$request);
        } else {
            $data = H::getHelper("Billing")->getUnbilledSchedulesData($request->input("type"),
                $request->input("filters"),$showCount,$request);
        }
        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($data['totalData']),
            'recordsFiltered' => intval($data['totalFiltered']),
            'data'            => $this->processData($data['data']),
        ]);
    }

    public function showOutcome(Request $request){
        try {
            $ids = $request->input('ids');
            $type = $request->input('type');
            if($ids != '' && $type != ''){
                $outcomes['bill_ready'] = [
                    'Authorization Issue'=>[
                        'No Authorization',
                        'Incorrect Authorization'
                    ],
                    'Late Notes',
                    'Cancelled Claims (Internal)'=>[
                        'Hours Added',
                        'Update to Service',
                        'Change in Insurance',
                        'Other'
                    ],
                    'Cancelled Claims (External)'=>[
                        'Charting Error',
                        'Other'
                    ],
                    'Eligibility',
                    'Other'
                ];
                if(LD::checkFeatureFlag('action-column-not-bill-ready-bucket-billing-dashboard')){
                    $outcomes['bill_not_ready'] = [
                        'Late Note' =>[
                            'Clinician Under PET',
                            'Relative Caregiver PET'
                        ],
                        'KanTime Hold/Issue',
                        'Changed to Missed',
                        'Authorization Issue',
                        'Eligibility',
                        'EVV Issue',
                        'Payer Change Needed'
                    ];
                }
                return view('billing.billingOutcome',
                    [
                        'selectedIds' => $ids,
                        'outcome'=>$outcomes[$type],
                        'type' => $type
                    ]
                );
            }else{
                H::ExcelLog(
                    'showOutcome: ids / type is null', 'getBillingOrder.log'
                );
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'showOutcome:Controller type: ' . $request->input("type")."--".$e->getMessage(), 'getBillingOrder.log'
            );
        }
    }
    public function saveBillingOutcome(Request $request){
        try {
            $reqData = $request->all();
            if($reqData['selectedIds'] != '' && $reqData['billingReason'] != '' && $reqData['billingComments'] != ''){
                $ids = explode(',',$reqData['selectedIds']);
                $mainReason = $reqData['billingReason'];
                if(isset($reqData['billingSubReason']) && $reqData['billingSubReason'] != ''){
                    $mainReason .= ': '.$reqData['billingSubReason'];
                }
                $reason = date('m/d',strtotime('today')).' '.Auth::user()->name.
                    ' commented '.$mainReason.' Notes: '. $reqData['billingComments'];
                DB::table('mssql_schedulemaster')->whereIn('id',$ids)->update([
                    'billingNotes'=>$reason,
                    'billingReason'=>$reqData['billingReason'],
                    'billingSubReason'=>$reqData['billingSubReason']??null,
                    'billingReasonAddedAt'=>date('Y-m-d H:i:s'),
                    'billingReasonAddedBy'=>Auth::user()->id,
                    'billingComments' => $reqData['billingComments']
                ]);
                if(LD::checkFeatureFlag('billing-unbilled-schedules-add-activity-pop-up')){
                    if(count($ids) > 0){
                        foreach($ids as $id){
                            $logData = [
                                'entity' => 'billing',
                                'notes' => $mainReason.' Notes: '. $reqData['billingComments'],
                                'entity_id' => $id,
                                'misc4' => time()
                            ];
                            H::getHelper("ActivityNotes")->setActivityNotes($logData);
                        }
                    }
                }
                return response()->json(['status'=>true,'message'=>'Outcome updated successfully']);
            }else{
                return response()->json(['status'=>false,'message'=>'Reason not selected']);
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'saveBillingOutcome:Controller type: ' . $request->input("type")."--".$e->getMessage(),
                'getBillingOrder.log'
            );
        }
    }

    public function billingResolve(Request $request){
        try {
            $reqData = $request->all();
            if(isset($reqData['selectedIds']) && $reqData['selectedIds'] != ''){
                $ids = explode(',',$reqData['selectedIds']);
                DB::table('mssql_schedulemaster')->whereIn('id',$ids)->update([
                    'billingResolvedDate'=>date('Y-m-d H:i:s'),
                    'billingResolvedBy'=>Auth::user()->id
                ]);
                return response()->json(['status'=>true,'message'=>'resolved successfully']);
            }else{
                return response()->json(['status'=>false,'message'=>'something went wrong']);
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'billingResolve:Controller type: ' . $request->input("type")."--".$e->getMessage(),
                'getBillingOrder.log'
            );
        }
    }

    public function billingExclude(Request $request): JsonResponse
    {
        $ids = explode(',', $request->input('selectedIds'));
        $result = $this->service->billingExclude($ids, $request->input('type'));

        return response()->json(
            ['status' => $result],
            $result ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function exportBilling(Request $request)
    {
        try{
            if (LD::checkFeatureFlag('change-export-method-billing-dashboard')) {
                $requestData = json_decode($request->postexportdata, true);
                foreach ($requestData as $key => $value) {
                    $request->request->add([$key => $value]);
                }
            }
            $type = $request->input('type');
            $dataTableFields = H::getHelper('Billing')->getExportColumn($type);
            unset($dataTableFields['select']);
            unset($dataTableFields['id']);

            if (LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
                $data = H::getHelper("Billing")->getCombineUnbilledSchedulesData($request->input("type"), $request->input("filters"), 0, $request, true);
            } else {
                $data = H::getHelper("Billing")->getUnbilledSchedulesData($type, $request->input("filters"), 0, $request, true);
            }

            $data = H::objToArr($data);

            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=billing.csv",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $data = array_map(function ($row) {
                $row['not_in_kantime'] = $row['not_in_kantime'] === 0 ? 'No' : 'Yes';

                return $row;
            }, $data);

            $callback = function () use ($data, $dataTableFields) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($dataTableFields));

                foreach ($data as $tableData) {
                    $temp = [];
                    foreach ($dataTableFields as $key => $value) {
                        $temp[] = $tableData[$key];
                    }
                    fputcsv($file, $temp);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function notesFormate($note){
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
    public function processData($data){
        $billingData = $data;
        foreach($billingData as $billing){
            if(!empty($billing->Notes)){
                $billing->Notes = $this->notesFormate($billing->Notes);
            }
        }
        return $billingData;
    }

    public function invoiceIndex(){
        return view('billing.invoicesgrid');
    }

    public function getAllInvoice(Request $request){
        $columns = [
            0 =>'invoice_number',
            1 =>'invoice_status',
            2 =>'invoice_date',
            3 =>'invoice_age',
            4 =>'clientName',
            5 =>'FacilityName',
            6 =>'Payer',
            7 =>'StatementPeriod',
            8 =>'contractual_amount',
            9 =>'paidamount',
            10 =>'contractual_paid_diff',
        ];
        $bucket = $this->buildInvoiceQuery($request, $columns);
        $total_records = $bucket->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $bucket = $bucket->offset($request['start'])
                ->limit($request['length']);
        }
        $bucket = $bucket->get();
        foreach ($bucket as $bucketData) {
            $bucketData->action = "";
            $bucketData->action .= '<a href="javascript:void(0);" title="Set Outcome" ' .
                'onclick="invoicesOutcomeFromDetail(\''.$bucketData->invoice_type.'\', '.$bucketData->id.')">' .
                '<i class="fa fa-calendar"></i></a>';
            $bucketData->action .= '<a href="javascript:void(0);" title="View Detail"
            onclick="getInvoiceDetails('.$bucketData->id.', \''.$bucketData->invoice_type.'\');">
            <i class="fa fa-eye"></i></a>';
            $bucketData->action .= '<a href="javascript:void(0);" title="Add Note"
            onclick="openNotes('.$bucketData->id.',\'invoice\',\'Invoice Notes Added\');">
            <i class="fa fa-sticky-note-o"></i></a>';
            $bucketData->action .= '<a href="javascript:void(0);" title="Client Profile"
            onclick="getClientDetails('.$bucketData->patient_id.');">
            <i class="fa fa-user"></i></a>';
        }
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $bucket,
        ];
    }

    public function exportInvoiceData(Request $request){
        $columns = [
            ['name' => 'invoice_number', 'label' => 'Invoice Number'],
            ['name' => 'invoice_status', 'label' => 'Invoice Status'],
            ['name' => 'invoice_date', 'label' => 'Invoice Date'],
            ['name' => 'invoice_age', 'label' => 'Aging'],
            ['name' => 'clientName', 'label' => 'Client'],
            ['name' => 'clientID', 'label' => 'Client ID'],
            ['name' => 'FacilityName', 'label' => 'Branch Location'],
            ['name' => 'Payer', 'label' => 'Payer'],
            ['name' => 'StatementPeriod', 'label' => 'Statement Period'],
            ['name' => 'contractual_amount', 'label' => 'Expected Amount'],
            ['name' => 'paidamount', 'label' => 'Paid Amount'],
            ['name' => 'contractual_paid_diff', 'label' => 'Balance']
        ];
        $dataTableFields = [
            'invoice_number' => 'Invoice Number',
            'invoice_status' => 'Invoice Status',
            'invoice_date' => 'Invoice Date',
            'invoice_age' => 'Aging',
            'clientName' => 'Client',
            'clientID' => 'Client ID',
            'FacilityName' => 'Branch Location',
            'Payer' => 'Payer',
            'StatementPeriod' => 'Statement Period',
            'contractual_amount' => 'Expected Amount',
            'paidamount' => 'Paid Amount',
            'contractual_paid_diff' => 'Balance'
        ];
        if(LD::checkFeatureFlag('add-reason-sub-reason-columns-invoice-showall-export')){
            $dataTableFields['reason'] = 'Reason';
            $dataTableFields['subreason'] = 'Sub Reason';
            $dataTableFields['subreason_details'] = 'Sub Reason Details';
            $dataTableFields['notes'] = 'Notes';
            $columns[] = ['name' => 'reason', 'label' => 'Reason'];
            $columns[] = ['name' => 'subreason', 'label' => 'Sub Reason'];
            $columns[] = ['name' => 'subreason_details', 'label' => 'Sub Reason Details'];
            $columns[] = ['name' => 'latest_note', 'label' => 'Notes'];
        } else {
            $dataTableFields['latest_note'] = 'Notes';
            $columns[] = ['name' => 'latest_note', 'label' => 'Notes'];
        }
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=invoiceList.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        if(LD::checkFeatureFlag('change-export-method-billing-dashboard')) {
            $requestData = json_decode($request->postexportdata, true);
            foreach($requestData as $key => $value) {
                $request->request->add([$key => $value]);
            }
        }
        $bucket = $this->buildInvoiceQuery($request, $columns);
        H::ExcelLog('invoiceListing: ' . $bucket->toSql(), 'invoiceListing.log');
        $data = $bucket->get();
        $columns = [];

        foreach ($dataTableFields as $v) {
            $columns[] = $v;
        }
        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    if($fields == 'invoice_number'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'invoice_status'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'invoice_date'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'invoice_age'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'clientName'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'clientID'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'FacilityName'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'Payer'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'StatementPeriod'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'contractual_amount'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'paidamount'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'contractual_paid_diff'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'reason' || $fields == 'subreason' || $fields == 'subreason_details'){
                        $csvData[] = $tableData->{$fields};
                    }
                    if($fields == 'notes'){
                        $csvData[] = str_replace("<br>"," ",$tableData->{$fields});
                    }
                    if($fields == 'latest_note'){
                        if(LD::checkFeatureFlag('add-reason-sub-reason-columns-invoice-showall-export')){
                            $csvData[] = str_replace("<br>"," ",$tableData->{$fields});
                        } else {
                            $entityId = $tableData->id;
                            $note = Notes::where('entity_id', $entityId)
                                ->where('entity','invoice')->latest()->first();
                            $csvData[] = $note ? $note->notes : '';
                        }
                    }
                    $cntr++;
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildInvoiceQuery($request, $columns) {
        $search = $request->input('search.value');
        $bucket = DB::table('claims_master')->select(
            'claims_master.id',
            'claims_master.invoice_number',
            'claims_master.invoice_status',
            'claims_master.reason',
            'claims_master.subreason',
            'claims_master.notes',
            'claims_master.subreason_details',
            DB::raw('DATE_FORMAT(claims_master.invoice_date,
            "'.config('constant')['mysqlsearchdateformat'].'")
            as invoice_date'),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS clientName"),
            DB::raw("mssql_patients.PatientAccount AS clientID"),
            'mssql_facilities.FacilityName',
            'payment_source.name as Payer',
            DB::raw('CONCAT(DATE_FORMAT(claims_master.start_date,
            "'.config('constant')['mysqlsearchdateformat'].'"),
            " - ",
            DATE_FORMAT(claims_master.end_date,
            "'.config('constant')['mysqlsearchdateformat'].'"))
            as StatementPeriod'),
            DB::raw("CONCAT('$',FORMAT(ROUND(claims_master.contractual_amount, 2), 2)) as contractual_amount"),
            DB::raw("CONCAT('$',FORMAT(ROUND(claims_master.paidamount, 2), 2)) as paidamount"),
            DB::raw("CASE
            WHEN ROUND(claims_master.balance, 2) >= 0
            THEN CONCAT('$', FORMAT(ROUND(claims_master.balance, 2), 2))
            ELSE CONCAT('-$', FORMAT(ROUND(ABS(claims_master.balance), 2), 2))
            END as contractual_paid_diff"),
            DB::raw("DATEDIFF(now(),claims_master.invoice_date) as invoice_age"),
            'payment_source.historical_payment_norm',
            'mssql_patients.id as patient_id',
            DB::raw("CASE
                WHEN claims_master.is_partially_paid = 0 AND
                    claims_master.is_fully_paid = 0 AND
                    claims_master.is_cancelled = 0 AND
                    claims_master.is_denied = 0 AND
                    claims_master.balance != 0 AND
                    DATEDIFF(now(),
                    claims_master.invoice_date) >= payment_source.historical_payment_norm
                THEN 'unpaid_invoices_no_response'
                WHEN claims_master.is_denied = 1 AND
                    IFNULL(paid_date, '1970-01-01') > IFNULL(billed_date, invoice_date) AND
                    DATEDIFF(now(),
                    claims_master.invoice_date) >= payment_source.historical_payment_norm AND
                    claims_master.balance != 0
                THEN 'unpaid_invoices_denied'
                WHEN claims_master.balance > 0 AND
                    claims_master.paidamount > 0 AND
                    claims_master.is_cancelled = 0
                THEN 'invoices_under_paid'
                WHEN claims_master.balance < 0 AND
                    claims_master.paidamount > 0 AND
                    claims_master.is_cancelled = 0
                THEN 'invoices_over_paid'
                ELSE 'other_status'
            END as invoice_type")
        )
            ->leftJoin('mssql_patients', 'mssql_patients.PatientID', '=', 'claims_master.client_id')
            ->leftJoin("mssql_facilities","mssql_facilities.id", "=", "mssql_patients.aoc_facility_id")
            ->leftJoin('payment_source','payment_source.payersource_id', '=', 'claims_master.payersource_id');
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search){
                $query->whereRaw("mssql_patients.Search_Name LIKE ?
                OR claims_master.invoice_number LIKE ?", ["%{$search}%","%{$search}%"]);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $bucket = $bucket->orderBy($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $bucket->whereRaw("claims_master.balance != 0");
        $filters = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
            foreach($filters as $fkey => $fval) {
                if($fkey=='billInvoices_memode') {
                    $userBranch = H::getHelper('ConstantHelper')->getUserNameById(Auth::user()->id,'facilityid');
                    $userBranchIdArr = explode(',',$userBranch);
                    $bucket->whereIn("mssql_facilities.id", $userBranchIdArr);
                }elseif($fkey=='billInvoices_state') {
                    $bucket->where(function($bucket) use ($fval) {
                        foreach($fval as $val) {
                            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($val);
                            $facilities = H::objToArr($facilities);
                            foreach($facilities as $state){
                                $bucket->orWhere("mssql_patients.aoc_facility_id",'=',$state['id']);
                            }
                        }
                    });
                }elseif($fkey=='billInvoices_region'){
                    $bucket->where(function ($bucket) use ($fval) {
                        foreach ($fval as $value) {
                            $bucket->orWhere('mssql_facilities.region', '=', $value);
                        }
                    });
                }elseif($fkey=='billInvoices_branch'){
                    $bucket->where(function ($bucket) use ($fval) {
                        foreach ($fval as $value) {
                            $bucket->orWhere('mssql_patients.aoc_facility_id', '=', $value);
                        }
                    });
                }elseif($fkey=='billInvoices_payer'){
                    $bucket->where(function ($bucket) use ($fval) {
                        foreach ($fval as $value) {
                            $bucket->orWhere('payment_source.name', '=', $value);
                        }
                    });
                }elseif($fkey=='billInvoices_payer_group'){
                    $bucket->where(function ($bucket) use ($fval) {
                        foreach ($fval as $value) {
                            $bucket->orWhere('mssql_patients.payer_group', '=', $value);
                        }
                    });
                }elseif ($fkey == 'fromInvoiceDate') {
                    $formattedDate = date('Y-m-d', strtotime($fval[0]));
                    $bucket->whereDate('claims_master.invoice_date', '>=', $formattedDate);
                }elseif ($fkey == 'toInvoiceDate') {
                    $formattedDate = date('Y-m-d', strtotime($fval[0]));
                    $bucket->whereDate('claims_master.invoice_date', '<=', $formattedDate);
                }elseif ($fkey == 'invoice_type') {
                    if ($fval[0] != "all") {
                        $bucket->having('invoice_type', '=', $fval[0]);
                    }
                }
            }
        }
        return $bucket;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getInvoicesData(Request $request): JsonResponse
    {
        $showCount = 0;

        if ($request->input("type") === 'invoices_adjustments') {
            $data = H::getHelper("Billing")->getAdjustmentsInvoicesData(
                $request->input("type"),
                $request->input("filters"),
                $showCount,
                $request
            );
        } else {
            $data = H::getHelper("Billing")->getInvoicesData($request->input("type"),
                $request->input("filters"), $showCount, $request);
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($data['totalData']),
            'recordsFiltered' => intval($data['totalFiltered']),
            'data'            => $this->processData($data['data']),
        ]);
    }

    public function showInvoiceOutcome(Request $request)
    {
        try {
            $ids = $request->input('ids');
            $type = $request->input('type');
            if($ids != '' && $type != ''){
                $invoiceNumber = DB::table('claims_master')
                    ->whereIn('id', explode(',', $ids))
                    ->pluck('invoice_number')
                    ->toArray();
                $invoiceNumber = implode(',',$invoiceNumber);
                $adjustmentsCategories = $this->adjustmentService->getAvailableCategories();

                return view('billing.billingInvoiceOutcome',
                    [
                        'selectedIds' => $ids,
                        'invoiceNumber' => $invoiceNumber,
                        'type' => $type,
                        'adjustmentsCategories' => $adjustmentsCategories,
                    ]
                );
            }else{
                H::ExcelLog('showOutcome: ids / type is null', 'getBillingInvoiceOrder.log');

                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'showOutcome:Controller type: ' . $request->input('type') . "--" . $e->getMessage(), 'getBillingInvoiceOrder.log'
            );
        }
    }

    public function saveBillingInvoiceOutcome(Request $request): JsonResponse
    {
        try {
            return response()->json($this->billingService->saveInvoiceOutcome($request->all(), Auth::user()));
        } catch (Throwable $e) {
            H::ExcelLog(
                'saveBillingInvoiceOutcome: Controller type: ' .
                $request->input('type') . '--' . $e->getMessage(),
                'getBillingInvoiceOrder.log'
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function getInvoiceDetails(Request $request) {
        try {
            $invoiceDetails = $patientsDetails = $invoicesData = $invoicesList = [];
            if ($request->input('id') != '') {
                $invoiceDetailsQry = DB::table('claims_master')
                    ->select('claims_master.id',
                        'claims_master.client_id',
                        'claims_master.start_date',
                        'claims_master.end_date',
                        'claims_master.invoice_date',
                        'claims_master.totalamount',
                        DB::raw("ROUND(IFNULL(claims_master.paidamount,0), 2)
                as paidamount"),
                        DB::raw("ROUND(IFNULL(claims_master.other_adjustments,0), 2)
                as other_adjustments"),
                        DB::raw("ROUND(IFNULL(claims_master.contractual_amount,0), 2)
                as contractual_amount"),
                        DB::raw("ROUND(IFNULL(claims_master.contractual_adjustments,0), 2)
                as contractual_adjustments"),
                        'claims_master.invoice_number',
                        'claims_master.balance');
                if(empty($request->input('bucket_type'))) {
                    $invoiceDetailsQry->addSelect(DB::raw("CASE
                    WHEN claims_master.is_partially_paid = 0 AND
                        claims_master.is_fully_paid = 0 AND
                        claims_master.is_cancelled = 0 AND
                        claims_master.is_denied = 0 AND
                        claims_master.balance != 0 AND
                        DATEDIFF(now(),
                        claims_master.invoice_date) >= payment_source.historical_payment_norm
                    THEN 'unpaid_invoices_no_response'
                    WHEN claims_master.is_denied = 1 AND
                        IFNULL(paid_date, '1970-01-01') > IFNULL(billed_date, invoice_date) AND
                        DATEDIFF(now(),
                        claims_master.invoice_date) >= payment_source.historical_payment_norm AND
                        claims_master.balance != 0
                    THEN 'unpaid_invoices_denied'
                    WHEN claims_master.balance > 0 AND
                        claims_master.paidamount > 0 AND
                        claims_master.is_cancelled = 0
                    THEN 'invoices_under_paid'
                    WHEN claims_master.balance < 0 AND
                        claims_master.paidamount > 0 AND
                        claims_master.is_cancelled = 0
                    THEN 'invoices_over_paid'
                    ELSE 'other_status'
                END as invoice_bucket_type"))
                        ->leftJoin('payment_source','payment_source.payersource_id', '=', 'claims_master.payersource_id');
                }
                if(LD::checkFeatureFlag('add-adjustments-details-on-invoice-details-page')){
                    $invoiceDetailsQry->addSelect('claims_master.reason',
                        'claims_master.teamlead_approved_by', 'claims_master.teamlead_approved_at',
                        'claims_master.manager_approved_by', 'claims_master.manager_approved_at',
                        'claims_master.director_approved_by', 'claims_master.director_approved_at',
                        'claims_master.vp_approved_by', 'claims_master.vp_approved_at',
                        'claims_master.operations_approved_by', 'claims_master.operations_approved_at',
                        'claims_master.rejected_by','claims_master.rejected_at', 'claims_master.rejected_level'
                        ,'claims_master.adjustment_status', 'claims_master.requested_ops_approval_date');
                }
                $invoiceDetails = $invoiceDetailsQry->where('claims_master.id',$request->input('id'))
                    ->first();
                $patientsDetails = MssqlPatients::select(
                    "mssql_patients.id",
                    "mssql_patients.Patient_FirstName",
                    "mssql_patients.Patient_LastName",
                    "mssql_patients.PatientAccount",
                    "mssql_patients.Patient_DOB",
                    "mssql_patients.Payer",
                    "mssql_patients.Patient_MobilPhone",
                    "mssql_patients.Patient_Phone",
                    "mssql_patients.MedicaidNumber",
                    "mssql_patients.SOCDate",
                    "mssql_facilities.FacilityName")
                    ->leftJoin('mssql_facilities',
                        'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                    ->where('mssql_patients.PatientID',$invoiceDetails->client_id)->first();
                $invoicesList = DB::table('claims_master')
                    ->select('claims_master.id',
                        'claims_master.client_id',
                        'claims_master.start_date',
                        'claims_master.end_date',
                        DB::raw('DATE_FORMAT(claims_master.invoice_date,
                "%m/%d/%y") as invoice_date'),
                        'claims_master.totalamount',
                        DB::raw("ROUND(IFNULL(claims_master.paidamount,0), 2)
                as paidamount"),
                        DB::raw("ROUND(IFNULL(claims_master.other_adjustments,0), 2)
                as other_adjustments"),
                        DB::raw("ROUND(IFNULL(claims_master.contractual_amount,0), 2)
                as contractual_amount"),
                        DB::raw("ROUND(IFNULL(claims_master.contractual_adjustments,0), 2)
                as contractual_adjustments"),
                        'claims_master.invoice_number',
                        DB::raw("DATEDIFF(now(),claims_master.invoice_date) as invoice_age"),
                        'claims_master.balance')
                    ->where('claims_master.client_id',$invoiceDetails->client_id)
                    ->where("claims_master.balance",'!=',0)->get();
                $invoicesData = $invoicesList->map(function ($invoice) {
                    $invoice->bucket = $this->getBucket($invoice->invoice_age);
                    return $invoice;
                });
                //last payment info
                $lastPaymentinfo = [];
                $lastInvoiceData = DB::table('claims_master')
                    ->select('claims_master.invoice_number',
                        'claims_payment.transaction_date', 'claims_payment.paid_amt')
                    ->join('claims_payment', 'claims_master.claim_id', '=',
                        'claims_payment.claim_id')
                    ->where('claims_master.client_id', $invoiceDetails->client_id)
                    ->orderBy('claims_payment.transaction_date', 'DESC')
                    ->limit(1)
                    ->first();
                if(!empty($lastInvoiceData)) {
                    $lastPaymentinfoData = DB::table('claims_master')
                        ->select(DB::raw('sum(claims_master.paidamount) as paid_amount'))
                        ->join('claims_payment', 'claims_master.claim_id', '=',
                            'claims_payment.claim_id')
                        ->where('claims_master.client_id', $invoiceDetails->client_id);
                    $ninetyDaysAgo = DB::raw('DATE_SUB(CURDATE(), INTERVAL 90 DAY)');
                    $lastNintyDaysPaidAmount = $lastPaymentinfoData
                        ->where('claims_payment.transaction_date', '>=', $ninetyDaysAgo)
                        ->first();
                    $yearStart = DB::raw('DATE_FORMAT(CURDATE(), \'%Y\')-01-01');
                    $ytdPaidAmount = $lastPaymentinfoData
                        ->where('claims_payment.transaction_date', '>=', $yearStart)
                        ->first();
                    $lastPaymentinfo['invoice_number'] = $lastInvoiceData->invoice_number;
                    $lastPaymentinfo['transaction_date'] = H::getFormatedDate($lastInvoiceData->transaction_date);
                    $lastPaymentinfo['paid_amt'] = H::getFormatedAmountWithCurrency($lastInvoiceData->paid_amt);
                    $lastPaymentinfo['ninty_days_paid_amt'] =
                        H::getFormatedAmountWithCurrency($lastNintyDaysPaidAmount->paid_amount);
                    $lastPaymentinfo['ytd_paid_amt'] = H::getFormatedAmountWithCurrency($ytdPaidAmount->paid_amount);
                }
                if(!empty($request->input('bucket_type'))) {
                    $invoice_bucket_type = $request->input('bucket_type');
                }else {
                    $invoice_bucket_type = $invoiceDetails->invoice_bucket_type;
                }

                $tags = [];

                if(LD::checkFeatureFlag('manage-project-tags')){
                    $tags = DB::table('claim_tag')
                        ->join('tags', 'tags.id', '=', 'claim_tag.tag_id')
                        ->where('claim_tag.claim_id', $request->input('id'))
                        ->select('tags.id', 'tags.name', 'tags.status')
                        ->get();
                }

                $adjustmentHistoryByStage = $this->workflowService->adjustmentHistoryService
                    ->getCurrentCycleHistories($request->integer('id'))
                    ->filter(function (ClaimAdjustmentHistory $item) {
                        return in_array($item->event_type, [
                            AdjustmentEventTypeEnum::APPROVED,
                            AdjustmentEventTypeEnum::REJECTED,
                            AdjustmentEventTypeEnum::COMPLETED,
                        ], true);
                    })
                    ->keyBy(function (ClaimAdjustmentHistory $item) {
                        return $item->stage->value;
                    });

                return view('billing.invoiceDetails', [
                    'invoiceDetails' => $invoiceDetails,
                    'patientsDetails' => $patientsDetails,
                    'invoicesData' => $invoicesData,
                    'lastPaymentinfo' => $lastPaymentinfo,
                    'bucket_type' => $invoice_bucket_type,
                    'tags' => $tags,
                    'adjustmentHistoryByStage' => $adjustmentHistoryByStage,
                ],
                );
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                "getInvoiceDetails--".$e->getMessage(),
                'getInvoiceDetails.log'
            );
            return false;
        }
    }
    private function getBucket($days)
    {
        $bucketDayRange = '210+';
        if ($days <= 30) {
            $bucketDayRange = '0-30';
        } elseif ($days <= 60) {
            $bucketDayRange = '31-60';
        } elseif ($days <= 90) {
            $bucketDayRange = '61-90';
        } elseif ($days <= 120) {
            $bucketDayRange = '91-120';
        } elseif ($days <= 150) {
            $bucketDayRange = '121-150';
        } elseif ($days <= 180) {
            $bucketDayRange = '151-180';
        } elseif ($days <= 210) {
            $bucketDayRange = '181-210';
        }
        return $bucketDayRange;
    }

    public function getBillingDetails(Request $request){
        try {
            if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
                $billingDetail = $request->input('id');
                $smIds = explode(",",$request->input('id'));
                $billingData = [];
                if(LD::checkFeatureFlag('change-to-activity-log-unbilled-widget')) {
                    $visitDateColumn = 'mssql_schedulemaster.ScheduleDate';
                    $visitDateformat = config('constant')['mysqlsearchdateformat'];
                    if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                        $visitDateColumn = 'mssql_schedulemaster.PlannedStart';
                        $visitDateformat = config('constant')['mysqlsearchdatetimeformat'];
                    }
                    $billingData = DB::table('mssql_schedulemaster')->select(
                        'mssql_schedulemaster.id',
                        'mssql_schedulemaster.Hours',
                        DB::raw("CONCAT('$',FORMAT(ROUND(mssql_schedulemaster.Contractualamount,2),2)) as Contractualamount"),
                        'mssql_schedulemaster.InsuranceID',
                        DB::raw('DATE_FORMAT('.$visitDateColumn.',"'.$visitDateformat.'")
                            as ScheduleDate'),
                        DB::raw("DATEDIFF(now(),".$visitDateColumn.")
                            AS DaysPastDOS"),
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                            mssql_patients.Patient_LastName) AS clientName"),
                        DB::raw("CONCAT(mssql_providers.FirstName,' ',
                            IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
                            mssql_providers.LastName) AS clinicianName"),
                        'mssql_facilities.FacilityName',
                        'lob.LOBName',
                        'mssql_patients.Payer',
                    )
                        ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                        ->leftJoin('mssql_providers', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
                        ->leftJoin('mssql_facilities', 'mssql_schedulemaster.FacilityID', '=', 'mssql_facilities.FacilityID')
                        ->leftJoin('lob', 'mssql_patients.LOBID', '=', 'lob.LOBID')
                        ->whereIn("mssql_schedulemaster.id",$smIds)
                        ->distinct();
                    if(LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen')){
                        $billingData->addselect(DB::raw("(CASE WHEN mssql_schedulemaster.ScheduleStatusID =1 THEN 'Approved'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =2 THEN 'Completed'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =3 THEN 'In Progress'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =4 THEN 'MissedVisit'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =5 THEN 'Planned'
                                    ELSE ''
                                    END) as scheduleStatus"),DB::raw("(CASE WHEN mssql_schedulemaster.EChartStatusID =2 THEN 'Approved'
                                    WHEN mssql_schedulemaster.EChartStatusID =4 THEN 'Planned'
                                    WHEN mssql_schedulemaster.EChartStatusID =5 THEN 'Submitted'
                                    WHEN mssql_schedulemaster.EChartStatusID =3 THEN 'Sent for Correction'
                                    ELSE ''
                                    END) as Status"));
                    }
                    if(LD::checkFeatureFlag('exclude-missed-visit-schedule-from-bill-detail')) {
                        $billingData->where('mssql_schedulemaster.ScheduleStatusID','!=',4);
                    }
                    $billingData = $billingData->orderBy($visitDateColumn)->get();
                }
            } else {
                $billingDetail = $request->input('id');
                $billingData = [];
                if(LD::checkFeatureFlag('change-to-activity-log-unbilled-widget')) {
                    $visitDateColumn = 'mssql_schedulemaster.ScheduleDate';
                    $visitDateformat = config('constant')['mysqlsearchdateformat'];
                    if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                        $visitDateColumn = 'mssql_schedulemaster.PlannedStart';
                        $visitDateformat = config('constant')['mysqlsearchdatetimeformat'];
                    }
                    $billingData = DB::table('mssql_schedulemaster')->select(
                        'mssql_schedulemaster.id',
                        'mssql_schedulemaster.Hours',
                        DB::raw("CONCAT('$',FORMAT(ROUND(mssql_schedulemaster.Contractualamount,2),2)) as Contractualamount"),
                        'mssql_schedulemaster.InsuranceID',
                        DB::raw('DATE_FORMAT('.$visitDateColumn.',"'.$visitDateformat.'")
                            as ScheduleDate'),
                        DB::raw("DATEDIFF(now(),".$visitDateColumn.")
                            AS DaysPastDOS"),
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                            mssql_patients.Patient_LastName) AS clientName"),
                        DB::raw("CONCAT(mssql_providers.FirstName,' ',
                            IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
                            mssql_providers.LastName) AS clinicianName"),
                        'mssql_facilities.FacilityName',
                        'lob.LOBName',
                        'mssql_patients.Payer',
                    )
                        ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                        ->leftJoin('mssql_providers', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
                        ->leftJoin('mssql_facilities', 'mssql_schedulemaster.FacilityID', '=', 'mssql_facilities.FacilityID')
                        ->leftJoin('lob', 'mssql_patients.LOBID', '=', 'lob.LOBID')
                        ->where("mssql_schedulemaster.id",$request->input('id'));
                    if(LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen')){
                        $billingData->addselect(DB::raw("(CASE WHEN
                                    mssql_schedulemaster.ScheduleStatusID =2 THEN 'Completed'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =3 THEN 'In Progress'
                                    WHEN mssql_schedulemaster.ScheduleStatusID =5 THEN 'Planned'
                                    ELSE ''
                                    END) as scheduleStatus"),DB::raw("(CASE WHEN mssql_schedulemaster.EChartStatusID =2 THEN 'Approved'
                                    WHEN mssql_schedulemaster.EChartStatusID =4 THEN 'Planned'
                                    WHEN mssql_schedulemaster.EChartStatusID =5 THEN 'Submitted'
                                    WHEN mssql_schedulemaster.EChartStatusID =3 THEN 'Sent for Correction'
                                    ELSE ''
                                    END) as Status"));
                    }
                    if(LD::checkFeatureFlag('exclude-missed-visit-schedule-from-bill-detail')) {
                        $billingData->where('mssql_schedulemaster.ScheduleStatusID','!=',4);
                    }
                    $billingData = $billingData->first();
                }
            }
            return view('billing.billingactivitylog', ['billingDetails'=> $billingDetail,'billingData' => $billingData]);
        } catch (\Exception $e) {
            H::ExcelLog(
                "getBillingDetails--".$e->getMessage(),
                'getBillingDetails.log'
            );
            return false;
        }
    }

    public function exportInvoiceBucket(Request $request){
        try{
            if(LD::checkFeatureFlag('change-export-method-billing-dashboard')) {
                $requestData = json_decode($request->postexportdata, true);
                foreach($requestData as $key => $value) {
                    $request->request->add([$key => $value]);
                }
            }
            $type = $request->input('type');
            $dataTableFields = H::getHelper('Billing')->getInvoiceExportColumn($type);
            unset($dataTableFields['id']);unset($dataTableFields['select']);
            if($request->input("type") == 'invoices_adjustments') {
                $data = H::getHelper("Billing")->getAdjustmentsInvoicesData($type, $request->input("filters"), 0, $request, true);
            } else {
                $data = H::getHelper("Billing")->getInvoicesData($type, $request->input("filters"), 0, $request, true);
            }
            $data = H::objToArr($data);
            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=invoice.csv",
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
                        if($key == 'invoice_notes'){
                            $temp[] = str_replace("<br>"," ",$tableData[$key] ?? '');
                        } else {
                            $temp[] = $tableData[$key] ?? '';
                        }
                    }
                    fputcsv($file, $temp);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            H::ExcelLog('BillingController::exportBilling:'.$e->getMessage(),'billing.log');
        }
    }

    /**
     * @deprecated
     *
     * TODO Delete after release Adjustments
     *
     * @see BillingService::resolveLegacyWriteOffAssignment
     */
    public function getTeamLead($id,$biller){
        $billerDetails = DB::table('users')->select('billing_team_lead','billing_team_manager')->where('id',$biller)->first();
        $assignedToDetails = [];
        if ($billerDetails->billing_team_lead) {
            $assignedToDetails = ['assigned_to' => $billerDetails->billing_team_lead,'adjustment_status'=>'Pending Team Lead'];
        } elseif ($billerDetails->billing_team_manager) {
            $assignedToDetails = ['assigned_to' => $billerDetails->billing_team_manager,'adjustment_status'=>'Pending Manager'];
        } else {
            $patient_id = DB::table('claims_master')->where('id', $id)->value('client_id');
            $facility_id = DB::table('mssql_patients')->where('PatientID', $patient_id)->value('aoc_facility_id');
            $directorDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($facility_id, 'Director of Revenue Cycle Management');
            if(!empty($directorDetail[0])) {
                $assignedToDetails = ['assigned_to' => $directorDetail[0],'adjustment_status'=>'Pending Director'];
            }else {
                $vpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole($facility_id, 'VP of Revenue Cycle Management');
                $assignedToDetails = ['assigned_to' => $vpDetail[0],'adjustment_status'=>'Pending VP'];
            }
        }
        return $assignedToDetails;
    }

    public function showAdjustmentsInvoiceOutcome(Request $request){
        try {
            $id = $request->input('ids');
            if($id != ''){
                $invoiceDetails = DB::table('claims_master')->where('id', $id)->first();
                $patientsDetails = MssqlPatients::select(
                    "mssql_patients.id",
                    "mssql_patients.Patient_FirstName",
                    "mssql_patients.Patient_LastName",
                    "mssql_patients.PatientAccount",
                    "mssql_patients.Patient_DOB",
                    "mssql_patients.Payer",
                    "mssql_patients.Patient_MobilPhone",
                    "mssql_patients.Patient_Phone",
                    "mssql_patients.MedicaidNumber",
                    "mssql_patients.SOCDate",
                    "mssql_facilities.FacilityName")
                    ->leftJoin('mssql_facilities',
                        'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                    ->where('mssql_patients.PatientID',$invoiceDetails->client_id)->first();
                $vpExists = 0;
                $requestOpsUsers = [];
                $currentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId(Auth::user()->id);
                $vpRole = ['VP of Revenue Cycle Management'];
                $vpRoleExist = array_intersect($currentRole, $vpRole);

                if (!empty($vpRoleExist)) {
                    $vpExists = 1;
                    $requestAllOpsUsers = H::getHelper("UserPermissionResolver")->getUserListByPermission('adjustments.bucket');
                    $requestOpsUsers = array_filter($requestAllOpsUsers, function ($element) {
                        return $element['id'] !== Auth::user()->id;
                    });
                    $requestOpsUsers = array_values($requestOpsUsers);
                }
                $allowComplete = 0;
                $canCompleteUsers = [];
                if($invoiceDetails->adjustment_status == 'Approved') {
                    $canCompleteUsers = H::getHelper("UserPermissionResolver")->getUserListByPermission('can_complete_adjustments');
                    $allowedUsers = array_column($canCompleteUsers,'id');
                    if(!empty($canCompleteUsers)  && in_array(Auth::user()->id,$allowedUsers)) {
                        $allowComplete = 1;
                    }
                }

                $showRequestOpsReviewOption = false;
                $autoRouteOpsReviewFromOutcome = LD::checkFeatureFlag('update-adjustment-workflow');

                if ($autoRouteOpsReviewFromOutcome) {
                    $showRequestOpsReviewOption = $this->workflowService
                        ->canRequestOpsReviewFromOutcome((int) $id, Auth::user());
                }

                return view('billing.adjustmentsInvoiceOutcome',
                    [
                        'selectedId' => $id,
                        'invoiceDetails' => $invoiceDetails,
                        'patientsDetails' => $patientsDetails,
                        'vpExists' => $vpExists,
                        'requestOpsUsers' => $requestOpsUsers,
                        'allowComplete' => $allowComplete,
                        'canCompleteUsers' => $canCompleteUsers,
                        'showRequestOpsReviewOption' => $showRequestOpsReviewOption,
                        'autoRouteOpsReviewFromOutcome' => $autoRouteOpsReviewFromOutcome,
                    ]
                );
            }else{
                H::ExcelLog(
                    'showOutcome: ids is null', 'showAdjustmentsInvoiceOutcome.log'
                );
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'showOutcome:Controller type: ' . $request->input("type")."--".$e->getMessage(), 'showAdjustmentsInvoiceOutcome.log'
            );
        }
    }

    public function saveAdjustmentsInvoiceOutcome(Request $request)
    {
        if (!LD::checkFeatureFlag('update-adjustment-workflow')) {
            return $this->billingService->saveAdjustmentsInvoiceOutcomeLegacy($request);
        }

        return $this->billingService->saveAdjustmentsInvoiceOutcome($request);
    }

    public function showAdjustmentsInvoiceReassign(Request $request){
        try{
            $id = $request->input('ids');
            if($id != ''){
                $invoiceDetails = DB::table('claims_master')->where('id', $id)->first();
                $canReassign = 0;
                $reassignUsers = [];
                $currentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId(Auth::user()->id);
                if(in_array('VP of Revenue Cycle Management',$currentRole)) {
                    $canReassign = 1;
                    $reassignUsersRole = ['Billing Manager','Director of Revenue Cycle Management','Billing Team Lead'];
                } elseif(in_array('Director of Revenue Cycle Management',$currentRole)) {
                    $canReassign = 1;
                    $reassignUsersRole = ['Billing Manager','Billing Team Lead'];
                } elseif(in_array('Billing Manager',$currentRole)) {
                    $canReassign = 1;
                    $reassignUsersRole = ['Billing Team Lead'];
                }
                if($canReassign == 1) {
                    $reassignUsers = H::getHelper('ConstantHelper')->getUsersByRoles($reassignUsersRole);
                }
                return view('billing.adjustmentsInvoiceReassign',
                    [
                        'selectedId' => $id,
                        'invoiceDetails' => $invoiceDetails,
                        'canReassign' => $canReassign,
                        'reassignUsers' => $reassignUsers
                    ]);
            }else{
                H::ExcelLog(
                    'showOutcome: ids is null', 'showAdjustmentsInvoiceReassign.log'
                );
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                'showAdjustmentsInvoiceReassign:
                Controller type: --' .$e->getMessage(),
                'showAdjustmentsInvoiceReassign.log'
            );
        }
    }

    public function saveAdjustmentsInvoiceReassign(Request $request){
        try{
            $reqData = $request->all();
            $updateData = ['assigned_to' => $reqData['adjustmentsReassign']];
            H::ExcelLog('Approve Update for claim master id: '.$reqData['selectedId'], 'saveAdjustmentsInvoiceReassign.log');
            H::ExcelLog(print_r($updateData, true), 'saveAdjustmentsInvoiceReassign.log');
            DB::table('claims_master')->where('id',$reqData['selectedId'])->update($updateData);
            return response()->json(['status'=>true,'message'=>'Invoice reassigned successfully']);
        } catch (\Exception $e) {
            H::ExcelLog(
                'saveAdjustmentsInvoiceReassign:
                Controller type: --' .$e->getMessage(),
                'saveAdjustmentsInvoiceReassign.log'
            );
        }
    }
}
