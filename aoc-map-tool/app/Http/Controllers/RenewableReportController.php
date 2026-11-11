<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RenewableReportController extends Controller
{
    public function getrenewablespopup(Request $request){
        $req = $request->all();
        H::ExcelLog(print_r($req,true),'dbRenewablePop.log');
        $rowFilter = json_decode($req['rowfilter'],true);
        $form = $req['form'];
        foreach($form as $key => $value) {
            if (
                empty($value['value']) ||
                substr_count($value['name'], "date") ||
                substr_count($value['name'], "excludeprn")
            ) {
                continue;
            }
            if (substr_count($value['name'], "search") > 0) {
                if (substr_count($value['name'], "[") == 1) {
                    $key = str_replace(["]","search["], ["",""], $value['name']);
                    $rowFilter[$key] = $value['value'];
                } elseif (substr_count($value['name'], "[") == 2) {
                    $key = str_replace(["]","search","["], ["","",""], $value['name']);
                    if (isset($rowFilter[$key]) && !is_array($rowFilter[$key])) {
                        $temp = $rowFilter[$key];
                        unset($rowFilter[$key]);
                        $rowFilter[$key][] = $temp;
                        $rowFilter[$key][] = $value['value'];
                    } else {
                        $rowFilter[$key][] = $value['value'];
                    }
                }
            } else {
                if (in_array($value['name'], array('_token', 'isajax', 'period', 'source', 'lob', 'view'))) {
                    continue;
                }
                $rowFilter[$key] = $value['value'];
            }
        }
        $result = [];
        $query = null;
        if(isset($req['type'])){
            if($req['type']=='renewablesReport.missingRenewable') {
                $query = DB::table("renewables")
                        ->select(
                            "renewables_types.name as Item",
                            DB::raw("(CASE
                            WHEN renewables.expiration_date >= CURDATE()
                                AND renewables.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                                AND renewables.isMissing = 0
                                AND (renewables.outcome IS NULL OR renewables.outcome <> 'Uploaded to KanTime')
                                 THEN 'Expiring'
                            WHEN renewables.isMissing = 1
                                AND (renewables.outcome IS NULL OR renewables.outcome <> 'Uploaded to KanTime')
                                THEN 'Missing'
                            WHEN renewables.expiration_date < CURDATE() AND renewables.isMissing = 0 AND
                            (renewables.outcome IS NULL OR renewables.outcome <> 'Uploaded to KanTime') THEN 'Expired'
                            WHEN renewables.outcome = 'Uploaded to KanTime' THEN 'Completed'
                            END) AS ItemStatus"),
                            DB::raw(
                                "CONCAT(
                                    mssql_providers.FirstName,
                                    ' ',
                                    mssql_providers.LastName
                                ) as `Clinician Name`"
                            ),
                            "mssql_providers.ClinicianID as Clinician ID",
                            "mssql_providers.DisciplineName as Discipline",
                            DB::raw("IF(
                                mssql_providers.HiredDate != '' AND mssql_providers.HiredDate IS NOT NULL,
                                DATE_FORMAT(mssql_providers.HiredDate,'%m/%d/%Y'),
                                ''
                            ) as `Hire Date`"),
                            DB::raw("CASE
                                WHEN mssql_providers.IsOfficeStaff = 1 AND mssql_providers.IsClinician = 1 THEN 'Both'
                                WHEN mssql_providers.IsOfficeStaff = 1 THEN 'Office'
                                ELSE 'Field'
                            END AS Type")
                        )
                ->leftJoin("mssql_providers","mssql_providers.id",'=',"renewables.aoc_provider_id")
                ->leftJoin("renewables_types","renewables_types.renewable_type_id",'=',"renewables.renewables_type")
                ->leftJoin("mssql_facilities","mssql_facilities.FacilityID",'=',"mssql_providers.FacilityID")
                ->leftJoin("regions","regions.id",'=',"mssql_facilities.region")
                ->leftJoin("mssql_employmentstatus","mssql_employmentstatus.id",'=',"mssql_providers.EmploymentStatusID")
                ->leftJoin('renewable_rules', function ($join) {
                    $join->on('renewables_types.id', '=', 'renewable_rules.renewable_type_id')
                         ->whereRaw('FIND_IN_SET(mssql_facilities.State, renewable_rules.states)')
                         ->whereRaw('(FIND_IN_SET(mssql_providers.DisciplineName, renewable_rules.discipline) OR
                         renewable_rules.discipline="ALL STAFF")')
                         ->whereRaw('mssql_providers.IsClinician >=
                         (case when renewable_rules.isClinician = 1 then 1 else 0 end)')
                         ->whereRaw('( mssql_providers.IsClinician >=
                         (case when renewable_rules.isClinician = 1 then 1 else 0 end) OR
                         mssql_providers.IsOfficeStaff >=
                         (case when renewable_rules.isOfficeStaff = 1 then 1 else 0 end) )');
                })
                ->whereNotNull('renewables.aoc_provider_id')
                ->whereIn('mssql_providers.EmploymentStatusID', config('constant')['renewableClinicianStatusIds'])
                ->where('mssql_providers.fullName', 'not like', '%testing%')
                ->where(function($q){
                    $q->whereNull("last_follow_up")->orWhereDate("last_follow_up",'<',date('Y-m-d'));
                })
                ->where('isMissing',1)
                ->where('renewable_rules.Missing',1);
                if(count($rowFilter)) {
                    $fieldmap = [
                        'FacilityName' => 'mssql_facilities.FacilityName',
                        'State' => 'mssql_facilities.State',
                        'state' => 'mssql_facilities.State',
                        'aoc_facility_id' => 'mssql_facilities.id',
                        'reregion' => 'regions.id',
                        'renewable_clinicianstatus' => 'mssql_employmentstatus.EmploymentStatus',
                        'renewable_discipline' => 'mssql_providers.DisciplineName',
                        'renewable_items' => 'renewables_types.id',
                        'renewableItems' => 'renewables_types.name'
                    ];
                    $query->where(function($q) use($rowFilter, $fieldmap){
                        foreach($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if (is_array($rf)) {
                                $q->whereIn($rfkey, $rf);
                            } else {
                                $q->where($rfkey, "=", $rf);
                            }
                        }
                    });
                }
            }
            H::ExcelLog(print_r($query->toSql(),true),'dbRenewablePop.log');
            if($query) {
                $result = $query->get()->toArray();
            }

        }

        return view('reports.popup',['data'=>$request->all(),"results"=>count($result)>0?$result:[]]);
    }

    public function missingRenewable()
    {
        $report = [];
        $data = [];
        $report['title'] = 'Missing Renewable Report';
        $report['id'] = 'renewablesReport.missingRenewable';
        //columns defines the fieldname, heading, optional format, width and style for each column
            $clickedFilters = [
                'State',
                'FacilityName',
                'renewableItems'
            ];
        $report['columns'] = [
            [
                'field' => 'State',
                'heading' => ['title' => 'State', 'attributes' => ' width="10%"', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'FacilityName',
                'heading' => ['title' => 'Branch', 'attributes' => ' width="20%"', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'renewableItems',
                'heading' => ['title' => 'Renewable Items', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'renewablesCompleted',
                'heading' => ['title' => 'Renewables Completed', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'avg', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'renewablesRequired',
                'heading' => ['title' => 'Renewables Required', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'renewablesEntered',
                'heading' => ['title' => 'Renewables Entered', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'renewablesNeeded',
                'heading' => ['title' => 'Renewables Needed', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => [
                    'jsfunction' => 'openRenewableReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                    ]
                ],
            ],
        ];
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

            ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
            ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
            ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
            ['id' => 4, 'label' => 'Sub totals', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
        ];

        //now define filters
        $state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        $branches  = [];
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label'=>'Region',
                    'name'=>'reregion',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
                    'disabled' => true
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label'=>'Clinician status',
                    'name'=>'renewable_clinicianstatus',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
                ],
                [
                    'label' => 'Discipline',
                    'name' => 'renewable_discipline',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getDiscipline()
                ],
                [
                    'label'=>'Renewable Item',
                    'name'=>'renewable_items',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getRenewableItems()
                ]
            ];
        
        //define datasource
        $where = [];
        $paramsArray = [];
        H::ExcelLog(print_r(request()->all(),true),'renewableReport.log');
		if (request()->input('isajax')) {
			$search = request()->input('search');
			if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'renewableReport.log');
                        $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            if($field == 'reregion'){
                                $tmp = 'regions.id';
                            }
                            if($field == 'renewable_clinicianstatus'){
                                $tmp = 'mssql_employmentstatus.EmploymentStatus';
                            }
                            if($field == 'renewable_discipline'){
                                $tmp = 'mssql_providers.DisciplineName';
                            }
                            if($field == 'renewable_items'){
                                $tmp = 'renewables_types.id';
                            }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'renewableReport.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            $where[] = $tmp . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
			}
		}
        $newLeftJoin = " ";
            $newLeftJoin = "LEFT JOIN regions ON regions.id = mssql_facilities.region
            LEFT JOIN mssql_employmentstatus ON mssql_employmentstatus.id =
            mssql_providers.EmploymentStatusID";
        $qry = "SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State, renewables_types.id as id, ifnull(mssql_facilities.FacilityName,'No EMR Match') as FacilityName,renewables_types.name as renewableItems,count(mssql_providers.id) as 'renewablesRequired'
                    FROM `renewables`
                        left join mssql_providers on mssql_providers.id = renewables.aoc_provider_id
                        left join renewables_types on renewables_types.renewable_type_id = renewables.renewables_type
                        LEFT JOIN `renewable_rules` ON `renewables_types`.`id` = `renewable_rules`.`renewable_type_id`
                        left join mssql_facilities on mssql_facilities.FacilityID = mssql_providers.FacilityID
                        ".$newLeftJoin."
                        where FIND_IN_SET(
                            mssql_facilities.State,
                            renewable_rules.states
                        ) AND (FIND_IN_SET(
                            mssql_providers.DisciplineName,
                            renewable_rules.discipline
                        ) OR renewable_rules.discipline = 'ALL STAFF') AND mssql_providers.IsClinician >=(
                            CASE WHEN renewable_rules.isClinician = 1 THEN 1 ELSE 0 END
                        ) AND ( mssql_providers.IsClinician >= (
                            CASE WHEN renewable_rules.isClinician = 1 THEN 1 ELSE 0 END
                        ) OR mssql_providers.IsOfficeStaff >= (
                            CASE WHEN renewable_rules.isOfficeStaff = 1 THEN 1 ELSE 0 END
                        )) AND renewables.aoc_provider_id is not null  AND mssql_providers.is_deleted = 0 AND `mssql_providers`.`EmploymentStatusID` in (".implode(',',config('constant')['renewableClinicianStatusIds']).") AND mssql_facilities.hide != 1  ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                        group by State,FacilityName,renewableItems order by State ASC";
        $qryForEnteredRenewable = "SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State, renewables_types.id as id, ifnull(mssql_facilities.FacilityName,'No EMR Match') as FacilityName,renewables_types.name as renewableItems,count(mssql_providers.id) as 'renewablesEntered'
            FROM `renewables`
                left join mssql_providers on mssql_providers.id = renewables.aoc_provider_id
                left join renewables_types on renewables_types.renewable_type_id = renewables.renewables_type
                LEFT JOIN `renewable_rules` ON `renewables_types`.`id` = `renewable_rules`.`renewable_type_id`
                left join mssql_facilities on mssql_facilities.FacilityID = mssql_providers.FacilityID
                ".$newLeftJoin."
                where FIND_IN_SET(
                    mssql_facilities.State,
                    renewable_rules.states
                ) AND (FIND_IN_SET(
                    mssql_providers.DisciplineName,
                    renewable_rules.discipline
                ) OR renewable_rules.discipline = 'ALL STAFF') AND mssql_providers.IsClinician >=(
                    CASE WHEN renewable_rules.isClinician = 1 THEN 1 ELSE 0 END
                ) AND ( mssql_providers.IsClinician >= (
                    CASE WHEN renewable_rules.isClinician = 1 THEN 1 ELSE 0 END
                ) OR mssql_providers.IsOfficeStaff >= (
                    CASE WHEN renewable_rules.isOfficeStaff = 1 THEN 1 ELSE 0 END
                )) AND renewables.aoc_provider_id is not null AND mssql_providers.is_deleted = 0 AND `mssql_providers`.`EmploymentStatusID` in (".implode(',',config('constant')['renewableClinicianStatusIds']).") AND mssql_facilities.hide != 1 AND renewables.isMissing != 1 ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                group by State,FacilityName,renewableItems order by State ASC";

        H::ExcelLog($qry,'renewableReport.log');
        H::ExcelLog($qryForEnteredRenewable,'renewableReport.log');
        $tmp = DB::select($qry, $paramsArray);
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];

        $enteredRenewableData = DB::select($qryForEnteredRenewable, $paramsArray);
        $enteredRenewableData = json_decode(json_encode($enteredRenewableData), true);
        $enteredRenewableProcessedData = [];
        foreach ($enteredRenewableData as $r) {
            $id = $r['State'] . "|" .  $r['FacilityName'] . "|" . $r['renewableItems'];
            foreach($r as $key => $val){
                if($key=='renewablesEntered') {
                    $enteredRenewableProcessedData[$id][$key] = $val;
                }
            }
        }

        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['State'] . "|" .  $record['FacilityName'] . "|" . $record['renewableItems'];
            foreach($record as $key => $val){
                if($key!='id') {
                    $data[$id][$key] = $val;
                }
                $data[$id]['renewablesEntered'] = isset($enteredRenewableProcessedData[$id]['renewablesEntered']) ? $enteredRenewableProcessedData[$id]['renewablesEntered'] : 0;

                $data[$id]['renewablesNeeded'] = isset($data[$id]['renewablesRequired']) ? $data[$id]['renewablesRequired'] - $data[$id]['renewablesEntered'] : 0;

                $data[$id]['renewablesCompleted'] = isset($data[$id]['renewablesRequired']) ? ($data[$id]['renewablesEntered'] * 100) / $data[$id]['renewablesRequired'] : 0;

                $data[$id]['renewablesCompleted'] = round($data[$id]['renewablesCompleted'], 0);
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.renewableReports', ['report' => $report, 'data' => $data, 'filters' => $filters]);
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
