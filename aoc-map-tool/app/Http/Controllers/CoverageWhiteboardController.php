<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Roles;
use App\Region;
use App\Helpers\H;
use App\Helpers\LD;
use DB;

class CoverageWhiteboardController extends Controller
{
    public function getWhiteboardPopup(Request $request) {
        $req = $request->all();
        $reqdata = json_decode($req['rowfilter']);
        $user_id = $reqdata->user_id;
        $State = $reqdata->State;
        $date = $reqdata->date;
        $type = $reqdata->type;
        $query = DB::table('coverage_tracker')
        ->select([
            'users.name as Coordinator',
            DB::raw('CONCAT(mssql_patients.Patient_FirstName, " ", mssql_patients.Patient_LastName) AS `Client`'),
            'mssql_providers.fullName as Clinician',
            'mssql_facilities.FacilityName as Location',
            'coverage_tracker.shift_date as Visit Date',
            'coverage_tracker.Schedule_Time as Start Time',
            'coverage_tracker.Hours as Planned Hours',
            'coverage_tracker.escalated_pay_rate as Escalated Pay Rate',
            DB::raw('CONCAT(FORMAT(coverage_tracker.gp, 2), "%") AS `GP%`'),
            'mssql_patients.Payer as Payer'
        ])
        ->leftJoin('mssql_patients', 'coverage_tracker.aoc_patient_id', '=', 'mssql_patients.id')
        ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.id')
        ->leftJoin('regions', 'mssql_facilities.region', '=', 'regions.id')
        ->leftJoin('users', 'coverage_tracker.created_by', '=', 'users.id')
        ->leftJoin('mssql_providers', 'coverage_tracker.aoc_provider_id', '=', 'mssql_providers.id')
        ->where('coverage_tracker.created_by', $user_id)
        ->where('mssql_facilities.State', $State)
        ->orderBy(DB::raw('CAST(coverage_tracker.created_at AS DATE)'));
        if($type == 'day'){
            $query = $query->whereDate('coverage_tracker.created_at', $date);
        }elseif($type == 'wtd'){
            $givenDate = date('Y-m-d', strtotime($date));
            $mondayDate = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $query = $query->whereDate('coverage_tracker.created_at', '>=', $mondayDate)
                   ->whereDate('coverage_tracker.created_at', '<=', $givenDate);
        }
        if($query) {
            $result = $query->get()->toArray();
        }

        return view('reports.popup',['data'=>$request->all(),"results"=>count($result)>0?$result:[]]);  
    }

    public function coverageWhiteBoard()
    {
        $excludeTerminatedEmp = '';
        if (LD::checkFeatureFlag('exclude-terminated-employees-coverage-whiteboard')) {
            $excludeTerminatedEmp = ' AND users.status =1 ';
        }
        if (LD::checkFeatureFlag('remove-branch-region-coverage-whiteboard')) {
            $qry = 'select  coverage_tracker.id as id,
                CONCAT(regions.region_name) as regionname,
                mssql_facilities.FacilityName,
                mssql_facilities.BranchName,
				mssql_facilities.State,
                users.name as user_name,
				'.(LD::checkFeatureFlag('exclude-goal-for-outside-of-payroall') ? 'IF((select
				FacilityID from mssql_providers where mssql_providers.AOC_Email = users.email limit 1)
				= mssql_patients.FacilityID, users.coverage_booked_goal, 0) as coverage_booked_goal'
				: 'users.coverage_booked_goal').',
                users.id as user_id
                from `coverage_tracker`
                left join mssql_patients on coverage_tracker.aoc_patient_id = mssql_patients.id
                left join users on coverage_tracker.created_by = users.id
                left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id
                left join regions on mssql_facilities.region = regions.id
                WHERE users.coverage_booked_goal IS NOT NULL'.$excludeTerminatedEmp.'
                group by mssql_facilities.State, user_name
                order by mssql_facilities.State, user_name';
        } else {
            $qry = 'select  coverage_tracker.id as id,
            CONCAT(regions.region_name) as regionname,
            mssql_facilities.FacilityName,
            mssql_facilities.BranchName,
            users.name as user_name,
            users.coverage_booked_goal,
            users.id as user_id
            from `coverage_tracker`
            left join mssql_patients on coverage_tracker.aoc_patient_id = mssql_patients.id
            left join users on coverage_tracker.created_by = users.id
            left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id
            left join regions on mssql_facilities.region = regions.id
            WHERE users.coverage_booked_goal IS NOT NULL'.$excludeTerminatedEmp.'
            group by regions.region_name, mssql_facilities.FacilityName, user_name
            order by case when
            regions.region_name = "Out of Territory" then "zz" else regions.region_name end,
            mssql_facilities.FacilityName, user_name';
        }
        //H::ExcelLog($qry, 'coveragedbwhiteboard.log');
        $tmp = DB::select($qry);
        $tmp = json_decode(json_encode($tmp), true);
        $wb = [];
        foreach ($tmp as $user) {
            $wb[$user['id']] = [
                'id' => $user['id'],
                'user_id' => $user['user_id'],
                'name' => $user['user_name'],
                'roles' => H::getRolesAbbreviationForReport($user['user_id']),
                'row_number' => $user['id'],
                'row_id' => $user['FacilityName'],
                'show_row' => 0,
                'territory' => $user['BranchName'],
                'candidate_start_hour_goals' => $user['coverage_booked_goal'],
                'Monday' => ['CSH' => 0],
                'Tuesday' => ['CSH' => 0],
                'Wednesday' => ['CSH' => 0],
                'Thursday' => [
                    'CSH' => 0
                ],
                'Friday' => [
                    'CSH' => 0
                ],
                'Saturday' => [
                    'CSH' => 0
                ],
                'Sunday' => [
                    'CSH' => 0
                ],
                'wtd' => [
                    'Monday' => [
                        'CSH' => 0
                    ],
                    'Tuesday' => [
                        'CSH' => 0
                    ],
                    'Wednesday' => [
                        'CSH' => 0
                    ],
                    'Thursday' => [
                        'CSH' => 0
                    ],
                    'Friday' => [
                        'CSH' => 0
                    ],
                    'Saturday' => [
                        'CSH' => 0
                    ],
                    'Sunday' => [
                        'CSH' => 0
                    ],
                ],

            ];

            $wb[$user['id']]['regionname'] = $user['regionname'];
			$wb[$user['id']]['State'] = $user['State'];
        }

        //H::ExcelLog('start queries','coveragedbWhiteboard.log');
        foreach ($wb as $key => $wbRow) {
            $rowNumber = $wbRow['id'];
            $userId = $wbRow['user_id'] ?: -999;
            $rowId = $wbRow['row_id'];
            $recruiter = $wbRow['name'];
            $lastSunday = date('Y-m-d', strtotime('last Sunday'));
            $nextMonday = date('Y-m-d', strtotime('next Monday'));
            $rounds = ['CSH'];
            foreach ($rounds as $round) {
                $qry = '';
                if ($round == 'CSH') {
                        //$lastSunday = '2023-03-05';
                        //$nextMonday = '2023-03-13';
						$qry = 'select "CSH" as type,
                            cast(coverage_tracker.created_at as date) as scheduled_date,
                            sum(coverage_tracker.Hours) as total
                            from `coverage_tracker`
                            left join mssql_patients on coverage_tracker.aoc_patient_id = mssql_patients.id
                            left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id
                            left join regions on mssql_facilities.region = regions.id
                            where coverage_tracker.created_by = ' . $userId . ' and mssql_facilities.State = "' . $wbRow['State'] . '"
                            and coverage_tracker.created_at > "' . $lastSunday . '" and coverage_tracker.created_at < "' . $nextMonday . '"
                            group by cast(coverage_tracker.created_at as date)
                            order by cast(coverage_tracker.created_at as date)';
                }
                //H::ExcelLog($qry,'coveragedbWhiteboard.log');
                //echo $wbRow['name'] . '<br /><br />';
                //echo $qry . '<br /><br /><br />';
                $mainData = DB::select($qry);
                //echo '<pre>';
                //var_dump($mainData);
                //echo '</pre>';
                //echo '<br /><br /><br />';
                $mainData = json_decode(json_encode($mainData), true);
                //now fill the buckets into the chart rows
                $count = 0;

                foreach ($mainData as $row) {
                    if ($row['total'] > 0) {
                        $wb[$rowNumber]['show_row'] = 1;
                        $type = $row['type'];
                        $date = $row['scheduled_date'];
                        //set day of week
                        $tmp = strtotime($date);
                        $day = date('l', $tmp);

                        //echo $rowNumber . ' - ' . $day . ' - ' . $type . '<br />';
                        $wb[$rowNumber][$day][$type] = $row['total'];
                        if ($type != '') {
                            if ($day == 'Monday') {
                                $wb[$rowNumber]['wtd']['Monday'][$type] = $wb[$rowNumber]['wtd']['Monday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Tuesday'][$type] = $wb[$rowNumber]['wtd']['Tuesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Monday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Tuesday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Wednesday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Thursday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Friday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Tuesday') {
                                $wb[$rowNumber]['wtd']['Tuesday'][$type] = $wb[$rowNumber]['wtd']['Tuesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Tuesday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Wednesday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Thursday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Friday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Wednesday') {
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Wednesday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Thursday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Friday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Thursday') {
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Thursday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Friday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Friday') {
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Friday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Saturday') {
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Saturday']['date'] = $date;
                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            } elseif ($day == 'Sunday') {
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];

                                $wb[$rowNumber]['wtd']['Sunday']['date'] = $date;
                            }
                            $count++;
                        }
                    }
                }
            }
            //H::ExcelLog(print_r($wb, true), 'coveragedbWhiteboard.log');
            //echo '<pre>';
            //var_dump($wb[$rowNumber]);
            //echo '</pre><br /><br /><br /><br />';
        }
        $wb['9999']['row_id'] = '9999';
        if (LD::checkFeatureFlag('remove-branch-region-coverage-whiteboard')) {
            return view('coveragewhitedashboard.newgride', ['wb' => $wb]);
        } else {
            return view('coveragewhitedashboard.gride', ['wb' => $wb]);
        }
    }
}
