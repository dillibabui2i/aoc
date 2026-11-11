<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WekklyCallFormController extends Controller
{
    const CCC = "users.name AS Client_Coordinator";
    public function weeklyShowAllCall(){
        return view('weeklyShowAll.weeklygrid');
    }

    public function getAllWeeklyShowAll(Request $request)
    {
        $search = $request->input('search.value');
        $orderColumn = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            'patient_name', // 0 - Client (CONCAT of Patient_FirstName and Patient_LastName)
            'mssql_patients.PatientAccount', // 1 - ClientID
            'users.name', // 2 - Client Care Coordinator
            DB::raw("CASE WHEN ccc_followup_calls.IsPriority = 1 THEN 'Yes' ELSE 'No' END"), // 3 - Priority
            'mssql_facilities.FacilityName', // 4 - Branch
            'mssql_patients.Payer', // 5 - Payer
            'mssql_patients.CF_ServicesNeeded', // 6 - Service Needed
            DB::raw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y')"), // 7 - Date Added
            DB::raw("ROUND((SELECT SUM(ShiftHours) 
                FROM NeedToHire_Schedules 
                WHERE ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id 
                AND NeedToHire_Schedules.IsActive=1), 2)"), // 8 - Hours on NTH
            'ccc_followup_calls.outcome', // 9 - Outcome
            DB::raw("DATE_FORMAT(ccc_followup_calls.last_weekly_followup, '%m/%d/%Y')"), // 10 - Last Updated
        ];
        
    
        $bucket = DB::table("ccc_followup_calls")
            ->select(
                'ccc_followup_calls.*',
                DB::raw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) AS patient_name"),
                'mssql_patients.PatientAccount',
                DB::raw(self::CCC),
                'mssql_facilities.FacilityName',
                "mssql_patients.Payer",
                'mssql_patients.CF_ServicesNeeded',
                'mssql_patientstatus.PatientStatus',
                DB::raw("ROUND((select sum(ShiftHours)
                    from NeedToHire_Schedules
                    where ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id
                    and NeedToHire_Schedules.IsActive=1),2) as totalhours"),
                DB::raw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y') as created_at"),
                DB::raw("DATE_FORMAT(ccc_followup_calls.last_weekly_followup, '%m/%d/%Y') as last_weekly_followup"),
                DB::raw("CASE WHEN ccc_followup_calls.IsPriority = 1 THEN 'Yes' ELSE 'No' END AS IsPriority"),
                DB::raw("
                    SUBSTRING_INDEX(SUBSTRING_INDEX(notes.notes, '<p>Recap of call:', -1), '</p>', 1) AS last_recap
                ")
            );
    
        $bucket->leftJoin("mssql_patients", function ($query) {
            $query->on("mssql_patients.id", "=", "ccc_followup_calls.aoc_patient_id");
        });
        $bucket->leftJoin("mssql_facilities", function ($query) {
            $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
        });
        $bucket->leftJoin("users", function ($query) {
            $query->on("users.id", "=", "mssql_patients.ccc_id");
        });
        $bucket->leftJoin("mssql_patientstatus", function ($query) {
            $query->on("mssql_patients.Patient_CurrentStatusID", "=", "mssql_patientstatus.PatientStatusID");
        });
        $bucket->leftJoin("notes", function ($query) {
            $query->on("notes.misc3", "=", "ccc_followup_calls.id")
                  ->on("notes.entity_id", "=", "mssql_patients.id")
                  ->where("notes.entity", "=", "client")
                  ->where("notes.type", "=", "call");
        });
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
        $bucket->where("ccc_followup_calls.type", "=", 'nth');
        $this->setFilters($bucket, $request['filters']);
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search) {
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"])
                    ->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.Payer', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.CF_ServicesNeeded', 'LIKE', "%{$search}%")
                    ->orWhereRaw("ROUND((SELECT SUM(ShiftHours)
                        FROM NeedToHire_Schedules
                        WHERE ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id
                        AND NeedToHire_Schedules.IsActive=1), 2) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(ccc_followup_calls.last_weekly_followup, '%m/%d/%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CASE WHEN ccc_followup_calls.IsPriority = 1 THEN 'Yes' ELSE 'No' END LIKE ?", ["%{$search}%"]);
            });
        }
        if (isset($orderColumn) && isset($columns[$orderColumn])) {
            $bucket->orderBy($columns[$orderColumn], $orderDir);
        }
    
        H::ExcelLog('getClientCareWeeklyCallShowAll: ' . $bucket->toSql(), 'getClientCareWeeklyCallShowAll.log');
        $total_records = $bucket->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $bucket = $bucket->offset($request['start'])
                ->limit($request['length']);
        }
        $bucket = $bucket->get();
        foreach ($bucket as $bucketData) {
            if (!empty ($bucketData->last_recap)) {
                $note = $bucketData->last_recap;
                $str = '';
                if (strlen($note) > 35) {
                    $str = substr($note, 0, 35) . '...';
                } else {
                    $str = $note;
                }
                $bucketData->last_recap = "<span data-toggle='tooltip' title=\"" .
                    mb_convert_encoding($note, 'UTF-8', 'UTF-8') . "\">" .
                    mb_convert_encoding($str, 'UTF-8', 'UTF-8') .
                    '</span>';
            }else{
                $bucketData->last_recap = "-";
            }
            $bucketData->action = "";
            if($bucketData->outcome != 'Completed' && $bucketData->outcome != 'Not Complete' && (int)$bucketData->attempt_count < 2){
                $bucketData->action .= '<a href="javascript:void(0);"
                onclick="getWeeklyMonthlyInputCall(' . $bucketData->id . ', \'weekly\');">
                <i class="fa fa-calendar"></i></a>';
            }
            $bucketData->action .= '<a href="javascript:void(0);" 
            onclick="getClientDetails(' . $bucketData->aoc_patient_id . ');">
            <i class="fa fa-user"></i></a>';
        }
    
        return [
            'draw' => intval($request['draw']),
            'recordsTotal' => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data' => $bucket,
        ];
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
            $bucket->where(function ($bucket) use ($column, $values) {
                foreach ($values as $value) {
                    $bucket->orWhere($column, '=', $value);
                }
            });
        }
    }
    private function setFilters(&$bucket, $searchfilter)
    {
        $filters = $this->extractFilters($searchfilter);
        foreach ($filters as $key => $values) {
            switch ($key) {
                case 'weeklymemode':
                    $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
                    break;
                case 'weeklyccstate':
                    $this->applyFacilityFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'weeklyccbranch':
                    $this->applyFilter($bucket, 'mssql_patients.aoc_facility_id', $values);
                    break;
                case 'weeklyregion':
                    $this->applyFilter($bucket, 'mssql_facilities.region', $values);
                    break;
                case 'weeklyclientcare':
                    $this->applyFilter($bucket, 'mssql_patients.ccc_id', $values);
                    break;
                case 'weeklypayer':
                    $this->applyFilter($bucket, 'mssql_patients.Payer', $values);
                    break;
                case 'weeklypayergroup':
                    $this->applyFilter($bucket, 'mssql_patients.payer_group', $values);
                    break;
                case 'weeklyLOB':
                    $this->applyFilter($bucket, 'mssql_patients.LOBID', $values);
                    break;
                default:
                    // Do nothing
                    break;
            }
        }
    }

    public function exportWeeklyShowAll(Request $request)
    {
        $search = $request->input('search.value');
        $orderColumn = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = [
            DB::raw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y')"), // Date Added
            'users.name', // Client Care Coordinator
            'patient_name', // Client
            'mssql_patients.PatientAccount', // ClientID
            'mssql_facilities.FacilityName', // Branch
            'mssql_patients.Payer', // Payer
            'mssql_patients.CF_ServicesNeeded', // Service Needed
            DB::raw("ROUND((SELECT SUM(ShiftHours) 
                FROM NeedToHire_Schedules 
                WHERE ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id 
                AND NeedToHire_Schedules.IsActive=1), 2)"), // Hours on NTH
            'ccc_followup_calls.outcome', // Outcome
        ];

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=weeklyShowAll.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $bucket = DB::table("ccc_followup_calls")
            ->select(
                DB::raw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) AS patient_name"),
                'mssql_patients.PatientAccount',
                'users.name AS ClientCareCoordinator',
                DB::raw("CASE WHEN ccc_followup_calls.IsPriority = 1 THEN 'Yes' ELSE 'No' END AS Priority"),
                'mssql_facilities.FacilityName',
                'mssql_patients.Payer',
                'mssql_patients.CF_ServicesNeeded',
                DB::raw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y') AS DateAdded"),
                DB::raw("ROUND((SELECT SUM(ShiftHours) 
                    FROM NeedToHire_Schedules 
                    WHERE ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id 
                    AND NeedToHire_Schedules.IsActive=1), 2) AS HoursOnNTH"),
                'ccc_followup_calls.outcome AS Outcome',
                DB::raw("DATE_FORMAT(ccc_followup_calls.last_weekly_followup, '%m/%d/%Y') AS LastUpdated"),
                "notes.notes",
            )
            ->leftJoin("mssql_patients", "mssql_patients.id", "=", "ccc_followup_calls.aoc_patient_id")
            ->leftJoin("mssql_facilities", "mssql_patients.aoc_facility_id", "=", "mssql_facilities.id")
            ->leftJoin("users", "users.id", "=", "mssql_patients.ccc_id")
            ->leftJoin("notes", function ($query) {
                $query->on("notes.misc3", "=", "ccc_followup_calls.id")
                      ->on("notes.entity_id", "=", "mssql_patients.id")
                      ->where("notes.entity", "=", "client")
                      ->where("notes.type", "=", "call");
            })
            ->where("ccc_followup_calls.type", '=', 'nth');
        if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')){
            $bucket->addSelect('mssql_patients.LOBID');
            $bucket->leftJoin("lob", function($query){
                $query->on("mssql_patients.LOBID","=","lob.LOBID");
            });
        }
        $this->setFilters($bucket, $request['filters']);
        if (!empty($search)) {
            $bucket->where(function ($query) use ($search) {
                $query->whereRaw("CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName) LIKE ?", ["%{$search}%"])
                    ->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.Payer', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_patients.CF_ServicesNeeded', 'LIKE', "%{$search}%")
                    ->orWhereRaw("ROUND((SELECT SUM(ShiftHours)
                        FROM NeedToHire_Schedules
                        WHERE ccc_followup_calls.aoc_nthaccount_id = NeedToHire_Schedules.aoc_nthaccounts_id
                        AND NeedToHire_Schedules.IsActive=1), 2) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(ccc_followup_calls.created_at, '%m/%d/%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(ccc_followup_calls.last_weekly_followup, '%m/%d/%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CASE WHEN ccc_followup_calls.IsPriority = 1 THEN 'Yes' ELSE 'No' END LIKE ?", ["%{$search}%"]);
            });
        }
        if (isset($orderColumn) && isset($columns[$orderColumn])) {
            $bucket->orderBy($columns[$orderColumn], $orderDir);
        }
        $data = $bucket->get();

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, [
                'Date Added','Client Care Coordinator', 'Client', 'Client ID', 'Branch',
                'Payer', 'Service Needed', 'Hours on NTH', 'Outcome','Notes'
            ]);

            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->DateAdded,
                    $row->ClientCareCoordinator,
                    $row->patient_name,
                    $row->PatientAccount,
                    $row->FacilityName,
                    $row->Payer,
                    $row->CF_ServicesNeeded,
                    $row->HoursOnNTH,
                    $row->Outcome,
                    isset($row->notes) ? H::getHelper('ConstantHelper')->formatTextFromHtml($row->notes) : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
