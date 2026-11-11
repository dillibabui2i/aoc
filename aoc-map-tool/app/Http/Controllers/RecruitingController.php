<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlRecruitertracker;
use App\Enums\MssqlRecruiterTrackerBonusStatus;
use DB;
use Illuminate\Support\Facades\Auth;

class RecruitingController extends Controller
{
    public function recruiting(Request $request)
    {

        if ($request->ajax()) {

            //Keep Bonus_Status fresh for whoever is viewing this report right now, rather than
            //waiting for the next process:hoursworked run (see markCrossRecruiterDuplicatesIneligible).
            H::getHelper('RecruiterTrackerHelper')->markCrossRecruiterDuplicatesIneligible();

            $columns = [
                0 => 'Recruiter',
                1 => 'Branch_Location',
                2 => 'Candidate',
                3 => 'mssql_patients.PatientName',
                4 => 'Candidate_Start_Date',
                5 => 'Hours_Worked',
                6 => 'bonus_amount',
                7 => 'bonus_override',
                8 => 'Bonus_Status',
                9 => 'bonus_tier',
                10 => 'margin',
                11 => 'actual_gp',
                12 => 'final_bonus_amount',
                13 => 'Bonus_Paid_Date',
            ];

            $recruitertrackers = MssqlRecruitertracker::select(
                    'mssql_recruitertracker.*',
                    'mssql_facilities.FacilityName as FacilityName',
                    'mssql_patients.PatientName as Client',
                    DB::raw("DATE_FORMAT(mssql_recruitertracker.Candidate_Start_Date,'%m/%d/%Y') AS Formatted_Candidate_Start_Date"),
                    DB::raw("case when ifnull(mssql_recruitertracker.bonus_override,0) = 0 then mssql_recruitertracker.bonus_amount else
                    mssql_recruitertracker.bonus_override end as final_bonus_amount"),
                    DB::raw("ROUND(mssql_recruitertracker.actual_gp * 100, 2) AS margin"),
                    DB::raw("DATE_FORMAT(mssql_recruitertracker.Bonus_Paid_Date,'%m/%d/%Y') AS Formatted_Bonus_Paid_Date")
                )
                ->where('Candidate_Current_Status', 'First Shift Confirmed')
                ->where('mssql_recruitertracker.Candidate_Start_Date', '>', '2022-12-31')
                ->where('mssql_patients.Patient_CurrentStatusID', '1')
                ->where('mssql_patients.IsLatestAdmit', '1')
                ->leftjoin('mssql_patients', 'mssql_patients.id', '=', 'mssql_recruitertracker.aoc_patient_id')
                ->leftjoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
            if (isset($request->search['value']) && $request->search['value'] != '') {
                $recruitertrackers = $recruitertrackers->where(function ($query) use ($request) {
                    $searchValue = '%' . $request['search']['value']. '%';
                    $query->where('Recruiter', 'like', $searchValue)
                          ->orWhere('Candidate', 'like', $searchValue)
                          ->orWhere('Branch_Location', 'like', $searchValue)
                          ->orWhere('Candidate_Current_Status', 'like', $searchValue)
                          ->orWhere('mssql_patients.PatientName', 'like', $searchValue)
                          ->orWhere('Bonus_Status', 'like', $searchValue);
                });
            }
            $users = H::getLoggedInUser();
            if(H::canPermissions('manage-recruiter-bonus')){
                H::ExcelLog('manager','dbrecruiterbonus.log');
            }elseif(H::canPermissions('is_recruiting_team_leader') || H::canPermissions('is_recruiting_manager')){
                H::ExcelLog('team lead','dbrecruiterbonus.log');
                $allSubOrdinates = H::getHelper('UserPermissionResolver')->getAllSubordinatesFromCurrentUser($users['id']);
                H::ExcelLog(print_r($allSubOrdinates,true),'dbrecruiterbonus.log');
                $recruitertrackers->whereIn('Recruiter', $allSubOrdinates);
            }else{
                H::ExcelLog('recruiter','dbrecruiterbonus.log');
                $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.user_id', '=', $users['id']);
            }
            if (isset($request['filters'])) {
                $filters = $request['filters'];
                H::ExcelLog(print_r($filters,true),'dbrecruiterbonus.log');
                $stats = [];
                $branch = [];
                $start_date = '';
                $end_date = '';
                foreach ($filters as $filter) {
                    if ($filter['name'] == 'Recruiter' && !empty($filter['value'])) {
                        $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Recruiter', '=', $filter['value']);
                    }
                    if ($filter['name'] == 'Bonus_Status' && !empty($filter['value'])) {
                        $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Bonus_Status', '=', $filter['value']);
                    }
                    if ($filter['name'] == 'state' && !empty($filter['value'])) {
                        $stats[] = $filter['value'];
                    }
                    if ($filter['name'] == 'aoc_facility_id' && !empty($filter['value'])) {
                        $branch[] = $filter['value'];
                    }
                    if ($filter['name'] == 'excludeprn' && !empty($filter['value'])) {
                        $recruitertrackers = $recruitertrackers->where(function ($query){
                            $query->where('mssql_patients.isPRN', '=', '0')
                                  ->orWhereNull('mssql_patients.isPRN');
                        });
                    }
                    if($filter['name'] == 'from_date' && !empty($filter['value'])){
                        $start_date = $filter['value'];
                    }
                    if($filter['name'] == 'to_date' && !empty($filter['value'])){
                        $end_date = $filter['value'];
                    }
                }

                if(!empty($stats)){
                    $recruitertrackers = $recruitertrackers->whereIn('mssql_facilities.State', $stats);
                }
                if(!empty($branch)){
                    $recruitertrackers = $recruitertrackers->whereIn('mssql_patients.aoc_facility_id', $branch);
                }
                if(!empty($start_date) && !empty($end_date)){
                    $recruitertrackers->whereBetween('mssql_recruitertracker.Bonus_Paid_Date', [date('Y-m-d 00:00:00', strtotime($start_date)), date('Y-m-d 23:59:59', strtotime($end_date))]);
                }
            }

            if(LD::checkFeatureFlag('exclude-bonus-for-same-candidate-client')) {
                $recruitertrackers->addSelect( DB::raw('IFNULL(DATEDIFF(
                    LEAD(Candidate_Start_Date) OVER (PARTITION BY mssql_recruitertracker.Recruiter, mssql_patients.PatientName,
                    mssql_recruitertracker.Candidate ORDER BY Candidate_Start_Date), Candidate_Start_Date
                ),0) AS date_diff'),);

                $bindings = $recruitertrackers->getBindings();

                $recruitertrackers = DB::table(DB::raw("({$recruitertrackers->toSql()}) as main"))
                ->select('main.*')
                ->whereRaw('(main.date_diff = ? OR main.date_diff >= ?)')
                ->setBindings(array_merge($bindings, ['0', '30']));
            }

            if (isset($request['order'][0]) && $request['order'][0] != '') {
                $recruitertrackers = $recruitertrackers->orderby(
                    $columns[intval($request['order'][0]['column'])],
                    $request['order'][0]['dir']
                );
            }
            $total_records = $recruitertrackers->get();
            //Bonus Ineligible trackers stay in the list/count for audit (AC16), but their
            //(possibly stale, pre-ineligibility) amount must not count toward the total shown.
            $totalBonusAmount = $total_records
                ->where('Bonus_Status', '!=', MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value)
                ->sum('final_bonus_amount');
            if (isset($request['start']) && $request['length'] != '-1') {
                $recruitertrackers = $recruitertrackers
                                            ->offset($request['start'])
                                            ->limit($request['length']);
            }
            H::ExcelLog(print_r($recruitertrackers->toSql(),true),'dbBonusPage.log');
            $recruitertrackers = $recruitertrackers->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval(count($total_records)),
                'recordsFiltered' => intval(count($total_records)),
                'data'            => $recruitertrackers,
                'totalbonus'      => $totalBonusAmount,
            ]);
        }
        return view('reports.bonusreport');
    }

    public function exportBonusReport(Request $request){

        $columns = [
            0 => 'Recruiter',
            1 => 'Branch_Location',
            2 => 'Candidate',
            3 => 'mssql_patients.PatientName',
            4 => 'Candidate_Start_Date',
            5 => 'Hours_Worked',
            6 => 'bonus_amount',
            7 => 'bonus_override',
            8 => 'Bonus_Status',
            9 => 'bonus_tier',
            10 => 'actual_gp_pct',
            11 => 'bonus_calc_goals',
            12 => 'bonus_calc_pct_to_goal',
            13 => 'bonus_calc_total_hours',
        ];

        $dataTableFields = [
            'Recruiter' => 'Recruiter',
            'Branch_Location' => 'Branch',
            'Candidate' => 'Candidate',
            'Client' => 'Client',
            'Formatted_Candidate_Start_Date' => 'Candidate Start Date',
            'Hours_Worked' => 'Hours Worked',
            'bonus_amount' => 'Bonus',
            'bonus_override' => 'Bonus Override',
            'Bonus_Status' => 'Bonus Status',
            'bonus_tier' => 'Tier',
            'actual_gp_pct' => 'Actual GP',
            'bonus_calc_goals' => 'Bonus Goals',
            'bonus_calc_pct_to_goal' => 'Pct to Goal',
            'bonus_calc_total_hours' => 'Recruiter Total Hours',

        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=Bonusreport.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $recruitertrackers = MssqlRecruitertracker::select('mssql_recruitertracker.*', 'mssql_recruitertracker.actual_gp as actual_gp_pct',
                'mssql_facilities.FacilityName as FacilityName', 'mssql_patients.PatientName as Client',
            DB::raw("DATE_FORMAT(mssql_recruitertracker.Candidate_Start_Date,'%m/%d/%Y') AS Formatted_Candidate_Start_Date"))
            ->where('Candidate_Current_Status', 'First Shift Confirmed')
            ->where('Candidate_Start_Date', '>','2022-12-31')
            ->leftjoin('mssql_patients', 'mssql_patients.id', '=', 'mssql_recruitertracker.aoc_patient_id')
            ->leftjoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $recruitertrackers = $recruitertrackers->where(function ($query) use ($request) {
                $searchValue = '%' . $request['search']['value'] . '%';
                $query->where('Recruiter', 'like', $searchValue)
                      ->orWhere('Candidate', 'like', $searchValue)
                      ->orWhere('Branch_Location', 'like', $searchValue)
                      ->orWhere('Candidate_Current_Status', 'like', $searchValue)
                      ->orWhere('mssql_patients.PatientName', 'like', $searchValue)
                      ->orWhere('Bonus_Status', 'like', $searchValue);
            });
        }
        if(!H::canPermissions('manage-recruiter-bonus')){
            $users = H::getLoggedInUser();
            $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.user_id', '=', $users['id']);
        }
        if (isset($request['filters'])) {
            $filters = $request['filters'];
            $stats = [];
            $branch = [];
            $start_date = '';
            $end_date = '';
            foreach ($filters as $filter) {
                if ($filter['name'] == 'Recruiter' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Recruiter', '=', $filter['value']);
                }
                if ($filter['name'] == 'Bonus_Status' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Bonus_Status', '=', $filter['value']);
                }
                if ($filter['name'] == 'state' && !empty($filter['value'])) {
                    $stats[] = $filter['value'];
                }
                if ($filter['name'] == 'aoc_facility_id' && !empty($filter['value'])) {
                    $branch[] = $filter['value'];
                }
                if ($filter['name'] == 'excludeprn' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where(function ($query){
                        $query->where('mssql_patients.isPRN', '=', '0')
                              ->orWhereNull('mssql_patients.isPRN');
                    });
                }
                if($filter['name'] == 'from_date' && !empty($filter['value'])){
                    $start_date = $filter['value'];
                }
                if($filter['name'] == 'to_date' && !empty($filter['value'])){
                    $end_date = $filter['value'];
                }
            }

            if(!empty($stats)){
                $recruitertrackers = $recruitertrackers->whereIn('mssql_facilities.State', $stats);
            }
            if(!empty($branch)){
                $recruitertrackers = $recruitertrackers->whereIn('mssql_patients.aoc_facility_id', $branch);
            }
            if(!empty($start_date) && !empty($end_date)){
                $recruitertrackers->whereBetween('mssql_recruitertracker.Bonus_Paid_Date', [date('Y-m-d 00:00:00', strtotime($start_date)), date('Y-m-d 23:59:59', strtotime($end_date))]);
            }
        }

        if(LD::checkFeatureFlag('exclude-bonus-for-same-candidate-client')) {
            $recruitertrackers->addSelect( DB::raw('IFNULL(DATEDIFF(
                LEAD(Candidate_Start_Date) OVER (PARTITION BY mssql_recruitertracker.Recruiter, mssql_patients.PatientName,
                mssql_recruitertracker.Candidate ORDER BY Candidate_Start_Date), Candidate_Start_Date
            ),0) AS date_diff'),);

            $bindings = $recruitertrackers->getBindings();

            $recruitertrackers = DB::table(DB::raw("({$recruitertrackers->toSql()}) as main"))
            ->select('main.*')
            ->whereRaw('(main.date_diff = ? OR main.date_diff >= ?)')
            ->setBindings(array_merge($bindings, ['0', '30']));
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $recruitertrackers = $recruitertrackers->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $recruitertrackers->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $recruitertrackers = $recruitertrackers->offset($request['start'])->limit($request['length']);
        }
        $recruitertrackers = json_decode(json_encode($recruitertrackers->get()->toArray()), true);
        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $callback = function () use ($recruitertrackers, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($recruitertrackers as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                        $csvData[] = $tableData[$fields];
                    $cntr++;
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function setBonusPaidmodal(Request $request){
        //Guard against submitting a cross-recruiter duplicate to payroll before the
        //next daily process:hoursworked run would have caught it (see markCrossRecruiterDuplicatesIneligible).
        H::getHelper('RecruiterTrackerHelper')->markCrossRecruiterDuplicatesIneligible();

        $columns = [
            0 => 'Recruiter',
            1 => 'Branch_Location',
            2 => 'Candidate',
            3 => 'mssql_patients.PatientName',
            4 => 'Candidate_Start_Date',
            5 => 'Hours_Worked',
            6 => 'bonus_amount',
            7 => 'Bonus_Status',
        ];
        $date = '';
        if(!empty($request->bonusPaidDate)){
            $date = date('Y-m-d h:i:s', strtotime($request->bonusPaidDate));
        }
        $recruitertrackers = MssqlRecruitertracker::select(
            'mssql_recruitertracker.*',
            'mssql_facilities.FacilityName as FacilityName',
            'mssql_patients.PatientName as Client',
            DB::raw("DATE_FORMAT(mssql_recruitertracker.Candidate_Start_Date,'%m/%d/%Y') AS Candidate_Start_Date"),
            DB::raw(
                "case when ifnull(mssql_recruitertracker.bonus_override,0) = 0 then mssql_recruitertracker.bonus_amount else mssql_recruitertracker.bonus_override end as final_bonus_amount"
            )
        )
            ->where('Candidate_Current_Status', 'First Shift Confirmed')
            //Safety net: never submit a Bonus Ineligible tracker to payroll here, regardless of
            //whichever Bonus_Status filter the caller did or didn't apply on top of this base query.
            ->where('mssql_recruitertracker.Bonus_Status', '!=', MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value)
            ->leftjoin('mssql_patients', 'mssql_patients.id', '=', 'mssql_recruitertracker.aoc_patient_id')
            ->leftjoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id');
        if (isset($request['search']) && $request['search'] != '') {
            $recruitertrackers = $recruitertrackers->where(function ($query) use ($request) {
                $searchValue = '%' . $request['search']. '%';
                $query->where('Recruiter', 'like', $searchValue)
                      ->orWhere('Candidate', 'like', $searchValue)
                      ->orWhere('Branch_Location', 'like', $searchValue)
                      ->orWhere('Candidate_Current_Status', 'like', $searchValue)
                      ->orWhere('mssql_patients.PatientName', 'like', $searchValue)
                      ->orWhere('Bonus_Status', 'like', $searchValue);
            });
        }
        if(!H::canPermissions('view.recruiting')){
            $users = H::getLoggedInUser();
            $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.user_id', '=', $users['id']);
        }
        if (isset($request['filters'])) {
            $filters = $request['filters'];
            $stats = [];
            $branch = [];
            foreach ($filters as $filter) {
                if ($filter['name'] == 'Recruiter' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Recruiter', '=', $filter['value']);
                }
                if ($filter['name'] == 'Bonus_Status' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where('mssql_recruitertracker.Bonus_Status', '=', $filter['value']);
                }
                if ($filter['name'] == 'state' && !empty($filter['value'])) {
                    $stats[] = $filter['value'];
                }
                if ($filter['name'] == 'aoc_facility_id' && !empty($filter['value'])) {
                    $branch[] = $filter['value'];
                }
                if ($filter['name'] == 'excludeprn' && !empty($filter['value'])) {
                    $recruitertrackers = $recruitertrackers->where(function ($query){
                        $query->where('mssql_patients.isPRN', '=', '0')
                              ->orWhereNull('mssql_patients.isPRN');
                    });
                }
            }

            if(!empty($stats)){
                $recruitertrackers = $recruitertrackers->whereIn('mssql_facilities.State', $stats);
            }
            if(!empty($branch)){
                $recruitertrackers = $recruitertrackers->whereIn('mssql_patients.aoc_facility_id', $branch);
            }
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $recruitertrackers = $recruitertrackers->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $recruitertrackers->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $recruitertrackers = $recruitertrackers->offset($request['start'])->limit($request['length']);
        }
        $recruitertrackers = $recruitertrackers->get();
        foreach($recruitertrackers as $data){
            $record = MssqlRecruitertracker::where('id', $data['id'])->update(['mssql_recruitertracker.Bonus_Status' => 'Submitted To Payroll', 'mssql_recruitertracker.Bonus_Paid_Date' => $date]);

            $noteData = [];
            $noteData['entity'] = 'tracker-activity';
            $noteData['entity_id'] = $data['id'];
            $noteData['notes'] = "Bonus submitted to payroll for payment on ".$request->bonusPaidDate."";

            H::getHelper("ActivityNotes")->setActivityNotes($noteData);
        }
    }

    public function updateBonusOverRide(Request $request){
        $id = $request->id;
        $bonusoverride = $request->bonusoverride;
        $user_id = Auth::user()->id;
        $record = MssqlRecruitertracker::where('id', $id)->update(['mssql_recruitertracker.bonus_override' => $bonusoverride, 'mssql_recruitertracker.bonus_override_by' => $user_id]);
    }
}
