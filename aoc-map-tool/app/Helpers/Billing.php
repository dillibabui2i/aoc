<?php
namespace App\Helpers;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\AdjustmentTypeEnum;
use App\Enums\UserStatus;
use App\Services\Billing\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Billing
{
    public function getBucketColumn($type)
    {
        $dataTableColumn = [
            'select'=>'Select',
            'id'=>'Id',
            'FacilityName'=>'Branch Location',
            'clientName'=>'Client',
            'LOBName'=>'LOB',
            'clinicianName'=>'Clinician',
            'ScheduleDate'=>'Visit Date',
            'DaysPastDOS'=>'Days Past DOS',
            'Hours'=>'Billable Hours',
            'Contractualamount'=>'Expected Amount',
            'Payer'=>'Terminal Payer',
        ];
        if($type == 'bill_not_ready') {
            $dataTableColumn['Notes']='Notes';
            if(!LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen')){
                $dataTableColumn['scheduleStatus']='Schedule Status';
                $dataTableColumn['Status']='Echart Status';
            }
        } else {
            if(LD::checkFeatureFlag('change-outcome-reasons-unbilled-schedules-billing-dashboard')){
                $dataTableColumn['Notes']="Department Notes";
                $dataTableColumn['billingNotes']='Biller Notes';
            }else{
                $dataTableColumn['Notes']="Operation's Notes";
                $dataTableColumn['billingNotes']='Rev Cycle Reason';
            }
        }
        if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
            unset($dataTableColumn['clinicianName']);
            $dataTableColumn['ScheduleDate']='Statement Period';
        }
        return $dataTableColumn;
    }
    public function getExportColumn($type)
    {
        $dataTableColumn = [
            'select'=>'Select',
            'id'=>'Id',
            'State'=>'State',
            'FacilityName'=>'Branch Location',
            'clientName'=>'Client',
            'PatientAccount'=>'Client ID',
            'LOBName'=>'LOB',
            'clinicianName'=>'Clinician',
            'ScheduleDate'=>'Visit Date',
            'DaysPastDOS'=>'Days Past DOS',
            'Hours'=>'Billable Hours',
            'Contractualamount'=>'Expected Amount',
            'Payer'=>'Terminal Payer',
        ];
        if($type == 'bill_not_ready') {
            $dataTableColumn['Notes']='Notes';
            if(LD::checkFeatureFlag('add-column-schedule-status-in-not-bill-ready')){
                $dataTableColumn['scheduleStatus']='Schedule Status';
                $dataTableColumn['Status']='Echart Status';
            }else{
                $dataTableColumn['Status']='Status';
            }
        } else {
            if(LD::checkFeatureFlag('add-reason-subreason-column-to-export-bill-ready-bucket')){
                $dataTableColumn['billingReason']='Reason';
                $dataTableColumn['billingSubReason']='Sub Reason';
            }
            if(LD::checkFeatureFlag('add-column-bill-ready-export')){
                $dataTableColumn['billingNotes']='Rev Cycle Notes';
                $dataTableColumn['Notes']="Operation's Notes";
            }else{
                $dataTableColumn['billingNotes']='Notes';
            }
        }
        if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
            unset($dataTableColumn['clinicianName']);
            $dataTableColumn['ScheduleDate']='Statement Period';
        }

        $dataTableColumn['not_in_kantime'] = 'Acquisition';

        return $dataTableColumn;
    }
    public function getInvoiceExportColumn($type)
    {
        if($type == 'invoices_adjustments') {
            $dataTableColumn = [
                'assigned_user_name' => 'Assigned To',
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'contractual_amount'=>'Invoice Amount',
                'contractual_paid_diff'=>'Balance',
                'category'=>'Category',
                'adjustment_status'=>'Status',
                'invoice_age'=>'Aging',
                'invoice_notes' => 'Notes'
            ];

            if (LD::checkFeatureFlag('manage-project-tags')) {
                $dataTableColumn['tags'] = 'Tags';
            }

        }else{
            $dataTableColumn =  [
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'contractual_paid_diff'=>'Balance',
                'reason' => 'Reason',
                'subreason' => 'Sub Reason',
                'subreason_details' => 'Sub Reason Details',
                'invoice_notes' => 'Notes',
            ];

            // add denial code column for denied invoices export
            if (LD::checkFeatureFlag('add-denial-code-to-export')
                && $type === 'unpaid_invoices_denied'
            ) {
                $dataTableColumn =  [
                    'invoice_number'=>'Invoice Number',
                    'invoice_date'=>'Invoice Date',
                    'invoice_age'=>'Aging',
                    'clientName'=>'Client',
                    'FacilityName'=>'Branch Location',
                    'Payer'=>'Payer',
                    'StatementPeriod'=>'Statement Period',
                    'contractual_amount'=>'Expected Amount',
                    'contractual_paid_diff'=>'Balance',
                    'denial_code'=>'Denial Code',
                    'reason' => 'Reason',
                    'subreason' => 'Sub Reason',
                    'subreason_details' => 'Sub Reason Details',
                    'invoice_notes' => 'Notes',
                ];
            }

            if (LD::checkFeatureFlag('manage-project-tags')) {
                $dataTableColumn['tags'] = 'Tags';
            }
        }
        return $dataTableColumn;
    }
    public function getInvoiceBucketColumn($type)
    {
        if($type == 'unpaid_invoices_no_response') {
            $dataTableColumn = [
                'select'=>'Select',
                'id'=>'Id',
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'contractual_paid_diff'=>'Balance',
                'reason' => 'Reason',
                'invoice_notes' => 'Notes'
            ];
        } elseif($type == 'unpaid_invoices_denied') {
            $dataTableColumn = [
                'select'=>'Select',
                'id'=>'Id',
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'paidamount'=>'Paid Amount',
                'contractual_paid_diff'=>'Balance',
                'denial_code' => 'Denial Code',
                'reason' => 'Reason',
                'invoice_notes' => 'Notes'
            ];
        } elseif($type == 'invoices_under_paid') {
            $dataTableColumn = [
                'select'=>'Select',
                'id'=>'Id',
                'invoice_number'=>'Invoice Number',
                'remit_remark' => 'Remit Remark',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'paidamount'=>'Paid Amount',
                'contractual_paid_diff'=>'Balance',
                'reason' => 'Reason',
                'invoice_notes' => 'Notes'
            ];
        } elseif($type == 'invoices_pending') {
            $dataTableColumn = [
                'invoice_number'=>'Invoice Number',
                'invoice_status' => 'Invoice Status',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'paidamount'=>'Paid Amount',
                'contractual_paid_diff'=>'Balance',
                'invoice_notes' => 'Notes'
            ];
        } elseif($type == 'invoices_adjustments') {
            $dataTableColumn = [
                'assigned_user_name' => 'Assigned To',
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'contractual_amount'=>'Invoice Amount',
                'contractual_paid_diff'=>'Balance',
                'category'=>'Category',
                'adjustment_status'=>'Status',
                'invoice_age'=>'Aging',
                'invoice_notes' => 'Notes'
            ];
        }else{
            $dataTableColumn = [
                'select'=>'Select',
                'id'=>'Id',
                'invoice_number'=>'Invoice Number',
                'invoice_date'=>'Invoice Date',
                'invoice_age'=>'Aging',
                'clientName'=>'Client',
                'FacilityName'=>'Branch Location',
                'Payer'=>'Payer',
                'StatementPeriod'=>'Statement Period',
                'contractual_amount'=>'Expected Amount',
                'paidamount'=>'Paid Amount',
                'contractual_paid_diff'=>'Balance',
                'reason' => 'Reason',
                'invoice_notes' => 'Notes'
            ];
        }
        return $dataTableColumn;
    }
    public function getDataTableHtml($type,$form='')
    {
        $clientCareCoordinator = 'Client Care Coordinator';
        $dataTable = [];
        $viewName = 'billing.billingUnbilledSchedules_datatable';
        switch($type) {
            case 'bill_ready':
                $dataTable = H::getHelper('Billing')->getBucketColumn($type);
                break;
            case 'bill_not_ready':
                $dataTable = H::getHelper('Billing')->getBucketColumn($type);
                break;
            case 'unpaid_invoices_no_response':
            case 'unpaid_invoices_denied':
            case 'invoices_under_paid':
            case 'invoices_over_paid':
            case 'invoices_pending':
            case 'invoices_adjustments':
                $dataTable = H::getHelper('Billing')->getInvoiceBucketColumn($type);
                $viewName = 'billing.billingInvoices_datatable';
                break;
            default:
                $dataTable = ['Client_Coordinator'=>$clientCareCoordinator,
                    'clientName'=>'Client',
                    'clinicianName'=>'Clinician',
                    'FacilityName'=>'Branch'];
                break;
        }
        return view($viewName,["type"=>$type,'dataTable'=>$dataTable,"form"=>$form]);
    }

    private function setFilters(&$bucket, $searchfilter, $btype, $useNewWorkflow = false)
    {
        $filters = $this->extractFilters($searchfilter);
        foreach ($filters as $key => $values) {
            switch ($key) {
                case 'billInvoices_tag':
                    $bucket->whereExists(function ($query) use ($values) {
                        $query->select(DB::raw(1))
                            ->from('claim_tag')
                            ->whereColumn('claim_tag.claim_id', 'claims_master.id')
                            ->whereIn('claim_tag.tag_id', $values);
                    });
                    break;
                case 'billInvoices_memode':
                case 'unbilledschedule_memode':
                    if($btype == 'invoices_adjustments') {
                        $userId = Auth::user()->id;
                        if ($useNewWorkflow) {
                            $bucket->where(function ($q) use ($userId) {
                                $q->where(function ($q2) use ($userId) {
                                    $q2->where('current_history.assignee_type', AdjustmentTypeEnum::USER->value)
                                        ->where('current_history.assignee_id', $userId);
                                })->orWhere(function ($q2) use ($userId) {
                                    $q2->where('current_history.assignee_type', AdjustmentTypeEnum::ROLE->value)
                                        ->whereExists(function ($sub) use ($userId) {
                                            $sub->select(DB::raw(1))
                                                ->from('users_has_roles')
                                                ->join('users', 'users.id', '=', 'users_has_roles.user_id')
                                                ->whereColumn('users_has_roles.role_id', 'current_history.assignee_id')
                                                ->where('users.id', $userId)
                                                ->where('users.status', UserStatus::ACTIVE->value)
                                                ->whereRaw('find_in_set(mssql_patients.aoc_facility_id, users.facilityid)');
                                        });
                                });
                            });
                        } else {
                            $bucket->where('claims_master.assigned_to', $userId);
                        }
                    } else {
                        $userBranch = H::getHelper('ConstantHelper')->getUserNameById(Auth::user()->id,'facilityid');
                        $userBranchIdArr = explode(',',$userBranch);
                        $bucket->whereIn("mssql_facilities.id", $userBranchIdArr);
                    }
                    break;
                case 'billInvoices_state':
                case 'unbilledschedule_state':
                    $this->applyFacilityFilter($bucket, 'mssql_facilities.id', $values);
                    break;
                case 'billInvoices_branch':
                case 'unbilledschedule_branch':
                    $this->applyFilter($bucket, 'mssql_facilities.id', $values);
                    break;
                case 'billInvoices_region':
                case 'unbilledschedule_region':
                    $this->applyFilter($bucket, 'mssql_facilities.region', $values);
                    break;
                case 'unbilledschedule_LOBID':
                    $this->applyFilter($bucket, 'mssql_patients.LOBID', $values);
                    break;
                case 'unbilledschedule_Status':
                    if($btype == 'bill_not_ready') {
                        $this->applyFilter($bucket, 'mssql_schedulemaster.EChartStatusID', $values);
                    }
                    break;
                case 'billInvoices_payer':
                    $this->applyFilter($bucket, 'payment_source.name', $values);
                    break;
                case 'unbilledschedule_payer':
                    $this->applyFilter($bucket, 'mssql_patients.Payer', $values);
                    break;
                case 'billInvoices_payer_group':
                case 'unbilledschedule_payer_group':
                    $this->applyFilter($bucket, 'mssql_patients.payer_group', $values);
                    break;
                case 'fromUnbilledScheduleDate':
                    if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                        $this->applyDateFilter($bucket, 'mssql_schedulemaster.PlannedStart', $values[0], '>=');
                    } else {
                        $this->applyDateFilter($bucket, 'mssql_schedulemaster.ScheduleDate', $values[0], '>=');
                    }
                    break;
                case 'toUnbilledScheduleDate':
                    if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                        $this->applyDateFilter($bucket, 'mssql_schedulemaster.PlannedStart', $values[0], '<=');
                    } else {
                        $this->applyDateFilter($bucket, 'mssql_schedulemaster.ScheduleDate', $values[0], '<=');
                    }
                    break;
                case 'fromInvoiceScheduleDate':
                    $this->applyDateFilter($bucket, 'claims_master.start_date', $values[0], '>=');
                    break;

                case 'toInvoiceScheduleDate':
                    $this->applyDateFilter($bucket, 'claims_master.end_date', $values[0], '<=');
                    break;
                case 'billready_Reason':
                case 'notbillready_Reason':
                    $this->applyFilter($bucket, 'mssql_schedulemaster.billingReason', $values);
                    break;
                case 'billready_SubReason':
                case 'notbillready_SubReason':
                    $this->applyFilter($bucket, 'mssql_schedulemaster.billingSubReason', $values);
                    break;
                case 'unbilledschedule_ccc':
                    if($btype == 'bill_not_ready'){
                        $this->applyFilter($bucket, 'mssql_patients.ccc_id', $values);
                    }
                    break;
                case 'billInvoices_Status':
                    $this->applyFilter($bucket, 'claims_master.invoice_status', $values);
                    break;
                case 'assigned_to':
                    if (LD::checkFeatureFlag('update-adjustment-workflow')) {
                        $bucket->where(function ($q) use ($values) {
                            foreach ($values as $filterValue) {

                                if ($filterValue === 'Unassigned') {
                                    $q->orWhere(function ($subQuery) {
                                        $subQuery
                                            ->where('current_history.event_type', AdjustmentEventTypeEnum::ASSIGNED->value)
                                            ->where('current_history.assignee_type', AdjustmentTypeEnum::ROLE->value)
                                            ->whereNotExists(function ($existsQuery) {
                                                $existsQuery->select(DB::raw(1))
                                                    ->from('users')
                                                    ->join('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                                                    ->where('users.status', 1)
                                                    ->whereColumn('users_has_roles.role_id', 'current_history.assignee_id')
                                                    ->whereRaw('find_in_set(mssql_patients.aoc_facility_id, users.facilityid)');
                                            });
                                    });

                                    continue;
                                }

                                if (!str_contains($filterValue, ':')) {
                                    continue;
                                }

                                [$type, $id] = explode(':', $filterValue, 2);
                                $id = (int) $id;

                                if ($type !== 'user' || $id <= 0) {
                                    continue;
                                }

                                $q->orWhere(function ($subQuery) use ($id) {
                                    $subQuery->where('current_history.event_type', AdjustmentEventTypeEnum::ASSIGNED->value)
                                        ->where(function ($matchQuery) use ($id) {
                                            $matchQuery
                                                // exact user assignment
                                                ->where(function ($directUserQuery) use ($id) {
                                                    $directUserQuery
                                                        ->where('current_history.assignee_type', AdjustmentTypeEnum::USER->value)
                                                        ->where('current_history.assignee_id', $id);
                                                })
                                                // role assignment resolved to this user by role + facility
                                                ->orWhere(function ($roleResolvedQuery) use ($id) {
                                                    $roleResolvedQuery
                                                        ->where('current_history.assignee_type', AdjustmentTypeEnum::ROLE->value)
                                                        ->whereExists(function ($existsQuery) use ($id) {
                                                            $existsQuery->select(DB::raw(1))
                                                                ->from('users')
                                                                ->join('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                                                                ->where('users.id', $id)
                                                                ->where('users.status', 1)
                                                                ->whereColumn('users_has_roles.role_id', 'current_history.assignee_id')
                                                                ->whereRaw('find_in_set(mssql_patients.aoc_facility_id, users.facilityid)');
                                                        });
                                                });
                                        });
                                });
                            }
                        });
                    } else {
                        $this->applyFilter($bucket, 'claims_master.assigned_to', $values);
                    }
                    break;
                case 'category':
                    if (LD::checkFeatureFlag('update-adjustment-workflow')) {
                        $bucket->whereIn("claims_master.adjustment_category_id", $values);
                    } else {
                        $this->applyFilter($bucket, 'claims_master.category', $values);
                    }

                    break;
                case 'aging':
                    if ($values[0] == '1') {
                        $bucket->having('invoice_age', '>=', 0)
                            ->having('invoice_age', '<=', 30);
                    } elseif ($values[0] == '2') {
                        $bucket->having('invoice_age', '>=', 31)
                            ->having('invoice_age', '<=', 60);
                    } elseif ($values[0] == '3') {
                        $bucket->having('invoice_age', '>=', 61)
                            ->having('invoice_age', '<=', 90);
                    } elseif ($values[0] == '4') {
                        $bucket->having('invoice_age', '>=', 91)
                            ->having('invoice_age', '<=', 120);
                    } elseif ($values[0] == '5') {
                        $bucket->having('invoice_age', '>=', 121)
                            ->having('invoice_age', '<=', 150);
                    } elseif ($values[0] == '6') {
                        $bucket->having('invoice_age', '>=', 151)
                            ->having('invoice_age', '<=', 180);
                    } elseif ($values[0] == '7') {
                        $bucket->having('invoice_age', '>=', 181)
                            ->having('invoice_age', '<=', 210);
                    } elseif ($values[0] == '8') {
                        $bucket->having('invoice_age', '>', 210);
                    }
                    break;
                case 'unbilledschedule_Discipline':
                    if($btype == 'bill_ready' || $btype == 'bill_not_ready') {
                        $this->applyFilter($bucket, 'mssql_providers.DisciplineName', $values);
                    }
                    break;
                case 'billInvoices_reject_write_off':
                    $this->applyFilter($bucket, "claims_master.adjustment_status",['Rejected']);
                    break;
                case 'billInvoices_tag':
                    $this->applyFilter($bucket, "claims_master.adjustment_status",['Rejected']);
                    break;
                default:
                    // Do nothing
                    break;
            }
        }
    }

    private function extractFilters($searchfilter){
        $filters = [];
        if (!empty($searchfilter)) {
            foreach ($searchfilter as $filter) {
                if (!empty($filter['value'])) {
                    $filters[$filter['name']][] = $filter['value'];
                }
            }
        }
        return $filters;
    }

    private function applyFacilityFilter(&$bucket, $column, $values){
        $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($values);
        $facilities = H::objToArr($facilities);
        $this->applyFilter($bucket, $column, array_map(function ($facility) {
            return $facility['id'];
        }, $facilities));
    }

    private function applyFilter(&$bucket, $column, $values){
        if (!empty($values)) {
            $bucket->whereIn($column, $values);
        }
    }

    private function applyDateFilter(&$bucket, $column, $date, $operator){
        if (!empty($date)) {
            $convertedDate = date('Y-m-d', strtotime($date));
            $bucket->whereDate($column, $operator, $convertedDate);
        }
    }

    public function getUnbilledSchedulesData($type,$searchfilter=[],$showCount=0,$request='',$isExport = false) {
        $search = '';$limit = '';$start = '';$order ='';$dir = '';
        $search = $request->input('search.value');
        $lastMonday = H::getMondayFromDate(date('Y-m-d'),1);
        $thisMonday = H::getMondayFromDate(date('Y-m-d'));
        $prevDate = date('Y-m-d', strtotime(' -1 day'));
        $visitDateColumn = 'mssql_schedulemaster.ScheduleDate';
        $visitDateformat = config('constant')['mysqlsearchdateformat'];
        if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
            $visitDateColumn = 'mssql_schedulemaster.PlannedStart';
            $visitDateformat = config('constant')['mysqlsearchdatetimeformat'];
        }
        $fromDataDate = '2023-01-01';
        $bucket = DB::table('mssql_schedulemaster')->select(
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
            'mssql_facilities.State',
            'lob.LOBName',
            'mssql_patients.Payer',
            'mssql_schedulemaster.billingReason',
            DB::raw('DATE_FORMAT(mssql_schedulemaster.billingReasonAddedAt,
            "'.config('constant')['mysqlsearchdatetimeformat'].'")
            as billingReasonAddedAt'),
            DB::raw('IF(DATE(mssql_schedulemaster.billingResolvedDate) < CURDATE(), 1, 0) AS less_than_today'),
            DB::raw("(CASE WHEN mssql_schedulemaster.EChartStatusID =2 THEN 'Approved'
            WHEN mssql_schedulemaster.EChartStatusID =4 THEN 'Planned'
            WHEN mssql_schedulemaster.EChartStatusID =5 THEN 'Submitted'
            WHEN mssql_schedulemaster.EChartStatusID =3 THEN 'Sent for Correction'
            ELSE ''
            END) as Status"),
            'mssql_schedulemaster.billingSubReason',
            'mssql_schedulemaster.billingNotes',
            'mssql_patients.ccc_id',
            'mssql_patients.not_in_kantime',
            'mssql_patients.PatientAccount'
        )
            ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
            ->leftJoin('mssql_providers', 'mssql_schedulemaster.ProviderID', '=', 'mssql_providers.ProviderID')
            ->leftJoin('mssql_facilities', 'mssql_schedulemaster.FacilityID', '=', 'mssql_facilities.FacilityID')
            ->leftJoin('lob', 'mssql_patients.LOBID', '=', 'lob.LOBID');

        switch ($type) {
            case 'bill_ready':
                if(LD::checkFeatureFlag('show-operation-notes-from-open-notes')){
                    $bucket->addselect(DB::raw("(SELECT CONCAT(DATE_FORMAT(notes.created_at, '%m/%d'), ' ', users.name, ' commented ',
                    notes.notes) FROM notes
                    LEFT JOIN users ON notes.created_by = users.id
                    WHERE notes.entity = 'billing' AND notes.entity_id = mssql_schedulemaster.id
                    AND notes.type = 'bill_ready'
                    ORDER BY notes.id DESC LIMIT 1) AS Notes"));
                }else{
                    $bucket->addselect(DB::raw("(SELECT CONCAT(DATE_FORMAT(notes.created_at, '%m/%d'), ' ', users.name, ' commented ',
                    notes.notes) FROM notes
                    LEFT JOIN users ON notes.created_by = users.id
                    WHERE notes.entity = 'billing' AND notes.entity_id = mssql_schedulemaster.id
                    ORDER BY notes.id DESC LIMIT 1) AS Notes"));
                }
                $bucket->where("mssql_schedulemaster.IsBillable",1)
                    ->whereIn("mssql_schedulemaster.ScheduleStatusID",[1])
                    ->where("mssql_schedulemaster.IsBilled",0)
                    ->whereRaw("IFNULL(mssql_schedulemaster.IsChild, 0) <> 1")
                    ->where($visitDateColumn,'<',$lastMonday)
                    ->where(function($q) {
                        $q->where("mssql_schedulemaster.billingResolvedDate",'<', date('Y-m-d'))
                            ->orWhereNull('mssql_schedulemaster.billingResolvedDate');
                    })->where("mssql_schedulemaster.EChartStatusID",2);
                break;

            case 'bill_not_ready':
                if(LD::checkFeatureFlag('add-column-schedule-status-in-not-bill-ready')){
                    $bucket->addselect(DB::raw("(CASE WHEN
                     mssql_schedulemaster.ScheduleStatusID =2 THEN 'Completed'
                    WHEN mssql_schedulemaster.ScheduleStatusID =3 THEN 'In Progress'
                    WHEN mssql_schedulemaster.ScheduleStatusID =5 THEN 'Planned'
                    ELSE ''
                    END) as scheduleStatus"));
                }
                $bucket->addselect(DB::raw("(SELECT CONCAT(DATE_FORMAT(notes.created_at, '%m/%d'), ' ', users.name, ' commented ',
                notes.notes) FROM notes
                LEFT JOIN users ON notes.created_by = users.id
                WHERE notes.entity = 'billing' AND notes.entity_id = mssql_schedulemaster.id
                ORDER BY notes.id DESC LIMIT 1) AS Notes"));
                $bucket->where("mssql_schedulemaster.IsBillable",1)
                    ->whereIn("mssql_schedulemaster.ScheduleStatusID",[2,3,5])
                    ->where("mssql_schedulemaster.IsBilled",0)
                    ->whereRaw("IFNULL(mssql_schedulemaster.IsChild, 0) <> 1");
                if(LD::checkFeatureFlag('view-records-from-current-week-not-bill-ready-bucket')){
                    $bucket->where(DB::raw('DATE('.$visitDateColumn.')'),'<',$thisMonday);
                } else {
                    $bucket->where($visitDateColumn,'<',$lastMonday);
                }
                $bucket->where(function($q) {
                    $q->where("mssql_schedulemaster.billingResolvedDate",'<', date('Y-m-d'))
                        ->orWhereNull('mssql_schedulemaster.billingResolvedDate');
                })->where("mssql_schedulemaster.EChartStatusID",'!=',2);
                break;

            default:
                # code...
                break;
        }
        $bucket->whereDate("mssql_schedulemaster.ScheduleDate", '>=', $fromDataDate);
        $bucket->whereRaw("mssql_patients.Search_Name NOT LIKE ?", ["%test%"]);
        if(LD::checkFeatureFlag('remove-is-deleted-records-from-biling-unbilled-schedules')){
            $bucket->where('mssql_schedulemaster.is_deleted','<>',1);
        }
        if($showCount==0) {
            $columns = H::getHelper('Billing')->getBucketColumn($type);
            $columnsKey = array_keys($columns);
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columnsKey[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
        }


        if (!empty($search)) {
            $bucket->where(function ($query) use ($search){
                $query->whereRaw("mssql_patients.Search_Name LIKE ?", ["%{$search}%"]);
            });
        }

        //Setting the filters
        $this->setFilters($bucket,$searchfilter, $type);

        H::ExcelLog('List: ' . $bucket->toSql(), 'getBillingRequest.log');
        if(in_array($order,['id','ScheduleDate','Hours','InsuranceID','Contractualamount','PlannedStart'])){
            if(LD::checkFeatureFlag('use-child-rows-unbilled-buckets-billing-dashboard')) {
                if($order == 'ScheduleDate') {
                    $order = 'mssql_schedulemaster.PlannedStart';
                } else {
                    $order = 'mssql_schedulemaster.'.$order;
                }
            } else {
                $order = 'mssql_schedulemaster.'.$order;
            }
        }
        H::ExcelLog('Order By: ' . $order, 'getBillingOrder.log');
        if($showCount==1) {
            return 0;
        } elseif($isExport){
            //we need to add limit here for the export, Check on staging
            //and the 100000 records export was fine and sucessfully processed
            return $bucket->limit(100000)->orderBy($order, $dir)->get();
        } else {
            $billTotalData = $bucket->limit(1000)->get();
            $showAllData['totalData'] =  count($billTotalData);
            $showAllData['totalFiltered'] = count($billTotalData);
            $bucket->offset($start)->limit($limit)->orderBy($order, $dir);
            H::ExcelLog('Final: ' . $bucket->toSql(), 'getBillingRequest.log');
            $showAllData['data'] = $bucket->get();
            return $showAllData;
        }
    }
    public function generateMultiSelectQuery($field, $value, &$array)
    {
        $fieldStr = [];
        foreach ($value as $fieldValue) {
            $fieldStr[] = '?';
            array_push($array, $fieldValue);
        }
        return ' ' . $field . ' IN (' . implode(", ", $fieldStr) . ') ';
    }

    public function getCombineUnbilledSchedulesData(
        $type,
        $searchfilter = [],
        $showCount = 0,
        $request = '',
        $isExport = false
    ) {
        $facilityParamArray = $patientParamArray = $providerParamArray = $scheduleParamArray = [];
        $facilityFilter = $patientFilter = $providerFilter = $scheduleFilter =  [];
        $filters = $this->extractFilters($searchfilter);
        $limit = '';
        $start = '';
        $order = '';
        $dir = '';
        $search = $request->input('search.value');
        $visitDateformat = config('constant')['mysqlsearchdateformat'];
        $thisMonday = H::getMondayFromDate(date('Y-m-d'));
        $billing_bucket_type = 2;
        $notesTypeQry = '';

        if($type == 'bill_ready') {
            $billing_bucket_type = 1;
            $notesTypeQry = "AND n.type = 'bill_ready'";
        }

        if (!empty($search)) {
            $tmp = 'mp.Search_Name';
            $fieldStr = ' ' . $tmp . ' LIKE ? ';
            array_push($patientParamArray, "%{$search}%");
            $patientFilter[] = $fieldStr;
        }

        $extraFacilities = $extraFacilityParamArray = [];
        foreach ($filters as $key => $values) {
            $fieldStr = $tmp = '';
            if ($key == 'unbilledschedule_memode') {
                $tmp = 'mf.id';
                $userBranch = H::getHelper('ConstantHelper')->getUserNameById(Auth::user()->id, 'facilityid');
                $userBranchIdArr = explode(',', $userBranch);
                $fieldStr = $this->generateMultiSelectQuery($tmp, $userBranchIdArr, $facilityParamArray);
                $facilityFilter[] = $fieldStr;
                $extraFacilities[] = $fieldStr;
                $extraFacilityParamArray = array_merge($extraFacilityParamArray, $userBranchIdArr);
            }

            if ($key == 'unbilledschedule_state') {
                $tmp = 'mf.State';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $facilityParamArray);
                $facilityFilter[] = $fieldStr;
                $extraFacilities[] = $fieldStr;
                $extraFacilityParamArray = array_merge($extraFacilityParamArray, $values);
            }

            if ($key == 'unbilledschedule_branch') {
                $tmp = 'mf.id';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $facilityParamArray);
                $facilityFilter[] = $fieldStr;
                $extraFacilities[] = $fieldStr;
                $extraFacilityParamArray = array_merge($extraFacilityParamArray, $values);
            }

            if ($key == 'unbilledschedule_region') {
                $tmp = 'mf.region';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $facilityParamArray);
                $facilityFilter[] = $fieldStr;
                $extraFacilities[] = $fieldStr;
                $extraFacilityParamArray = array_merge($extraFacilityParamArray, $values);
            }

            if ($key == 'unbilledschedule_LOBID') {
                $tmp = 'mp.LOBID';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $patientParamArray);
                $patientFilter[] = $fieldStr;
            }
            if ($key == 'unbilledschedule_payer') {
                $tmp = 'mp.Payer';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $patientParamArray);
                $patientFilter[] = $fieldStr;
            }

            if ($key == 'unbilledschedule_payer_group') {
                $tmp = 'mp.payer_group';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $patientParamArray);
                $patientFilter[] = $fieldStr;
            }

            if ($key == 'unbilledschedule_ccc') {
                $tmp = 'mp.ccc_id';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $patientParamArray);
                $patientFilter[] = $fieldStr;
            }

            if ($key == 'unbilledschedule_Discipline') {
                $tmp = 'pd.DisciplineName';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $providerParamArray);
                $providerFilter[] = $fieldStr;
            }

            if ($key == 'unbilledschedule_Status') {
                $tmp = 'sm.EChartStatusID';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            }

            if ($key == 'fromUnbilledScheduleDate') {
                $tmp = 'sm.PlannedStart';
                $fieldStr = ' ' . $tmp . ' >= ? ';
                $dateVal = date('Y-m-d', strtotime($values[0]));
                array_push($scheduleParamArray, $dateVal);
                $scheduleFilter[] = $fieldStr;
            }

            if ($key == 'toUnbilledScheduleDate') {
                $tmp = 'sm.PlannedStart';
                $fieldStr = ' ' . $tmp . ' <= ? ';
                $dateVal = date('Y-m-d', strtotime($values[0]));
                array_push($scheduleParamArray, $dateVal);
                $scheduleFilter[] = $fieldStr;
            }

            if ($key == 'billready_Reason' || $key == 'notbillready_Reason') {
                $tmp = 'sm.billingReason';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            }

            if ($key == 'billready_SubReason' || $key == 'notbillready_SubReason') {
                $tmp = 'sm.billingSubReason';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $values, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            }
        }

        if (!empty($extraFacilities)) {
            $facilitiesQry = " SELECT  mf.FacilityID FROM mssql_facilities mf";
            $facilitiesQry = $facilitiesQry . (!empty($extraFacilities) > 0 ? " WHERE " . implode(" AND ", $extraFacilities) : '');
            $facilitiesData = DB::select($facilitiesQry, $extraFacilityParamArray);
            $facilitiesList = json_decode(json_encode($facilitiesData), true);
            $facilitiesFinalList = array_column($facilitiesList, 'FacilityID');

            if (!empty($facilitiesFinalList)) {
                $tmp = 'sm.FacilityID';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $facilitiesFinalList, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            } else {
                // If user has no associated branches then return an empty result
                if ($showCount === 1) {
                    return 0;
                }
                $showAllData['totalData'] = 0;
                $showAllData['totalFiltered'] = 0;
                $showAllData['data'] = [];
                return $showAllData;
            }
        }

        if(!empty($patientFilter)) {
            $patientQry = " SELECT mp.PatientID FROM mssql_patients mp WHERE mp.Search_Name NOT LIKE '%test%' AND mp.is_deleted = 0 ";
            $patientQry =  $patientQry.(!empty($patientFilter) ? " AND " . implode(" AND " , $patientFilter) : '');
            $patientData = DB::select($patientQry,$patientParamArray);
            $patientDataList = json_decode(json_encode($patientData), true);
            $patientFinalList = array_column($patientDataList,'PatientID');
            if(!empty($patientFinalList)) {
                $tmp = 'sm.PatientID';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $patientFinalList, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            }
        }

        if(!empty($providerFilter)) {
            $providerQry = " SELECT  pd.ProviderID FROM mssql_providers pd ";
            $providerQry =  $providerQry.(!empty($providerFilter) ? " WHERE " . implode(" AND " , $providerFilter) : '');
            $providerData = DB::select($providerQry,$providerParamArray);
            $providerDataList = json_decode(json_encode($providerData), true);
            $providerFinalList = array_column($providerDataList,'ProviderID');
            if(!empty($providerFinalList)) {
                $tmp = 'sm.ProviderID';
                $fieldStr = $this->generateMultiSelectQuery($tmp, $providerFinalList, $scheduleParamArray);
                $scheduleFilter[] = $fieldStr;
            }
        }

        $lastMondayQry = '';
        if(LD::checkFeatureFlag('get-till-last-sunday-records-bill-ready-bucket')) {
            $lastMondayQry = " AND sm.PlannedStart < '".$thisMonday."'";
        }
        $cteQry = "WITH ScheduleData AS (
                SELECT sm.id, sm.Hours, sm.Contractualamount, sm.InsuranceID, sm.PlannedStart, sm.billingReason, sm.billingReasonAddedAt, sm.billingResolvedDate,
                    sm.EChartStatusID, sm.billingSubReason, sm.billingNotes, sm.PatientID, sm.ProviderID, sm.FacilityID, sm.ScheduleDate, sm.is_deleted, sm.billing_bucket_type,sm.ScheduleStatusID
                FROM mssql_schedulemaster sm
                WHERE sm.billing_bucket_type = ".$billing_bucket_type." ".$lastMondayQry." AND (sm.billingResolvedDate < '".date('Y-m-d')."' OR sm.billingResolvedDate IS NULL) AND DATE(sm.ScheduleDate) >= '2023-01-01' AND sm.is_deleted <> 1
                " .(!empty($scheduleFilter) ? " AND " .implode(" AND " , $scheduleFilter) : '')."
            ),

            PatientData AS (
                SELECT mp.not_in_kantime, mp.PatientAccount, mp.PatientID, mp.Patient_FirstName, mp.Patient_MiddleName, mp.Patient_LastName, mp.Payer, mp.ccc_id, mp.Search_Name, mp.LOBID
                FROM mssql_patients mp
                WHERE mp.Search_Name NOT LIKE '%test%'
                AND mp.is_deleted = 0
                " .(!empty($patientFilter) ? " AND " .implode(" AND " , $patientFilter) : '')."
            ),

            ProviderData AS (
                SELECT pd.ProviderID, pd.FirstName, pd.MiddleName, pd.LastName FROM mssql_providers pd
                ".(!empty($providerFilter) ? " WHERE " . implode(" AND " , $providerFilter) : '')."
            ),

            FacilityData AS (
                SELECT  mf.FacilityID, mf.FacilityName, mf.State FROM mssql_facilities mf
                ".(!empty($facilityFilter) ? " WHERE " . implode(" AND " , $facilityFilter) : '')."
            ),

            LOBData AS (
                SELECT  l.LOBID, l.LOBName FROM lob l
            ),

            LatestNoteData AS (
                SELECT  sn.PatientID, DATE_SUB(DATE(sn.PlannedStart), INTERVAL WEEKDAY(sn.PlannedStart) DAY) AS week_start,
                        MAX(n.id) AS note_id
                FROM notes n
                INNER JOIN ScheduleData sn ON sn.id = n.entity_id
                WHERE n.entity = 'billing' ".$notesTypeQry."
                GROUP BY sn.PatientID, DATE_SUB(DATE(sn.PlannedStart), INTERVAL WEEKDAY(sn.PlannedStart) DAY)
            ),

            NotesData AS (
                SELECT  ln.PatientID, ln.week_start, CONCAT(DATE_FORMAT(n.created_at, '%m/%d'), ' ', u.name, ' commented ', n.notes) AS Notes
                FROM LatestNoteData ln
                INNER JOIN notes n ON n.id = ln.note_id
                LEFT JOIN users u ON n.created_by = u.id
            )
            SELECT
                sd.id,GROUP_CONCAT(sd.id SEPARATOR ',') AS smids,
                FORMAT(ROUND(SUM(sd.Hours), 2), 2) as Hours,
                SUM(sd.Hours) as orgHours,
                SUM(sd.Contractualamount) as orgContractualamount,
                CONCAT('$', FORMAT(ROUND(SUM(sd.Contractualamount), 2), 2)) as Contractualamount,
                sd.InsuranceID,
                CONCAT(
                    DATE_FORMAT(DATE_SUB(DATE(MIN(sd.PlannedStart)), INTERVAL WEEKDAY(MIN(sd.PlannedStart)) DAY), '".$visitDateformat."'),
                    ' - ',
                    DATE_FORMAT(DATE_ADD(DATE_SUB(DATE(MIN(sd.PlannedStart)), INTERVAL WEEKDAY(MIN(sd.PlannedStart)) DAY), INTERVAL 6 DAY), '".$visitDateformat."')
                ) as ScheduleDate,
                DATEDIFF(now(), MIN(sd.PlannedStart)) AS DaysPastDOS,
                CONCAT(pd.Patient_FirstName, ' ', IF(pd.Patient_MiddleName != '', CONCAT(pd.Patient_MiddleName, ' '), ''), pd.Patient_LastName) AS clientName,
                CONCAT(pr.FirstName, ' ', IF(pr.MiddleName != '', CONCAT(pr.MiddleName, ' '), ''), pr.LastName) AS clinicianName,
                fd.FacilityName,fd.State,lb.LOBName,pd.Payer,
                SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(sd.billingReason, '') ORDER BY sd.billingReasonAddedAt DESC, sd.id DESC SEPARATOR 0x1D), 0x1D, 1) as billingReason,
                DATE_FORMAT(MAX(sd.billingReasonAddedAt),  '".config('constant')['mysqlsearchdatetimeformat']."') as billingReasonAddedAt,
                IF(DATE(sd.billingResolvedDate) < CURDATE(), 1, 0) AS less_than_today,
                (CASE WHEN sd.EChartStatusID = 2 THEN 'Approved'
                    WHEN sd.EChartStatusID = 4 THEN 'Planned'
                    WHEN sd.EChartStatusID = 5 THEN 'Submitted'
                    WHEN sd.EChartStatusID = 3 THEN 'Sent for Correction'
                    ELSE ''
                END) as Status,
                (CASE WHEN
                     sd.ScheduleStatusID =2 THEN 'Completed'
                    WHEN sd.ScheduleStatusID =3 THEN 'In Progress'
                    WHEN sd.ScheduleStatusID =5 THEN 'Planned'
                    ELSE ''
                    END) as scheduleStatus,
                SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(sd.billingSubReason, '') ORDER BY sd.billingReasonAddedAt DESC, sd.id DESC SEPARATOR 0x1D), 0x1D, 1) as billingSubReason,
                SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(sd.billingNotes, '') ORDER BY sd.billingReasonAddedAt DESC, sd.id DESC SEPARATOR 0x1D), 0x1D, 1) as billingNotes,
                pd.ccc_id,
                nd.Notes AS Notes,
                pd.not_in_kantime,
                pd.PatientAccount
            FROM ScheduleData sd
            LEFT JOIN PatientData pd ON sd.PatientID = pd.PatientID
            LEFT JOIN ProviderData pr ON sd.ProviderID = pr.ProviderID
            LEFT JOIN FacilityData fd ON sd.FacilityID = fd.FacilityID
            LEFT JOIN LOBData lb ON pd.LOBID = lb.LOBID
            LEFT JOIN NotesData nd ON nd.PatientID = sd.PatientID
                AND nd.week_start = DATE_SUB(DATE(sd.PlannedStart), INTERVAL WEEKDAY(sd.PlannedStart) DAY)
            GROUP BY sd.PatientID, DATE_SUB(DATE(sd.PlannedStart), INTERVAL WEEKDAY(sd.PlannedStart) DAY)
            HAVING clientName IS NOT NULL
            AND Payer IS NOT NULL
            AND LOBName IS NOT NULL";

        if ($showCount == 0) {
            $columns = H::getHelper('Billing')->getBucketColumn($type);
            $columnsKey = array_keys($columns);
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columnsKey[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
        }

        if (in_array($order, ['id', 'ScheduleDate', 'LOBName', 'Hours', 'InsuranceID', 'Contractualamount', 'PlannedStart'])) {
            if ($order == 'ScheduleDate' || $order == 'PlannedStart') {
                $order = (strtolower($dir) == 'asc') ? 'MIN(sd.PlannedStart)' : 'MAX(sd.PlannedStart)';
            } elseif ($order == 'Contractualamount') {
                $order = 'orgContractualamount';
            } elseif ($order == 'Hours') {
                $order = 'orgHours';
            } elseif ($order == 'LOBName') {
                $order = 'lb.LOBName';
            } else {
                $order = 'sd.' . $order;
            }
        }

        if ($showCount == 1) {
            return 0;
        } elseif ($isExport) {
            $cteQry = $cteQry . ' ORDER BY ' . $order . ' ' . $dir;
            $cteQry = $cteQry . ' LIMIT 100000 OFFSET 0';
            $paramsArray = array_merge($scheduleParamArray, $patientParamArray, $providerParamArray, $facilityParamArray);
            $showAllData = DB::select($cteQry, $paramsArray);
        } else {
            $paramsArray = array_merge($scheduleParamArray, $patientParamArray, $providerParamArray, $facilityParamArray);
            $billingTotal = (int) DB::selectOne(
                'SELECT COUNT(*) AS total FROM (' . $cteQry . ') billTotal',
                $paramsArray
            )->total;
            $cteQry = $cteQry . ' ORDER BY ' . $order . ' ' . $dir;
            $cteQry = $cteQry . ' LIMIT ' . $limit . ' OFFSET ' . $start;

            $billingData = DB::select($cteQry, $paramsArray);
            $showAllData['totalData'] = $billingTotal;
            $showAllData['totalFiltered'] = $billingTotal;

            $showAllData['data'] = array_map(function ($row) {
                if (str_contains($row->billingNotes, 'Thoughtful AI')) {
//                    $row->notes = $row->billingNotes;
//                    $row->Notes = $row->billingNotes;
                }

                return $row;
            }, $billingData);
        }

        return $showAllData;
    }

    public function getInvoicesData($type,$searchfilter=[],$showCount=0,$request='',$isExport = false) {
        $search = '';$limit = '';$start = '';$order ='';$dir = '';
        $search = $request->input('search.value');
        $thirtyOneDaysAgo = date('Y-m-d', strtotime('-31 days'));
        $fourteenDaysAgo = date('Y-m-d', strtotime('-14 days'));
        $bucket = DB::table('claims_master')->select(
            'claims_master.id',
            'claims_master.invoice_number',
            'claims_master.invoice_status',
            'claims_master.reason',
            'claims_master.subreason',
            'claims_master.subreason_details',
            DB::raw('DATE_FORMAT(claims_master.invoice_date,
            "'.config('constant')['mysqlsearchdateformat'].'")
            as invoice_date'),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS clientName"),
            'mssql_facilities.FacilityName',
            'payment_source.name as Payer',
            DB::raw("(
        SELECT GROUP_CONCAT(tags.name SEPARATOR ', ')
        FROM claim_tag
        JOIN tags ON tags.id = claim_tag.tag_id
        WHERE claim_tag.claim_id = claims_master.id
    ) as tags"),
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
            DB::raw("(select
                    concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                    from notes left join users on users.id = notes.created_by
                    where entity = 'invoice' and entity_id = claims_master.id
                    order by notes.id desc limit 1) as invoice_notes"),
            'claims_master.reason',
            'mssql_patients.id as patient_id',
        )
            ->leftJoin('mssql_patients', 'mssql_patients.PatientID', '=', 'claims_master.client_id')
            ->leftJoin("mssql_facilities","mssql_facilities.id", "=", "mssql_patients.aoc_facility_id")
            ->leftJoin('payment_source','payment_source.payersource_id', '=', 'claims_master.payersource_id');
        if($type != 'invoices_pending') {
            $bucket->where(function($query) use($thirtyOneDaysAgo,$fourteenDaysAgo) {
                $query->where(DB::raw("IFNULL(claims_master.followedup_at, '1970-01-01')"), '<', $fourteenDaysAgo )
                    ->where('claims_master.invoice_date', '<', $thirtyOneDaysAgo);
            });
        }else {
            if(LD::checkFeatureFlag('remove-partially-paid-for-no-response-bucket')) {
                $bucket->addSelect(DB::raw("CASE
                    WHEN claims_master.is_fully_paid = 0 AND
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
                END as invoice_bucket_type"));
            } else {
                $bucket->addSelect(DB::raw("CASE
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
                END as invoice_bucket_type"));
            }
        }
        if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget')) {
            $bucket->whereRaw("(ifnull(claims_master.reason,'') != 'Write off request' OR (reason = 'Write off request' AND adjustment_status = 'Rejected'))");
        }
        switch ($type) {
            case 'unpaid_invoices_no_response':
                if(LD::checkFeatureFlag('remove-partially-paid-for-no-response-bucket')) {
                    $bucket->where("claims_master.is_fully_paid",0)
                        ->where("claims_master.is_cancelled",0)
                        ->where("claims_master.is_denied",0)
                        ->where("claims_master.balance",'!=',0);
                } else {
                    $bucket->where("claims_master.is_partially_paid",0)
                        ->where("claims_master.is_fully_paid",0)
                        ->where("claims_master.is_cancelled",0)
                        ->where("claims_master.is_denied",0)
                        ->where("claims_master.balance",'!=',0);
                }
                $bucket->havingRaw("invoice_age >= historical_payment_norm");
                break;

            case 'unpaid_invoices_denied':
                $bucket->addSelect(DB::raw("IFNULL((
                SELECT GROUP_CONCAT(claim_denials.reason SEPARATOR ', ')
                FROM claim_denials
                WHERE claim_denials.claim_id = claims_master.claim_id), '-') AS denial_code"));
                $bucket->where("claims_master.is_denied",1);
                $bucket->whereRaw("IF( paid_date = '0000-00-00 00:00:00' OR paid_date IS NULL,'3099-01-01',
                paid_date ) > IFNULL(billed_date, invoice_date)");
                $bucket->havingRaw("invoice_age >= historical_payment_norm");
                $bucket->where("claims_master.balance",'!=',0);
                break;
            case 'invoices_under_paid':
                $bucket->addSelect(DB::raw("IFNULL(remit_remark, '-') AS remit_remark"));
                $bucket->whereRaw("claims_master.balance > 0")
                    ->where("claims_master.paidamount",">",0)
                    ->where("claims_master.is_cancelled",0);
                break;
            case 'invoices_over_paid':
                $bucket->whereRaw("claims_master.balance < 0")
                    ->where("claims_master.paidamount",">",0)
                    ->where("claims_master.is_cancelled",0);
                break;
            case 'invoices_pending':
                $bucket->where("claims_master.balance",'!=',0)
                    ->where("claims_master.is_cancelled",0);
                $bucket->whereRaw("(IFNULL(claims_master.followedup_at, '2100-01-01') >= '".$fourteenDaysAgo."' OR invoice_date >= '".$thirtyOneDaysAgo."' )");
                if(LD::checkFeatureFlag('change-logic-on-pending-invoice-bucket')){
                    $bucket->leftjoin('claims_payment', 'claims_master.claim_id', '=',
                        'claims_payment.claim_id')
                        ->whereRaw("IFNULL(claims_payment.transaction_date, '2100-01-01') >= '".$thirtyOneDaysAgo."'");
                }
                break;
            default:
                # code...
                break;
        }
        $bucket->whereRaw("mssql_patients.Search_Name NOT LIKE ?", ["%test%"]);
        if($showCount==0) {
            $columns = H::getHelper('Billing')->getInvoiceBucketColumn($type);
            $columnsKey = array_keys($columns);
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columnsKey[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
        }
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search){
                $query->whereRaw("mssql_patients.Search_Name LIKE ? OR
                claims_master.invoice_number LIKE ?", ["%{$search}%","%{$search}%"]);
            });
        }
        //Setting the filters
        $this->setFilters($bucket,$searchfilter, $type);
        H::ExcelLog('List: ' . $bucket->toSql(), 'getInvoicesData.log');
        if(in_array($order,['id','invoice_date','paidamount','contractual_amount'])){
            $order = 'claims_master.'.$order;
        } elseif($order == 'StatementPeriod') {
            $order = 'claims_master.start_date';
        }
        if($showCount==1) {
            return 0;
        } elseif($isExport){
            return $bucket->orderBy($order, $dir)->get();
        } else {
            $billTotalData = $bucket->get();
            $showAllData['totalData'] =  count($billTotalData);
            $showAllData['totalFiltered'] = count($billTotalData);
            if($order == 'contractual_paid_diff') {
                $order = 'ROUND(claims_master.balance, 2)';
                $bucket->offset($start)->limit($limit)->orderByRaw($order.' '.$dir);
            } else {
                $bucket->offset($start)->limit($limit)->orderBy($order, $dir);
            }
            H::ExcelLog('Final '.$type.' Query: ' . $bucket->toSql(), 'getInvoicesData.log');
            $showAllData['data'] = $bucket->get();
            return $showAllData;
        }
    }

    public static function getInvoiceNumberById($id, $field){
        $invoiceNumber = DB::table('claims_master')
            ->select($field)
            ->where('id', $id)
            ->first();
        return $invoiceNumber;
    }

    public function getAllAssignedTo(){
        return DB::table('claims_master')
            ->select('claims_master.assigned_to','users.name')->distinct()
            ->leftjoin('users', 'users.id','=','claims_master.assigned_to')
            ->where('users.status',1)
            ->where("claims_master.reason",'Write off request')
            ->get();
    }
    public function getAdjustmentsInvoicesData($type, $searchfilter = [], $showCount = 0, $request = '', $isExport = false)
    {
        $search = '';
        $limit = '';
        $start = '';
        $order ='';
        $dir = '';
        $adjustmentCategory = 'claims_master.category as category';

        $useNewWorkflow = LD::checkFeatureFlag('update-adjustment-workflow');
        $latestHistorySub = DB::table('claim_adjustment_histories as cah_latest')
            ->selectRaw('MAX(cah_latest.id) as id, cah_latest.claim_master_id')
            ->groupBy('cah_latest.claim_master_id');

        $search = $request->input('search.value');

        $adjustmentStatusSelect = DB::raw('claims_master.adjustment_status as adjustment_status');
        $assignedToSelect = DB::raw('claims_master.assigned_to as assigned_to');
        $assignedUserNameSelect = DB::raw(
            "
                CASE
                    WHEN claims_master.adjustment_status = 'Approved' and claims_master.assigned_to = 0
                    THEN 'Payment Posting'
                    ELSE assigned_users.name
                END as assigned_user_name
            "
        );

        if ($useNewWorkflow) {
            $adjustmentCategory = 'adjustments.category as category';
            $billingService = app(BillingService::class);
            $adjustmentStatusSelect = $billingService->getAdjustmentStatusSelectSql();
            $assignedToSelect = $billingService->getAssignedToSelectSql();
            $assignedUserNameSelect = $billingService->getAssignedUserNameSelectSql();
        }

        $bucket = DB::table('claims_master')->select(
            'claims_master.id',
            'claims_master.invoice_number',
            'claims_master.invoice_status',
            'claims_master.reason',
            'claims_master.subreason',
            'claims_master.subreason_details',
            DB::raw('DATE_FORMAT(claims_master.invoice_date,
            "'.config('constant.mysqlsearchdateformat').'")
            as invoice_date'),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
            IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
            mssql_patients.Patient_LastName) AS clientName"),
            'mssql_facilities.FacilityName',
            DB::raw("CONCAT('$',FORMAT(ROUND(claims_master.contractual_amount, 2), 2)) as contractual_amount"),
            DB::raw("CONCAT('$',FORMAT(ROUND(claims_master.paidamount, 2), 2)) as paidamount"),
            DB::raw("CASE
            WHEN ROUND(claims_master.balance, 2) >= 0
            THEN CONCAT('$', FORMAT(ROUND(claims_master.balance, 2), 2))
            ELSE CONCAT('-$', FORMAT(ROUND(ABS(claims_master.balance), 2), 2))
            END as contractual_paid_diff"),
            DB::raw("DATEDIFF(now(),claims_master.followedup_at) as invoice_age"),
            DB::raw("(select
                    concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                    from notes left join users on users.id = notes.created_by
                    where entity = 'invoice' and entity_id = claims_master.id
                    order by notes.id desc limit 1) as invoice_notes"),
            'claims_master.reason',
            $adjustmentStatusSelect,
            $assignedToSelect,
            $adjustmentCategory,
            DB::raw("'invoices_adjustments' as invoice_bucket_type"),
            $assignedUserNameSelect,
            'mssql_patients.id as patient_id',
            'claims_master.adjustment_completed_at',
            DB::raw("DATEDIFF(now(),claims_master.adjustment_completed_at) as invoice_completed_age")
        );

        if (LD::checkFeatureFlag('manage-project-tags')) {
            $bucket->addSelect(DB::raw("(
                SELECT GROUP_CONCAT(tags.name SEPARATOR ', ')
                FROM claim_tag
                JOIN tags ON tags.id = claim_tag.tag_id
                WHERE claim_tag.claim_id = claims_master.id
            ) as tags"));
        }

        $bucket->leftJoin('mssql_patients', 'mssql_patients.PatientID', '=', 'claims_master.client_id')
            ->leftJoin('mssql_facilities','mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->leftJoin('users as assigned_users','assigned_users.id', '=', 'claims_master.assigned_to');

        if ($useNewWorkflow) {
            $bucket->leftJoinSub($latestHistorySub, 'latest_history', function ($join) {
                $join->on('latest_history.claim_master_id', '=', 'claims_master.id');
            });

            $bucket->leftJoin('claim_adjustment_histories as current_history', 'current_history.id', '=', 'latest_history.id');

            $bucket->leftJoin('users as history_assigned_users', function ($join) {
                $join->on('history_assigned_users.id', '=', 'current_history.assignee_id')
                    ->where('current_history.assignee_type', '=', 'user');
            });

            $bucket->leftJoin('adjustments', 'adjustments.id', '=', 'claims_master.adjustment_category_id');
        }

        $bucket->whereRaw('mssql_patients.Search_Name NOT LIKE ?', ["%test%"]);
        $bucket->where("claims_master.reason",'Write off request')
            ->where("claims_master.balance",'!=',0)
            ->where(function ($q) {
                $q->whereNull('claims_master.is_deleted')
                    ->orWhere('claims_master.is_deleted', 0);
            })
            ->whereRaw("IFNULL(DATE(claims_master.adjustment_completed_at), '1970-01-01') < '".date('Y-m-d')."'");

        if ($useNewWorkflow) {
            $bucket->whereNotNull('current_history.id')
                ->whereNotIn('current_history.event_type', [
                    AdjustmentEventTypeEnum::COMPLETED->value,
                    AdjustmentEventTypeEnum::REJECTED->value,
                ]);
        } else {
            $bucket->where("claims_master.adjustment_status", '!=', 'Rejected');
        }

        if($showCount==0) {
            $columns = H::getHelper('Billing')->getInvoiceBucketColumn($type);
            $columnsKey = array_keys($columns);
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columnsKey[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
        }

        if (!empty($search)) {
            $bucket->where(function ($query) use ($search, $useNewWorkflow) {
                if ($useNewWorkflow) {
                    $resolvedAssignedNamesSql = "
                        CASE
                            WHEN current_history.assignee_type = 'user' THEN history_assigned_users.name
                            WHEN current_history.assignee_type = 'role' THEN COALESCE(
                                (
                                    SELECT GROUP_CONCAT(DISTINCT resolved_users.name ORDER BY resolved_users.name SEPARATOR ', ')
                                    FROM users as resolved_users
                                    JOIN users_has_roles
                                        ON users_has_roles.user_id = resolved_users.id
                                    WHERE resolved_users.status = 1
                                      AND users_has_roles.role_id = current_history.assignee_id
                                      AND find_in_set(mssql_patients.aoc_facility_id, resolved_users.facilityid)
                                ),
                                'Unassigned'
                            )
                            ELSE NULL
                        END
                    ";

                    $query->whereRaw(
                        "
                            mssql_patients.Search_Name LIKE ?
                            OR claims_master.invoice_number LIKE ?
                            OR history_assigned_users.name LIKE ?
                            OR {$resolvedAssignedNamesSql} LIKE ?
                            ",
                            [
                                "%{$search}%",
                                "%{$search}%",
                                "%{$search}%",
                                "%{$search}%",
                            ]
                    );
                } else {
                    $query->whereRaw(
                    "
                            mssql_patients.Search_Name LIKE ?
                            OR claims_master.invoice_number LIKE ?
                            OR assigned_users.name LIKE ?
                        ",
                        [
                            "%{$search}%",
                            "%{$search}%",
                            "%{$search}%",
                        ]
                    );
                }
            });
        }

        //Setting the filters
        $this->setFilters($bucket, $searchfilter, $type, $useNewWorkflow);

        if (in_array($order, ['id', 'invoice_date', 'paidamount', 'contractual_amount'])) {
            $order = 'claims_master.' . $order;
        } elseif ($order == 'StatementPeriod') {
            $order = 'claims_master.start_date';
        } elseif ($order == 'assigned_user_name') {
            if ($useNewWorkflow) {
                $order = 'assigned_user_name';
            } else {
                $order = 'assigned_users.name';
            }
        }

        if ($showCount == 1) {
            return 0;
        } elseif ($isExport) {
            return $bucket->orderBy($order, $dir)->get();
        } else {
            $billTotalData = $bucket->get();
            $showAllData['totalData'] = count($billTotalData);
            $showAllData['totalFiltered'] = count($billTotalData);

            if ($order == 'contractual_paid_diff') {
                $order = 'ROUND(claims_master.balance, 2)';
                $bucket->offset($start)->limit($limit)->orderByRaw($order . ' ' . $dir);
            } else {
                $bucket->offset($start)->limit($limit)->orderBy($order, $dir);
            }

            $showAllData['data'] = $bucket->get();

            return $showAllData;
        }
    }
}
