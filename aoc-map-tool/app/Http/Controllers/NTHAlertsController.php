<?php
namespace App\Http\Controllers;

use App\Enums\NTHLeadsAndInqInquiriesTypeEnum;
use App\Enums\RecruitingBucket;
use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\NthInquiry\NthInquiryBulkResolveRequest;
use App\Http\Requests\NthInquiry\NthInquirySimpleLeadsRequest;
use App\MssqlRecruitertracker;
use App\NTHInquiries;
use App\Repositories\NthInquiry\NthInquiryReadRepository;
use App\Repositories\NthInquiry\NthInquiryWriteRepository;
use Carbon\Carbon;
use App\Enums\NTHLeadsAndInqInquiriesOutcomeEnum;
use App\Enums\NTHLeadsAndInqInquiriesStatusEnum;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NTHAlertsController extends Controller
{

    public function __construct(
      private NthInquiryReadRepository $nthInquiryReadRepository,
      private NthInquiryWriteRepository $nthInquiryWriteRepository
    ) {}

    public function recruiterAssignment(Request $request) {
        try {

             DB::table("nth_inquiries")->whereIn("id",$request->ids)->update(["recruiter_id"=>$request->targetRecruiter]);
        } catch(\Exception $e) {
            return response()->json(["success"=>false,"reason"=>$e->getMessage()]);
        }
        return response()->json(["success"=>true]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getLeadsAndInquiries(Request $request): mixed
    {
        [$data, $count] = H::getHelper('NTHAlerts')->getNTHAlertsData($request);

        $dataTable =  Datatables::of($data)
        ->editColumn('Recruiter', function ($row) {
                if(empty($row->Recruiter)){
                    return 'Unassigned';
                } else {
                    return $row->Recruiter;
                }
        })
        ->addColumn('Action', function ($row) use($request){
            $action = '<a href="javascript:void(0);" title="View" onclick="getNthLeadsInquiriesDetail(\''.$row->id.'\')">
                        <i class="fa fa-eye" aria-hidden="true"></i></a> | ';

            if($row->aoc_provider_id) {
                $action.='<a href="javascript:void(0);" title="View Clinician Profile" onclick="getClinicianProfile(\''.$row->aoc_provider_id.'\')">
                <i class="fa fa-stethoscope" aria-hidden="true"></i></a> | ';
            }

            if($row->aoc_patient_id) {
                $action.='<a href="javascript:void(0);" title="View client Profile" onclick="getClientDetails(\''.$row->aoc_patient_id.'\')">
                <i class="fa fa-user" aria-hidden="true"></i></a> | ';
            }
            $action.='<a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes('.$row->id.',\'nthalerts\',\'NTH Leads and Inquiries notes added\')"><i class="fa  fa-sticky-note-o"></i></a>';

            return $action;
        });

        if(H::canPermissions(['selfassignment-nthleads-inquiries','re-assignment-nthleads-inquiries'])) {
            $dataTable->addColumn('select', function ($row){
                $checkbox = '<input type="checkbox" name="checkbox-nth_leads[]" data-recruiter="'.$row->Recruiter.'" class="form-input td select-all-nth_leads" value="' . $row->id . '" />';

                return $checkbox;
            });
        }

        $dataTable->with([
            'customCounter' => $count,
        ]);

        return $dataTable->escapeColumns('Action')->make(true);
    }

    public function reloadLeadsAndInquiries(Request $request)
    {
        if (LD::checkFeatureFlag('nth-leads-latest-lead-per-clinician')) {
            $cliniciansCount = $this->nthInquiryReadRepository->getLeadsAndInquiriesCountClinician();
            $leadsCount = $this->nthInquiryReadRepository->getLeadsCount();
            $inquiriesCount = $this->nthInquiryReadRepository->getInquiriesCount();
            $count = sprintf(
                '(%d Clinicians | %d Leads & %d Inquiries)',
                $cliniciansCount,
                $leadsCount,
                $inquiriesCount
            );

            return json_encode([
                'counter' => $count,
                'type' => $request->input('type'),
            ]);
        }

        $showCount = 1;
        return  json_encode([
            'counter' => H::getHelper('NTHAlerts')->getNTHAlertsData($request, $showCount),
            'type' => $request->input('type'),
        ]);
    }


    public function getNthLeadsInquiriesDetail(Request $request) {
        try{
            if ($request->input('id') != '') {
                $leadsInquiriesDetail = DB::table('nth_inquiries')
                ->select('nth_inquiries.*',DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
                DB::raw("CASE WHEN nth_inquiries.aoc_nthaccounts_id = 0 THEN ' '
                ELSE CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) END as patient_name"),
                DB::raw("CASE WHEN nth_inquiries.aoc_nthaccounts_id = 0 THEN ' '
                ELSE PatientAccount END as PatientAccount"),
                DB::raw("CASE WHEN nth_inquiries.recruiter_id = 0 THEN ' '
                ELSE users.name END as Recruiter"),
                DB::raw("CASE
                WHEN nth_inquiries.type = 'lead'
                AND nth_inquiries.Status = 'Resolved'
                AND nth_inquiries.resolution IS NULL
                THEN 'Unknown'
                ELSE nth_inquiries.resolution
                END AS resolution")
                )
                ->leftJoin('mssql_patients', function ($join) {
                    $join->on('mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
                         ->where('nth_inquiries.aoc_nthaccounts_id', '>', 0);
                })
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'nth_inquiries.recruiter_id')
                         ->where('nth_inquiries.recruiter_id', '>', 0);
                })
                ->where('nth_inquiries.id',$request->input('id'))
                ->first();

                $clinicianData = DB::table('mssql_providers')
                ->select('mssql_providers.fullName','mssql_providers.ClinicianID','mssql_providers.DisciplineName','mssql_employmentstatus.EmploymentStatus','mssql_facilities.FacilityName',
                'mssql_providers.Address1','mssql_providers.Address2','mssql_providers.City','mssql_providers.State','mssql_providers.Zip','mssql_providers.Phone','mssql_providers.Mobile',
                'mssql_providers.External_Email')
                ->leftJoin("mssql_employmentstatus","mssql_employmentstatus.EmploymentStatusID","=","mssql_providers.EmploymentStatusID")
                ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
                ->where('ClinicianID', $leadsInquiriesDetail->ClinicianID)->first();
                $clientDetails = $needToHireChecklistDetails = [];
                if($leadsInquiriesDetail->type == 'lead' && $leadsInquiriesDetail->aoc_patient_id > 0){
                    $clientDetails = DB::table('mssql_patients')->select("mssql_patients.*","mssql_facilities.FacilityName",DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))
                    ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                    ->where('mssql_patients.id',$leadsInquiriesDetail->aoc_patient_id)->first();
                    if ($clientDetails !== null) {
                        $genderPref = 'Either Gender';
                        if(strpos(strtolower($clientDetails->PreferenceList),'does not accept female nurses') !== false){
                            $genderPref = 'Male Only';
                        }elseif (strpos(strtolower($clientDetails->PreferenceList),'male nurse accepted') === false){
                            $genderPref = 'Female Only';
                        }
                        $clientDetails->genderPref = $genderPref;
                    }
                    $needToHireChecklistDetails = DB::table('NeedToHire_Accounts')->select('NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"))->where("NeedToHire_Accounts.id",$leadsInquiriesDetail->aoc_nthaccounts_id)->first();

                    $displaySchedule = '';
                    if($leadsInquiriesDetail->aoc_nthaccounts_id > 0) {
                        $displaySchedule = H::getHelper("NTHLanding")->getNTHShiftDescription($leadsInquiriesDetail->aoc_nthaccounts_id);
                    }
                    $needToHireChecklistDetails->displaySchedule =  $displaySchedule;
                }

                $leadResolutions =   H::getHelper('ConstantHelper')->getLeadResolutions();

                $superSededBanner = null;
                if ($leadsInquiriesDetail->type === NTHLeadsAndInqInquiriesTypeEnum::LEAD->value &&
                    $leadsInquiriesDetail->Status !== NTHLeadsAndInqInquiriesStatusEnum::RESOLVED->value &&
                    LD::checkFeatureFlag('nth-superseded-lead-notifications')) {
                    $superSededBanner = $this->nthInquiryReadRepository->getSupersededLeadsBanner(
                        $leadsInquiriesDetail,
                        $leadsInquiriesDetail->aoc_provider_id
                    );
                }

                return view('nthalerts.leadDetails', [
                    'leadsInquiriesDetail' => $leadsInquiriesDetail,
                    'clinicianData' => $clinicianData,
                    'clientDetails' => $clientDetails,
                    'needToHireChecklistDetails' => $needToHireChecklistDetails,
                    'leadResolutions' => $leadResolutions,
                    'bucket' => RecruitingBucket::NTH_LEADS_AND_INQUIRIES->value,
                    'relatedLeads' => $superSededBanner
                ]);
            }
        } catch (\Exception $e) {
            H::ExcelLog(
                "getInvoiceDetails--".$e->getMessage(),
                'getNthLeadsInquiriesDetail.log'
            );
            return false;
        }
    }

    public function list(Request $request) {

        $columns = [
            ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'Recruiter', 'label' => 'Recruiter', 'width' => '15%'],
            ['name' => 'ticket_id', 'label' => 'Ticket ID', 'width' => '15%'],
            ['name' => 'clinician_name', 'label' => 'Clinician', 'width' => '20%'],
            ['name' => 'ClinicianID', 'label' => 'Clinician ID', 'width' => '20%'],
            ['name' => 'State', 'label' => 'State', 'width' => '15%'],
            ['name' => 'BranchName', 'label' => 'Branch', 'width' => '15%'],
            ['name' => 'category', 'label' => 'Category', 'width' => '15%'],
            ['name' => 'Status', 'label' => 'Status', 'width' => '15%'],
            ['name' => 'outcome', 'label' => 'Outcome', 'width' => '15%'],
        ];

        if (LD::checkFeatureFlag('nth_leads_showall_add_workflowstep')) {
                $columns[] = ['name' => 'workflow_step', 'label' => 'Workflow Step', 'width' => '15%'];
        }

        if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
                $columns[] = ['name' => 'resolution', 'label' => 'Resolution', 'width' => '15%'];
        }

        $columns[] = ['name' => 'best_time_to_contact', 'label' => 'Contact Time', 'width' => '15%'];
        $columns[] = ['name' => 'ClientName', 'label' => 'Client', 'width' => '15%'];
        $columns[] = ['name' => 'ClientID', 'label' => 'Client ID', 'width' => '15%'];
        $columns[] = ['name' => 'fcreated_at', 'label' => 'Created Date', 'width' => '15%'];
        $columns[] = ['name' => 'fupdated_at', 'label' => 'Modified Date', 'width' => '15%'];

        if ($request->ajax()) {

            [$data, $totalData, $totalFiltered] = $this->getNTHAlertsList($request, $columns, 'list');

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->dataProcess($data),
            ]);
        }
        return view('nthalerts.grid', ['columns' => $columns]);
    }

    public function exportData(Request $request)
    {
        $columns = [
            ['name' => 'Recruiter', 'label' => 'Recruiter',],
            ['name' => 'ticket_id', 'label' => 'Ticket ID'],
            ['name' => 'clinician_name', 'label' => 'Clinician'],
            ['name' => 'ClinicianID', 'label' => 'Clinician ID'],
            ['name' => 'State', 'label' => 'State'],
            ['name' => 'BranchName', 'label' => 'Branch'],
            ['name' => 'category', 'label' => 'Category'],
            ['name' => 'Status', 'label' => 'Status'],
            ['name' => 'outcome', 'label' => 'Outcome'],
        ];

        if (LD::checkFeatureFlag('nth_leads_showall_add_workflowstep')) {
                $columns[] = ['name' => 'workflow_step', 'label' => 'Workflow Step', 'width' => '15%'];
        }

        if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
                $columns[] = ['name' => 'resolution', 'label' => 'Resolution'];
                $columns[] = ['name' => 'resolution_detail', 'label' => 'Resolution Detail'];
        }

        $columns[] = ['name' => 'best_time_to_contact', 'label' => 'Contact Time'];
        $columns[] = ['name' => 'ClientName', 'label' => 'Client'];
        $columns[] = ['name' => 'ClientID', 'label' => 'Client ID'];
        $columns[] = ['name' => 'fcreated_at', 'label' => 'Created Date'];
        $columns[] = ['name' => 'fupdated_at', 'label' => 'Modified Date'];


        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=nthalerts.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        [$data, $totalData, $totalFiltered] = $this->getNTHAlertsList($request, $columns, 'export');

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

    private function dataProcess($data)
    {
       //var_dump($data);
        foreach ($data as $key => $value) {
            $value->fcreated_at = H::getFormatedDateTime($value->created_at);
            $value->fupdated_at = H::getFormatedDateTime($value->updated_at);
        }
       // var_dump($data);
       // exit;
        return $data;
    }

    private function getNTHAlertsList($request, $columns, $requestFrom)
    {
        $orderByMap = [
            'fcreated_at' => 'nth_inquiries.created_at',
            'fupdated_at' => 'nth_inquiries.updated_at',
        ];
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

        $q = NTHInquiries::select(
            "nth_inquiries.*",
            DB::raw("COALESCE(f1.State, f2.State) as State"),
            DB::raw("users.name AS Recruiter"),
            DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
            DB::raw(
                "concat(COALESCE(p1.FirstName, p2.FirstName),' ',COALESCE(p1.LastName, p2.LastName)) as clinician_name"
            ),
            DB::raw("COALESCE(f1.FacilityName, f2.FacilityName) as FacilityName"),
            DB::raw("COALESCE(f1.BranchName, f2.BranchName) as BranchName"),
            DB::raw(
                "IF(nth_inquiries.type = 'inq', 'Inquiry', CONCAT(UPPER(LEFT(nth_inquiries.type, 1)), LOWER(SUBSTRING(nth_inquiries.type, 2)))) AS category"
            ),
            DB::raw("IFNULL(mssql_patients.PatientAccount,'-') as ClientID"),
            DB::raw(
                "IF(mssql_patients.id IS NULL,'-', CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)) as ClientName"
            ),
            DB::raw(
                "CASE
                WHEN nth_inquiries.type = 'lead'
                AND nth_inquiries.Status = 'Resolved'
                AND nth_inquiries.resolution IS NULL
                THEN 'Unknown'
                ELSE nth_inquiries.resolution
                END AS resolution"
            ),
            DB::raw(
                "CASE
                    WHEN nth_inquiries.Status = '" . NTHLeadsAndInqInquiriesStatusEnum::RESOLVED->value . "'
                    AND nth_inquiries.outcome IN(
                    '" . NTHLeadsAndInqInquiriesOutcomeEnum::SCHEDULE_NEXT_STEPS->value . "',
                    '" . NTHLeadsAndInqInquiriesOutcomeEnum::SUGGEST_OTHER_CLIENTS->value . "')
                    THEN COALESCE(r.Candidate_Current_Status, 'Not Captured (Before Feature)')
                    ELSE 'Not Started' END
                    AS workflow_step"
            ),
        )
            ->leftJoin('mssql_providers as p1', 'p1.id', '=', 'nth_inquiries.aoc_provider_id')
            ->leftJoin('mssql_providers as p2','p2.ClinicianID','=','nth_inquiries.ClinicianID')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
            ->leftJoin('mssql_facilities as f1', 'f1.FacilityID', '=', 'p1.FacilityID')
            ->leftJoin('mssql_facilities as f2', 'f2.FacilityID', '=', 'p2.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'nth_inquiries.recruiter_id')
            ->leftJoin('mssql_recruitertracker as r', 'r.lead_id', '=', 'nth_inquiries.id');

        $q->where('not_interested', 0);

        $q->where(function ($qq) {
            // LEAD: patient account is required
            $qq->where(function ($lead) {
                $lead->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::LEAD->value)
                    ->whereNotNull('mssql_patients.id')
                    ->whereNotNull('mssql_patients.PatientAccount')
                    ->where('mssql_patients.PatientAccount', '!=', '');
            })
                // INQ: no patient account requirement
                ->orWhere('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value);
        });

        if (!empty($filters)) {
            if (isset($filters['nthlead_state'])) {
                $q->whereIn(DB::raw("COALESCE(p1.State, p2.State)"), $filters['nthlead_state']);
            }
            if (isset($filters['nthlead_region'])) {
                $q->whereIn(DB::raw("COALESCE(f1.region, f2.region)"), $filters['nthlead_region']);
            }
            if (isset($filters['nthlead_branch'])) {
                $q->whereIn(DB::raw("COALESCE(f1.id, f2.id)"), $filters['nthlead_branch']);
            }
            if (isset($filters['nthlead_recruiter'])) {
                $q->whereIn('users.id', $filters['nthlead_recruiter']);
            }
            if (isset($filters['nthlead_status'])) {
                $q->whereIn("nth_inquiries.Status", $filters['nthlead_status']);
            }
            if (isset($filters['nthlead_category'])) {
                $q->whereIn("nth_inquiries.type", $filters['nthlead_category']);
            }
            if (isset($filters['nthlead_resolution'])) {
                $q->whereIn('nth_inquiries.resolution', $filters['nthlead_resolution']);
            }
        }

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->where(function ($qq) use ($search) {
                $searchParam = "%{$search}%";
                $qq->whereRaw(
                    DB::Raw("CONCAT(COALESCE(p1.FirstName, p2.FirstName),' ',COALESCE(p1.LastName, p2.LastName)) LIKE ?"),
                    [$searchParam]
                )
                    ->orWhereRaw(DB::raw("COALESCE(p1.ClinicianID, p2.ClinicianID) LIKE ?"), [$searchParam])
                    ->orWhere('mssql_patients.PatientAccount', 'LIKE', $searchParam)
                    ->orWhereRaw(DB::Raw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) LIKE ?"), [$searchParam])
                    ->orWhereRaw(DB::Raw("CONCAT(UPPER(nth_inquiries.type),'-',nth_inquiries.id) LIKE ?"), [$searchParam]);
            });
            $totalFiltered = $q->count();
        }

        if ($requestFrom == 'list') {
            $data = $q->offset($start)
                ->limit($limit)
                ->orderBy($orderByMap[$order] ?? $order, $dir)
                ->get();
        } else {
            $data = $q->get()->toArray();
        }


        return [$data, $totalData, $totalFiltered];
    }



    public function getClinicianAllNthLeadsInquiries(Request $request) {
        if($request->input('type') == 'lead') {
            $columns = [
                    0 => 'ticket_id',
                ];

                $index = 1;

                if (LD::checkFeatureFlag('nth-superseded-lead-notifications')) {
                    $columns[$index++] = 'superseded_by';
                }

                $columns[$index++] = 'patient_name';
                $columns[$index++] = 'Recruiter';
                $columns[$index++] = 'outcome';

                if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
                    $columns[$index++] = 'Resolution';
                }

                $columns[$index++] = 'patient_name';
                $columns[$index++] = 'Recruiter';
                $columns[$index++] = 'outcome';
                $columns[$index++] = 'latestNotes';
        } else {
            $columns = [
                0 => 'ticket_id',
                1 => 'created_at',
                2 => 'updated_at',
                3 => 'Status',
                4 => 'Recruiter',
                5 => 'outcome',
                6 => 'latestNotes'
            ];
        }
        $search = strtolower($request->input('search.value'));
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $displayDateformat = config('constant')['mysqlsearchdatetimeformat'];
        $bucket = DB::table('nth_inquiries')
                ->select('nth_inquiries.id',
                DB::raw("DATE_FORMAT(nth_inquiries.created_at, '".$displayDateformat."') as created_at"),
                DB::raw("DATE_FORMAT(nth_inquiries.updated_at, '".$displayDateformat."') as updated_at"),
                'nth_inquiries.Status',
                'nth_inquiries.recruiter_id',
                'nth_inquiries.outcome',
                'nth_inquiries.aoc_patient_id',
                'nth_inquiries.aoc_nthaccounts_id',
                DB::raw("(select notes.notes from notes where entity = 'nthalerts' and entity_id = nth_inquiries.id order by notes.id desc limit 1) as latestNotes"),
                DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
                DB::raw("users.name AS Recruiter"),
                DB::raw("CASE WHEN nth_inquiries.aoc_nthaccounts_id = 0 THEN ' '
                            ELSE CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)
                         END as patient_name"),
                DB::raw("CASE
                WHEN nth_inquiries.type = 'lead'
                AND nth_inquiries.Status = 'Resolved'
                AND nth_inquiries.resolution IS NULL
                THEN 'Unknown'
                ELSE nth_inquiries.resolution
                END AS resolution"),
                DB::raw("(SELECT CONCAT('LEAD-', sub.id) FROM nth_inquiries sub
                WHERE sub.ClinicianID = nth_inquiries.ClinicianID
                AND sub.type = '" . NTHLeadsAndInqInquiriesTypeEnum::LEAD->value . "'
                AND sub.created_at > nth_inquiries.created_at
                AND sub.Status IN (
                    '" . NTHLeadsAndInqInquiriesStatusEnum::NEW->value . "',
                    '" . NTHLeadsAndInqInquiriesStatusEnum::PENDING->value . "')
                ORDER BY sub.created_at ASC
                LIMIT 1) AS superseded_by")
                )
                ->leftJoin('users', 'users.id', '=', 'nth_inquiries.recruiter_id')
                ->leftJoin('mssql_patients', function ($join) {
                    $join->on('mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
                         ->where('nth_inquiries.aoc_nthaccounts_id', '>', 0);
                })
                ->where('type',$request->input('type'))
                ->where('ClinicianID',$request->input('ClinicianID'));

        $bucket->where('not_interested', 0);

        if (!empty($search)) {
            $bucket->where(function ($query) use ($search){
                $query->whereRaw("concat(nth_inquiries.type,'-',nth_inquiries.id) LIKE ?", ["%{$search}%"]);
            });
        }
        $users = $status = $outcome =[];
        if(!empty($request->filters)) {
            $filters = $request->filters;
            foreach($filters as $value){
                if($value['name']=='nthlead_recruiter'){
                    $users[] = $value['value'];
                }
                if( $value['name']=='nthlead_status'){
                    $status[] = $value['value'];
                }
                if($value['name']=='nthlead_outcome'){
                    $outcome[] = $value['value'];
                }
                if($value['name']=='nthlead_resolution'){
                    $resolution[] = $value['value'];
                }
            }
        }
        if(!empty($users)){
            $bucket->whereIn("nth_inquiries.recruiter_id",$users);
        }
        if(!empty($status)){
            $bucket->whereIn("nth_inquiries.Status",$status);
        }
        if(!empty($outcome)){
            $bucket->whereIn("nth_inquiries.outcome",$outcome);
        }
        if(!empty($resolution)){
            $bucket->whereIn("nth_inquiries.resolution",$resolution);
        }
        $total_records = $bucket->get();
        $bucket = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        if($request->input('type') == 'lead') {
            $bucket = $bucket->map(function ($bucketData) {
                $bucketData->ticket_id = '<a href="javascript:void(0);" title="View" onclick="getNthLeadsInquiriesDetail(\''.$bucketData->id.'\')">
                            '.$bucketData->ticket_id.'</a>';
                $bucketData->patient_name = '<a href="javascript:void(0);" title="View" onclick="getClientDetails(\''.$bucketData->aoc_patient_id.'\')">
                            '.$bucketData->patient_name.'</a>';
                return $bucketData;
            });
        } else {
            $bucket = $bucket->map(function ($bucketData) {
                $bucketData->ticket_id = '<a href="javascript:void(0);" title="View" onclick="getNthLeadsInquiriesDetail(\''.$bucketData->id.'\')">
                            '.$bucketData->ticket_id.'</a>';
                return $bucketData;
            });
        }
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $bucket,
        ];
    }

    public function getLeadsForBulk(NthInquirySimpleLeadsRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        $leads = $this->nthInquiryReadRepository->getLeads($dto);

        return response()->json([
          'draw' => (int)$request->input('draw'),
          'recordsTotal' => $leads->count(),
          'recordsFiltered' => $leads->count(),
          'data' => $leads->toArray(),
        ]);
    }

    public function checkOtherLeads(NthInquirySimpleLeadsRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        $count = $this->nthInquiryReadRepository->getOtherLeadsCountFilteredByClinicianID($dto);

        return response()->json(['count' => $count]);
    }


    public function bulkResolveLeads(NthInquiryBulkResolveRequest $request): JsonResponse
    {
        if (!H::canPermissions(['re-assignment-nthleads-inquiries'])) {
            return response()->json([
              'success' => false,
              'message' => 'You do not have permission to resolve leads.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $dto = $request->toDTO();
            $advancingLead = $this->nthInquiryReadRepository->getLeadWithClientName($dto->advancingLeadId);

            if (!$advancingLead) {
                return response()->json([
                  'success' => false,
                  'message' => 'Advancing lead not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $result = $this->nthInquiryWriteRepository->bulkResolveLeads($dto, $advancingLead);

            return response()->json(['success' => true, ...$result]);
        } catch (\Throwable $e) {
            return response()->json([
              'success' => false,
              'message' => $e->getMessage() ?: 'Failed to resolve leads.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveLeadInquiryOutcome(Request $request){
        $failErrorMsg = 'Failed to update outcome. Please refresh the page and try again. If the issue persists, contact support.';
        $currentDateTime = date('Y-m-d H:i:s');
        try {
            $reqData = $request->all();
            if (empty($reqData['selectedId']) || empty($reqData['outcome'])) {
                return response()->json(['outcome_id' => $reqData['outcome_id'], 'status' => false, 'message' => 'Please select an outcome']);
            }

            $id = $reqData['selectedId'];
            $createdByName = Auth::user()->name;
            $noteDate = date('m/d/Y h:i A');
            $resolution = $reqData['resolution'] ?? null;
            $resolutionDetail = $reqData['resolution_detail'] ?? null;
            $finalNote = '';
            $resoluionNote = '';
            $nthInquiryData = DB::table('nth_inquiries')->where('id', $id)->first();
            if (!$nthInquiryData) {
                return response()->json(['outcome_id' => $reqData['outcome_id'],'status' => false, 'message' => $failErrorMsg]);
            }

            $updateData = [
                'outcome' => $reqData['outcome'],
                'outcome_comment' => $reqData['outcome_comment'] ?? null
            ];

            switch ($reqData['outcome_id']) {
                case 'attempt1':
                case 'attempt2':
                    $updateData['attempt_count'] = $nthInquiryData->attempt_count + 1;
                    $updateData['Status'] = 'Pending';
                    $finalNote = "$createdByName made an {$reqData['outcome']}";
                    break;
                case 'attempt3':
                    $updateData['attempt_count'] = $nthInquiryData->attempt_count + 1;
                    $updateData['resolved_at'] = $currentDateTime;
                    $updateData['resolved_by'] = Auth::user()->id;
                    $updateData['Status'] = 'Resolved';
                    $finalNote = $finalNote ?: "$createdByName resolved inquiry";
                    break;
                case 'converttolead':
                    if (empty($reqData['patient_id'])) {
                        H::ExcelLog('ERROR!!!!! Patient Id is blank', 'saveLeadInquiryOutcome.log');
                        return response()->json(['outcome_id' => $reqData['outcome_id'], 'status' => false, 'message' => $failErrorMsg]);
                    }
                    $updateData = array_merge($updateData, [
                        'Status' => 'New',
                        'outcome' => 'Converted to Lead',
                        'type' => 'lead',
                        'convertedToLead_at' => $currentDateTime,
                        'convertedToLead_by' => Auth::user()->id,
                        'aoc_patient_id' => $reqData['patient_id'],
                        'aoc_nthaccounts_id' => $reqData['aoc_nthaccounts_id']
                    ]);
                    $finalNote = "$createdByName converted inquiry to lead";
                    break;

                case 'resolved':
                    if($nthInquiryData->type === 'lead') {
                        $updateData['resolved_at'] = $currentDateTime;
                        $updateData['resolved_by'] = Auth::user()->id;
                        $updateData['Status'] = 'Resolved';
                        $updateData['resolution'] = $reqData['resolution'] ?? null;
                        $updateData['resolution_detail'] = $reqData['resolution_detail'] ?? null;
                        $resoluionNote =
                        'Resolved: ' . $resolution .
                        '<br>Detail: ' . $resolutionDetail .
                        '<br>(by ' . $createdByName . ')';
                        break;
                    }
                case 'schedulenextsteps':
                case 'suggestotherclients':
                    $updateData['resolved_at'] = $currentDateTime;
                    $updateData['resolved_by'] = Auth::user()->id;
                    $updateData['Status'] = 'Resolved';
                    $finalNote = $finalNote ?: "$createdByName resolved inquiry";
                    if ($reqData['outcome_id'] === 'suggestotherclients') {
                        $providerDetails = DB::table('mssql_providers')->where('ClinicianID', $nthInquiryData->ClinicianID)->first();
                        $finalNoteTracker = $noteDate.' '. $createdByName.' created tracker record from NTH '.$nthInquiryData->type.'-'.$nthInquiryData->id.'.';
                        $createTracker = [
                            'aoc_patient_id' => 0,
                            'Client_Name' => '',
                            'Client_ID' => '',
                            'Payor_Source' => '',
                            'Agreed_Pay_Rate' => '',
                            'GP' => '',
                            'Total_Hours' => '',
                            'Approved_By' => '',
                            'PatientID' => '',
                            'Candidate_Current_Status' => 'Need to Review New Clients',
                            'Status_Changed_Date' => $currentDateTime,
                            'Client_Match_Date' => null,
                            'Candidate_Start_Date' => null,
                            'is_active' => 1,
                            'updated_at' => $currentDateTime,
                            'created_at' => $currentDateTime,
                            'application_date' => $currentDateTime,
                            'Date_Contacted' => $currentDateTime,
                            'Candidate' => $providerDetails->fullName,
                            'Clinician_ID' => $nthInquiryData->ClinicianID,
                            'aoc_provider_id' => $providerDetails->id,
                            'Skillset' => $providerDetails->DisciplineName,
                            'Recruiter' => Auth::user()->name,
                            'user_id' => Auth::user()->id,
                            'lead_id' => $id,
                            'created_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id
                        ];
                        H::ExcelLog('insert tracker: ' . print_r($createTracker, true), 'createTrackerFromNthLead.log');
                        $data = MssqlRecruitertracker::create($createTracker);
                        $trackerId = $data->id;
                        H::getHelper('ActivityNotes')->setActivityNotes([
                            'entity' => 'tracker-activity',
                            'entity_id' => $trackerId,
                            'notes' => $finalNoteTracker
                        ]);
                    }
                    break;
            }
            if (!empty($reqData['outcome_comment'])) {
                $finalNote .= ($finalNote ? '<br>' : '') . 'Comment: ' . $reqData['outcome_comment'];
            }
            $updateData['updated_at'] = Carbon::now();
            if($reqData['outcome_id'] == 'schedulenextsteps'){
                $temp_data = [];
                $temp_data['nth_inquiries'] = $updateData;
                $temp_data['nots_data'] = [
                    'entity' => 'nthalerts',
                    'notes' => $finalNote,
                    'entity_id' => $id
                ];
                session()->put('nth_lead_data_' . $id, $temp_data);
            }else{
                DB::table('nth_inquiries')->where('id', $id)->update($updateData);
                if($finalNote !== ''){
                    H::getHelper('ActivityNotes')->setActivityNotes([
                        'entity' => 'nthalerts',
                        'notes' => $finalNote,
                        'entity_id' => $id
                    ]);
                }
                if($resoluionNote !== ''){
                    H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'nthalerts',
                    'notes' => $resoluionNote,
                    'entity_id' => $id
                    ]);
                }
            }
            return response()->json(['outcome_id' => $reqData['outcome_id'], 'status' => true, 'message' => 'Outcome updated successfully']);

        } catch (\Exception $e) {
            H::ExcelLog('saveLeadInquiryOutcome type: ' . $request->input('type') . '--' . $e->getMessage(), 'saveLeadInquiryOutcome.log');
            return response()->json(['outcome_id' => $reqData['outcome_id'],'status' => false, 'message' => $failErrorMsg]);
        }
    }

    public function getNTHClientList(Request $request) {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        H::ExcelLog('term: ' . $term,'getNTHClientList.log');
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
            'mssql_patientstatus.PatientStatus',
            'mssql_patients.CF_Skills',
			'mssql_patients.AdmitNo',
            'NeedToHire_Accounts.id as aoc_nthaccounts_id'
        )
        ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
        ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=', 'mssql_patients.Patient_CurrentStatusID')
        ->leftJoin('NeedToHire_Accounts', 'mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
        ->where('mssql_patients.IsLatestAdmit', 1)
        ->where('NeedToHire_Accounts.IsActive',1);
        $clientlist = $clientlist->where(
            function ($query) use($term) {
                $query->where('mssql_patients.Search_Name', 'like', '%' . $term . '%')
                ->orWhere('mssql_patients.PatientAccount', 'like', '%' . $term . '%');
            }
        )
        ->limit(15)
        ->get();

        $clientlist = json_decode(json_encode($clientlist), true);

        $clientListData = [];
        $cnt = 0;
        foreach ($clientlist as $clientSubData) {
                $clientListData[$cnt] = ['id' => $clientSubData['id'],'aoc_nthaccounts_id' => $clientSubData['aoc_nthaccounts_id'],
                'label' => $clientSubData['Patient_LastName'] . ',
                ' . $clientSubData['Patient_FirstName'] .' | '. $clientSubData['PatientAccount'] . ' |
                ' . $clientSubData['PatientStatus'] . ' |
                ' . $clientSubData['BranchName'] . ' |
				' . $clientSubData['AdmitNo'],
                'value' => $clientSubData['Patient_FirstName'] . ' ' . $clientSubData['Patient_LastName']
            ];
            $cnt++;
        }
        H::ExcelLog('clientlist: ' . print_r($clientListData, true),'getNTHClientList.log');
        return response()->json($clientListData);
    }
}
