<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SkillsMatchReportController extends Controller
{

    public function skillsMatch()
    {

        $title = 'Clinician/Client Skills Match Report';
        $type = 'skills';
        $source = 'scheduled';
        $compareNurse = '';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;

        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='past'){
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'FacilityName',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'PatientName',
                    'heading' => ['title' => 'Client', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'count', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'ProviderName',
                    'heading' => ['title' => 'Clinician', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'CF_Skills',
                    'heading' => ['title' => 'Client Skills', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'SkillsList',
                    'heading' => ['title' => 'Clinician Skills', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'first_shift',
                    'heading' => ['title' => 'First Shift Worked', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'last_shift',
                    'heading' => ['title' => 'Last Shift Worked', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Duration',
                    'heading' => ['title' => 'Duration', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }else{
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'FacilityName',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'PatientName',
                    'heading' => ['title' => 'Client', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'count', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'ProviderName',
                    'heading' => ['title' => 'Clinician', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'CF_Skills',
                    'heading' => ['title' => 'Client Skills', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'SkillsList',
                    'heading' => ['title' => 'Clinician Skills', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'first_shift',
                    'heading' => ['title' => 'First Shift Worked', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'last_shift',
                    'heading' => ['title' => 'Next Shift', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Duration',
                    'heading' => ['title' => 'Duration', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }

        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';
        /**
            * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
            * leave 'field' empty for ones you do not need.  It will ignore it.
            * do not remove any or change the id values!!!!
            * make sure your query sorts the data in the same order as the grouping are defined!!!
            * it prints the subtotal row when the data in column 'field' changes
            * style = the style to apply to the <tr> tag
            * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
            *
            * Define groups with the lowest level first
            *
            */
        $report['groupby'] = [
                ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];


        //set date ranges
        $currentDate = date('Y-m-d');
        if($source=='past'){
            $startDate = Carbon::now()->subDays(7)->toDateString();
            $endDate = $startDate;
        }else{
            $startDate = Carbon::now()->addDays(1)->toDateString();
            $endDate = Carbon::now()->addMonths(1)->toDateString();
        }
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = [];
        $filters = [
            [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ]
        ];

        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');

        $paramsArray = [];
        $where = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'state') {
                            $tmp = 'f.State';
                        }
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'f.id';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'f.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'f.id';
                            }
                            $where[] = $tmp . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
            }
        }
        if (LD::checkFeatureFlag('skill-match-report-enhancement')) {
            $where[] = " p2.LOBID IN ('1002','1023') ";
            $where[] = " p1.DisciplineName IN ('RN','LVN','LPN') ";
            $where[] = " not(f.State = 'CO' AND scc.service <> 'SN') ";
        }
		$sourceFilters = [
			[
				'label' => 'Scheduled Visits',
				'value' => 'scheduled'
            ],
			[
				'label' => 'Past Visits',
				'value' => 'past'
            ]
		];
        $data = [];
        //now get report data
        if ($source=='past') {
            if (LD::checkFeatureFlag('skill-match-report-enhancement')) {
            $qry = "select f.State, mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID,
                    ClinicianID, PatientAccount, p1.ProviderName, p2.PatientName, f.FacilityName,
                    p1.SkillsList, p2.CF_Skills, max(ScheduleDate) as last_shift,
                    (select ScheduleDate from mssql_schedulemaster sm2 where sm2.PatientID = mssql_schedulemaster.PatientID
                    and sm2.ProviderID = mssql_schedulemaster.ProviderID order by ScheduleDate asc limit 1) as first_shift,
                    DATEDIFF(max(ScheduleDate),(select ScheduleDate from mssql_schedulemaster sm2 where
                    sm2.PatientID = mssql_schedulemaster.PatientID and sm2.ProviderID = mssql_schedulemaster.ProviderID
                    order by ScheduleDate asc limit 1)) as Duration
                    FROM `mssql_schedulemaster` left join mssql_providers p1 on mssql_schedulemaster.ProviderID = p1.ProviderID
                    left join mssql_patients p2 on mssql_schedulemaster.PatientID = p2.PatientID
                    left join mssql_facilities f on p2.aoc_facility_id = f.id
                    left join service_codes_daas sc on mssql_schedulemaster.ServiceCodeID = sc.service_id
                    left join service_code_crosswalk scc on sc.name = scc.service_name
                    WHERE p1.EmploymentStatusID <> 0 and p1.is_deleted = 0 and ScheduleDate > ?
                    and ScheduleStatusID in (1,2,3) and mssql_schedulemaster.PatientID <> 0
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                    group by mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID
                    order by FacilityName";

                    $tmp = DB::select($qry, array_merge([
                        $startDate
                    ], $paramsArray));
            } else {
                $qry = "select f.State, mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID, ClinicianID,
                    PatientAccount, p1.ProviderName, p2.PatientName, f.FacilityName, p1.SkillsList, p2.CF_Skills, max(ScheduleDate) as last_shift,
                    (select ScheduleDate from mssql_schedulemaster sm2 where sm2.PatientID = mssql_schedulemaster.PatientID
                    and sm2.ProviderID = mssql_schedulemaster.ProviderID order by ScheduleDate asc limit 1) as first_shift,
                    DATEDIFF(max(ScheduleDate),(select ScheduleDate from mssql_schedulemaster sm2 where
                    sm2.PatientID = mssql_schedulemaster.PatientID and sm2.ProviderID = mssql_schedulemaster.ProviderID
                    order by ScheduleDate asc limit 1)) as Duration
                    FROM `mssql_schedulemaster` left join mssql_providers p1 on mssql_schedulemaster.ProviderID = p1.ProviderID
                    left join mssql_patients p2 on mssql_schedulemaster.PatientID = p2.PatientID
                    left join mssql_facilities f on p2.aoc_facility_id = f.id
                    WHERE p1.EmploymentStatusID <> 0 and p1.is_deleted = 0 and ScheduleDate > ?
                    and ScheduleStatusID in (1,2,3) and mssql_schedulemaster.PatientID <> 0
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                    group by mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID
                    order by FacilityName";

                    $tmp = DB::select($qry, array_merge([
                        $startDate
                    ], $paramsArray));
            }
        } else {
            if (LD::checkFeatureFlag('skill-match-report-enhancement')) {
                $qry = "select f.State, mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID,
                    ClinicianID, PatientAccount, p1.ProviderName, p2.PatientName, f.FacilityName, p1.SkillsList,
                    p2.CF_Skills, max(ScheduleDate) as last_shift,
                    (select ScheduleDate from mssql_schedulemaster sm2 where sm2.PatientID = mssql_schedulemaster.PatientID
                    and sm2.ProviderID = mssql_schedulemaster.ProviderID order by ScheduleDate asc limit 1) as first_shift,
                    DATEDIFF(max(ScheduleDate),(select ScheduleDate from mssql_schedulemaster sm2 where
                    sm2.PatientID = mssql_schedulemaster.PatientID and sm2.ProviderID = mssql_schedulemaster.ProviderID
                    order by ScheduleDate asc limit 1)) as Duration
                    FROM `mssql_schedulemaster` left join mssql_providers p1 on mssql_schedulemaster.ProviderID = p1.ProviderID
                    left join mssql_patients p2 on mssql_schedulemaster.PatientID = p2.PatientID
                    left join mssql_facilities f on p2.aoc_facility_id = f.id
                    left join service_codes_daas sc on mssql_schedulemaster.ServiceCodeID = sc.service_id
                    left join service_code_crosswalk scc on sc.name = scc.service_name
                    WHERE p1.EmploymentStatusID <> 0 and p1.is_deleted = 0 and ScheduleDate >= ?
                    and ScheduleDate <= ? and ScheduleStatusID = 5 and mssql_schedulemaster.PatientID <> 0
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                    group by mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID
                    order by FacilityName";

                    $tmp = DB::select($qry, array_merge([
                        $startDate,
                        $endDate
                    ], $paramsArray));
            } else {
                $qry = "select f.State, mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID,
                    ClinicianID, PatientAccount, p1.ProviderName, p2.PatientName, f.FacilityName, p1.SkillsList,
                    p2.CF_Skills, max(ScheduleDate) as last_shift,
                    (select ScheduleDate from mssql_schedulemaster sm2 where sm2.PatientID = mssql_schedulemaster.PatientID
                    and sm2.ProviderID = mssql_schedulemaster.ProviderID order by ScheduleDate asc limit 1) as first_shift,
                    DATEDIFF(max(ScheduleDate),(select ScheduleDate from mssql_schedulemaster sm2 where
                    sm2.PatientID = mssql_schedulemaster.PatientID and sm2.ProviderID = mssql_schedulemaster.ProviderID
                    order by ScheduleDate asc limit 1)) as Duration
                    FROM `mssql_schedulemaster` left join mssql_providers p1 on mssql_schedulemaster.ProviderID = p1.ProviderID
                    left join mssql_patients p2 on mssql_schedulemaster.PatientID = p2.PatientID
                    left join mssql_facilities f on p2.aoc_facility_id = f.id
                    WHERE p1.EmploymentStatusID <> 0 and p1.is_deleted = 0 and ScheduleDate >= ?
                    and ScheduleDate <= ? and ScheduleStatusID = 5 and mssql_schedulemaster.PatientID <> 0
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                    group by mssql_schedulemaster.PatientID, mssql_schedulemaster.ProviderID
                    order by FacilityName";

                    $tmp = DB::select($qry, array_merge([
                        $startDate,
                        $endDate
                    ], $paramsArray));
            }
        }
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        $count = 0;
        $providerSkillList = strtolower('|Factor - IV|IVIG - IV|IVIG (IV)|Lab Draw: Venipuncture|Lab Draw: via PICC/CL|' .
            'Lab Draw: via Port-A-Cath|PICC/CL: Dressing Change|PICC/CL: Medication Administration|' .
            'PIV Start|Port-A-Cath Care: Access/De-access|Solumedrol (IV)|TPN & Lipids|Trach|Vent|Trach & Vent|');
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            //first see if the skills are mismatched
            $missing = 0;
            $missingList = [];
            $limitedProviderList = [];
            $limitedClientList = [];
            if(trim($record['CF_Skills'])!=''){
                H::ExcelLog($record['ProviderName'] . ' - ' . $record['PatientName'],'dbSkillsReport.log');
                $clientSkills = explode(',',$record['CF_Skills']);
                $providerSkills = explode(',',$record['SkillsList']);
                foreach($providerSkills as $tmpNurse){
                    if(trim($tmpNurse)!=''){
                        if(strpos($providerSkillList,strtolower(trim($tmpNurse)))!==false){
                            $limitedProviderList[] = $tmpNurse;
                        }
                    }
                }
                $missing = 0;
                H::ExcelLog(print_r($providerSkills,true),'dbSkillsReport.log');

                foreach($clientSkills as $clientSkill){
                    $compareClient = strtolower(trim($clientSkill));
                    H::ExcelLog('Matching: ' . $compareClient,'dbSkillsReport.log');
                    if($compareClient==strtolower('IV')){
                        $limitedClientList[] = $clientSkill;
                        H::ExcelLog('in IV','dbSkillsReport.log');
                        $match = 0;
                        foreach($providerSkills as $nurseSkill){
                            $compareNurse = strtolower(trim($nurseSkill));
                            if($compareNurse==strtolower('Factor - IV') || $compareNurse == strtolower('Lab Draw: Venipuncture')
                                || $compareNurse == strtolower('PIV Start') || $compareNurse == strtolower('Solumedrol (IV)'))
                            {
                                $match = 1;
                                $missingList[] = $clientSkill;
                            }
                        }
                        if($match==1){
                            H::ExcelLog($compareClient . ' Matched to ' . $compareNurse,'dbSkillsReport.log');
                        }else{
                            H::ExcelLog($compareClient . ' NOT Matched','dbSkillsReport.log');
                            $missing = 1;
                            $missingList[] = $clientSkill;
                        }
                    }elseif($compareClient==strtolower('IVIG')){
                        $limitedClientList[] = $clientSkill;
                        H::ExcelLog('in IVIG','dbSkillsReport.log');
                        $match = 0;
                        foreach($providerSkills as $nurseSkill){
                            $compareNurse = strtolower(trim($nurseSkill));
                            if($compareNurse==strtolower('IVIG - IV') || $compareNurse == strtolower('IVIG (IV)'))
                            {
                                $match = 1;
                                $missingList[] = $clientSkill;
                            }
                        }
                        if($match==1){
                            H::ExcelLog($compareClient . ' Matched to ' . $compareNurse,'dbSkillsReport.log');
                        }else{
                            H::ExcelLog($compareClient . ' NOT Matched','dbSkillsReport.log');
                            $missing = 1;
                            $missingList[] = $clientSkill;
                        }
                    }elseif($compareClient==strtolower('Central Line')){
                        $limitedClientList[] = $clientSkill;
                        H::ExcelLog('in Central Line','dbSkillsReport.log');
                        $match = 0;
                        foreach($providerSkills as $nurseSkill){
                            $compareNurse = strtolower(trim($nurseSkill));
                            if($compareNurse==strtolower('Lab Draw: via PICC/CL') || $compareNurse == strtolower('Lab Draw: via Port-A-Cath')
                                || $compareNurse == strtolower('PICC/CL: Dressing Change')
                                || $compareNurse == strtolower('PICC/CL: Medication Administration')
                                || $compareNurse == strtolower('Port-A-Cath Care: Access/De-access'))
                            {
                                $match = 1;
                                break;
                            }
                        }
                        if($match==1){
                            H::ExcelLog($compareClient . ' Matched to ' . $compareNurse,'dbSkillsReport.log');
                        }else{
                            H::ExcelLog($compareClient . ' NOT Matched','dbSkillsReport.log');
                            $missing = 1;
                            $missingList[] = $clientSkill;
                        }
                    } elseif ($compareClient == strtolower('Trach & Vent')) {
                        $limitedClientList[] = $clientSkill;
                        $match = 0;
                        $hasTrach = false;
                        $hasVent  = false;

                        foreach ($providerSkills as $nurseSkill) {
                            $compareNurse = strtolower(trim($nurseSkill));

                            if ($compareNurse == 'trach & vent') {
                                $match = 1;
                                break;
                            }

                            if ($compareNurse == 'trach') {
                                $hasTrach = true;
                            }

                            if ($compareNurse == 'vent') {
                                $hasVent = true;
                            }
                        }

                        if ($hasTrach && $hasVent) {
                            $match = 1;
                        }

                        if ($match == 0) {
                            $missing = 1;
                            $missingList[] = $clientSkill;
                        }
                    } else{
                        H::ExcelLog('in other','dbSkillsReport.log');
                        if(
                            $compareClient == strtolower('TPN & Lipids')
                            || $compareClient == strtolower('Trach')
                            || $compareClient == strtolower('Vent'))
                        {
                            $limitedClientList[] = $clientSkill;
                            H::ExcelLog('in other comparison','dbSkillsReport.log');
                            $match=0;
                            foreach($providerSkills as $nurseSkill){
                                if(strtolower(trim($nurseSkill))==strtolower(trim($clientSkill))){
                                    $match = 1;
                                    $compareNurse = $nurseSkill;
                                    break;
                                }
                            }
                            if($match==1){
                                H::ExcelLog($clientSkill . ' Matched to ' . $compareNurse,'dbSkillsReport.log');
                            }else{
                                H::ExcelLog($compareClient . ' NOT Matched','dbSkillsReport.log');
                                $missing = 1;
                                $missingList[] = $clientSkill;
                            }
                        }
                    }
                }
            }
            if($missing==1){
                $missingData = implode(',',$missingList);
                $clientList = implode(',', $limitedClientList);
                $providerList = implode(',', $limitedProviderList);
                $count++;
                $id = $record['PatientID'] . "|" . $record['ProviderID'];
                $data[$id] = [
                    'State' => $record['State'],
                    'FacilityName' => $record['FacilityName'],
                    'ClinicianID' => $record['ClinicianID'],
                    'PatientAccount' => $record['PatientAccount'],
                    'ProviderName' => $record['ProviderName'],
                    'PatientName' => $record['PatientName'],
                    'CF_Skills' => $clientList,
                    'SkillsList' => $providerList,
                    'MissingList' => $missingData,
                    'last_shift' => $record['last_shift'],
                    'first_shift' => $record['first_shift'],
                    'Duration' => $record['Duration']
                    ];
                $data[$id]['Total'] = 0;
            }
        }
        H::ExcelLog('Count: ' . $count,'dbSkillsReport.log');
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.skillsMatchReport', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters]);
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
}
