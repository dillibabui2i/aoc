<?php

namespace App\Helpers;

use App\Enums\NthInquiriesStatus;
use App\Enums\NTHLeadsAndInqInquiriesTypeEnum;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NTHAlerts
{
    private const LATE_THRESHOLD_MINUTES = 60;

    /**
     * @param $type
     * @return Application|Factory|View|void
     */
    public function getNTHAlertsDatatable($type)
    {
        $dataTable = [];
        if ($type == 'nth_leads') {
            $dataTableFields = ['Recruiter'=>'Recruiter',
            'ticket_id'=>'Ticket ID',
            'clinician_name'=>'Clinician',
            'ClinicianID'=>'Clinician ID',
            'State' => 'State',
            'BranchName'=>'Branch',
            'category'=>'Category',
            'Status'=>'Status',
            'best_time_to_contact'=>'Contact Time',
            'ClientName'=>'Client',
            'ClientID'=>'Client ID',
            'created_at'=>'Created Date',
            'updated_at'=>'Modified Date',
            'Notes'=>'Notes',
            ];
            if (H::canPermissions(['selfassignment-nthleads-inquiries', 're-assignment-nthleads-inquiries'])) {
                $dataTableSelect = ['select'=>'select'];
                $dataTable = array_merge($dataTableSelect, $dataTableFields);
            } else {
                $dataTable = $dataTableFields;
            }

            return view('nthalerts.leads_datatable', ['type'=>$type, 'dataTable'=>$dataTable]);
        }
    }

    /**
     * @param $request
     * @param $showCount
     * @return string|array
     */
    public function getNTHAlertsData($request = '', $showCount = 0)
    {
        $bucket = DB::table('nth_inquiries')
        ->select(
            'nth_inquiries.*',
            'mssql_providers.State',
            DB::raw('users.name AS Recruiter'),
            DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
            DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as clinician_name"),
            'mssql_facilities.FacilityName',
            'mssql_facilities.BranchName',
            DB::raw("DATE_FORMAT(nth_inquiries.created_at, '%m/%d/%y %l:%i %p') as created_at"),
            DB::raw("IF(nth_inquiries.type = 'inq', 'Inquiry',CONCAT(UPPER(LEFT(nth_inquiries.type, 1)), LOWER(SUBSTRING(nth_inquiries.type, 2)) )  ) AS category"),
            DB::raw("IF(nth_inquiries.updated_at = '', '-', DATE_FORMAT(nth_inquiries.updated_at, '%m/%d/%y %l:%i %p')) AS updated_at"),
            DB::raw("(select notes.notes from notes where entity = 'nthalerts' and entity_id = nth_inquiries.id order by notes.id desc limit 1) as Notes"),
            DB::raw("IFNULL(mssql_patients.PatientAccount,'-') as ClientID"),
            DB::raw("IF(mssql_patients.id IS NULL,'-', CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)) as ClientName")
        )
            ->leftJoin('mssql_providers', 'mssql_providers.ClinicianID', '=', 'nth_inquiries.ClinicianID')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'nth_inquiries.recruiter_id');

        $branch = [];
        $state = [];
        $users = [];
        $category = [];
        $region = [];
        $status = [];

        $searchValue = $request['search']['value'] ?? null;
        $isTicketSearch = false;
        $searchedId = null;
        $searchedType = null;

        if ($searchValue && preg_match('/^(LEAD)-(\d+)$/i', trim($searchValue), $m)) {
            $isTicketSearch = true;
            $searchedType = 'lead';
            $searchedId = (int)$m[2];
        }

        $isMeMode = 0;
        $isLateFilter = false;
        if (isset($request['filters'])) {
            foreach ($request['filters'] as $value) {
                if ($value['name'] == 'nthlead_memode') {
                    $isMeMode = 1;
                }
                if ($value['name'] === 'nthlead_late') {
                    $isLateFilter = true;
                }
                if ($value['name'] == 'nthlead_state') {
                    $state[] = $value['value'];
                }
                if ($value['name'] == 'nthlead_region') {
                    $region[] = $value['value'];
                }
                if ($value['name'] == 'nthlead_branch') {
                    $branch[] = $value['value'];
                }
                if ($value['name'] == 'nthlead_recruiter') {
                    $users[] = $value['value'];
                }
                if ($value['name'] == 'nthlead_category') {
                    $category[] = $value['value'];
                }
                if ($value['name'] == 'nthlead_status') {
                    $status[] = $value['value'];
                }
            }
        }

        if (!empty($state)) {
            $bucket->whereIn('mssql_providers.State', $state);
        }
        if (!empty($branch)) {
            $bucket->whereIn('mssql_facilities.id', $branch);
        }
        if (!empty($region)) {
            $bucket->whereIn('mssql_facilities.region', $region);
        }

        if (!empty($users)) {
            $bucket->whereIn('nth_inquiries.recruiter_id', $users);
        } elseif ($isMeMode) {
            $bucket->where('nth_inquiries.recruiter_id', '=', Auth::user()->id);
        }

        if (!empty($status)) {
            $bucket->whereIn('nth_inquiries.Status', $status);
        } else {
            $bucket->where('nth_inquiries.Status', '!=', NthInquiriesStatus::RESOLVED->value);
        }
        if (!empty($category)) {
            $bucket->whereIn('nth_inquiries.type', $category);
        }
        $bucket->where('not_interested', 0);

        $bucket->where(function ($q) {
            // LEAD: patient account is required
            $q->where(function ($lead) {
                $lead->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::LEAD->value)
                    ->whereNotNull('mssql_patients.id')
                    ->whereNotNull('mssql_patients.PatientAccount')
                    ->where('mssql_patients.PatientAccount', '!=', '');
            })
                // INQ: no patient account requirement
                ->orWhere('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value);
        });


        $lateCutoff = now()->subMinutes(self::LATE_THRESHOLD_MINUTES)->toDateTimeString();

        $bucket->selectRaw(
            '(nth_inquiries.Status = ? AND nth_inquiries.created_at < ?) AS is_late',
            [NthInquiriesStatus::NEW->value, $lateCutoff]
        );

        if ($isLateFilter) {
            $bucket->where('nth_inquiries.Status', NthInquiriesStatus::NEW->value)
                ->where('nth_inquiries.created_at', '<', $lateCutoff);
        }

        if (LD::checkFeatureFlag('nth-leads-latest-lead-per-clinician')) {

            $count = $this->geHeaderTotalStats($bucket);

            $this->addSelectNewAndPendingCount($bucket);

            if($isTicketSearch) {
                $this->addSupersededIdOnSearchByLead($bucket, $searchedId, $searchedType);
                return [$bucket->get(), $count ?? null];
            }

            $bucket->where(function ($q) {
                $q->where(function ($inq) {
                        // Inq — latest per provider
                        $inq->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value);
                        $this->filterByNewestInq($inq);
                    })
                    // LEAD — latest per provider
                    ->orWhere(function ($lead) {
                        $lead->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::LEAD->value);
                        $this->filterByNewestLead($lead);
                    });
            });
        }

        if ($showCount) {
            return '(' . $bucket->count() . ')';
        }

        $data = $bucket->get();

        return [$data, $count ?? null];
    }

    private function filterByNewestLead(Builder $bucket): void
    {
        $latestPerProvider = DB::table('nth_inquiries as ni_latest')
            ->selectRaw('MAX(ni_latest.id)')
            ->where('ni_latest.type', NTHLeadsAndInqInquiriesTypeEnum::LEAD->value)
            ->leftJoin('mssql_patients as mp', 'mp.id', '=', 'ni_latest.aoc_patient_id')
            ->where('ni_latest.not_interested', 0)
            ->where('ni_latest.Status', '!=', NthInquiriesStatus::RESOLVED->value)
            ->whereNotNull('mp.id')
            ->whereNotNull('mp.PatientAccount')
            ->where('mp.PatientAccount', '!=', '')
            ->groupBy('ni_latest.aoc_provider_id');

         $bucket->whereIn('nth_inquiries.id', $latestPerProvider);
    }

    private function filterByNewestInq(Builder $bucket): void
    {
        $latestPerProvider = DB::table('nth_inquiries as ni_latest')
            ->selectRaw('MAX(ni_latest.id)')
            ->where('ni_latest.type', NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value)
            ->leftJoin('mssql_patients as mp', 'mp.id', '=', 'ni_latest.aoc_patient_id')
            ->where('ni_latest.not_interested', 0)
            ->where('ni_latest.Status', '!=', NthInquiriesStatus::RESOLVED->value)
            ->groupBy('ni_latest.aoc_provider_id');

        $bucket->whereIn('nth_inquiries.id', $latestPerProvider);
    }

    private function addSelectNewAndPendingCount(Builder $bucket): void
    {
        $new = NthInquiriesStatus::NEW->value;
        $pending = NthInquiriesStatus::PENDING->value;
        $inq = NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value;

        $bucket->addSelect(

            DB::raw(
                "(
            SELECT COUNT(*)
            FROM nth_inquiries ni2
            WHERE ni2.aoc_provider_id = nth_inquiries.aoc_provider_id
              AND ni2.Status = '{$new}'
              AND ni2.type = nth_inquiries.type
              AND ni2.not_interested = 0
            ) AS tooltip_new_count"
            ),

            DB::raw(
                "
            (
                SELECT COUNT(*)
                FROM nth_inquiries ni2
                WHERE ni2.aoc_provider_id = nth_inquiries.aoc_provider_id
                  AND ni2.Status = '{$pending}'
                  AND ni2.type = nth_inquiries.type
                  AND ni2.not_interested = 0
            ) AS tooltip_pending_count"
            )
        );
    }

    private function geHeaderTotalStats($baseQuery): string
    {
        $statsQuery = clone $baseQuery;

        $leadValue = NTHLeadsAndInqInquiriesTypeEnum::LEAD->value;
        $inqValue  = NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value;

        $statsQuery
            ->selectRaw('COUNT(DISTINCT `nth_inquiries`.`aoc_provider_id`) as clinicians_count')
            ->selectRaw('SUM(CASE WHEN `nth_inquiries`.`type` = ? THEN 1 ELSE 0 END) as leads_count', [$leadValue])
            ->selectRaw('SUM(CASE WHEN `nth_inquiries`.`type` = ? THEN 1 ELSE 0 END) as inquiries_count', [$inqValue]);

        $stats = $statsQuery->first();

        $data = [
            'clinicians' => $stats?->clinicians_count ?? 0,
            'leads'      => $stats?->leads_count      ?? 0,
            'inquiries'  => $stats?->inquiries_count  ?? 0,
        ];

        return sprintf(
            '(%d Clinicians | %d Leads & %d Inquiries)',
            $data['clinicians'],
            $data['leads'],
            $data['inquiries']
        );
    }

    private function addSupersededIdOnSearchByLead(Builder $bucket, int $searchedId, string $searchedType): void
    {
        $searchedLead = DB::table('nth_inquiries')
            ->where('id', $searchedId)
            ->where('type', $searchedType)
            ->where('not_interested', 0)
            ->where('Status', '!=', NthInquiriesStatus::RESOLVED->value)
            ->first();

        if ($searchedLead) {
            $newestLeadId = DB::table('nth_inquiries as ni')
                ->leftJoin('mssql_patients as mp', 'mp.id', '=', 'ni.aoc_patient_id')
                ->where('ni.aoc_provider_id', $searchedLead->aoc_provider_id)
                ->where('ni.not_interested', 0)
                ->where('ni.Status', '!=', NthInquiriesStatus::RESOLVED->value)
                ->whereNotNull('mp.id')
                ->whereNotNull('mp.PatientAccount')
                ->where('mp.PatientAccount', '!=', '')
                ->orderByDesc('ni.created_at')
                ->value('ni.id');

            $bucket->where('nth_inquiries.id', $searchedLead->id);

            if ($newestLeadId && $newestLeadId != $searchedLead->id) {
                $bucket->addSelect(
                    DB::raw(
                        "
                            CASE
                                WHEN {$searchedLead->id} != {$newestLeadId}
                                THEN {$newestLeadId}
                                ELSE NULL
                            END AS superseded_id
                        "
                    )
                );
            }
        }
    }
}
