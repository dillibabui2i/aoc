<?php

namespace App\Helpers;

use App\Enums\FeatureFlagsEnum;
use App\Models\JobCategories;
use Illuminate\Support\Facades\DB;
use Microsoft\Graph\Generated\Applications\Item\Synchronization\Jobs\Item\Schema\Directories\Item\DirectoryDefinitionItemRequestBuilder;

class Datatable
{
    public static $fieldTypes = [
        'IPEA_Date' => 'Date',
        'PEPI_Date' => 'Date',
        'WEPI_Date' => 'Date',
        'Training_Shift' => 'Date',
        'Candidate_Start_Date' => 'Date',
        'application_date' => 'Date',
        'hire_date' => 'Date',
        'recruiterPhoneScreenDate' => 'DateTime',
        'PET1Date' => 'DateTime',
        'PET2Date' => 'DateTime',
        'PET3Date' => 'DateTime',
        'IPEAScheduledDate' => 'DateTime',
        'nursingSchoolGradDate' => 'DateTime',
        'addedToPotentialNurseDate' => 'DateTime',
        'offerRejectedDate' => 'DateTime',
        'rejectedByAgencyDate' => 'DateTime',
    ];

    public function getConfiguration($tablename)
    {
            $dataTable['mssql_recruitertracker']['fields'] = [
                'id'=>'id',
                'linked_IPEA'=>'linked IPEA',
                'Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'PatientName'=>'Client',
                'Candidate_Current_Status'=>'Candidate Current Status ',
                'IPEA_Date'=>'IPEA Date',
                'PEPI_Date'=>'PEPI Date',
                'WEPI_Date'=>'WEPI Date',
                'Training_Shift'=>'Training Shift Date',
                'Candidate_Start_Date'=>'Candidate Start Date',
                'Hours_Worked'=>'Hours Worked',
                'Bonus_Status'=>'Bonus Status',
                'Candidate_Notes'=>'Candidate Notes',
                'action'=>'Action',
            ];

        $dataTable['mssql_recruitertracker']['defaultsort'] = '0';
        $dataTable['mssql_recruitertracker']['searchable'] = ['Recruiter', 'Candidate', 'Branch_Location','Candidate_Current_Status','PatientName','Bonus_Status'];
        $dataTable['mssql_recruitertracker']['action'] = route('dashboard.ajaxallactivitydata');

        $dataTable['login_restrictions']['fields'] = [
                'id'=>'id',
                'blacklisted'=>'Blacklisted',
                'whitelisted'=>'Whitelisted',
                'ipaddress'=>'IP Address',
                'username'=>'Username',
                'updated_at'=>'Updated Date Time',
                'login_action'=>'Action',
            ];

        $dataTable['login_restrictions']['defaultsort'] = '4';
        $dataTable['login_restrictions']['searchable'] = ['ipaddress', 'username'];
        $dataTable['login_restrictions']['action'] = route('ajaxLoginRestrictionsData');

        $dataTable['ipea_email_attachments']['fields'] = [
                'id'=>'id',
                'file_path'=>'File Path',
                'skills'=>'Skill',
                'ipea_attachment_action'=>'Action',
            ];

        $dataTable['ipea_email_attachments']['defaultsort'] = '2';
        $dataTable['ipea_email_attachments']['searchable'] = ['skills'];
        $dataTable['ipea_email_attachments']['action'] = route('ajaxipeaattachment');

        $dataTable['branch_locations']['fields'] = [
                'id'=>'id',
                'branch'=>'Branch',
                'address'=>'Address',
                'city'=>'City',
                'state'=>'State',
                'ipea_email_action'=>'Action',
            ];

        $dataTable['branch_locations']['defaultsort'] = '2';
        $dataTable['branch_locations']['searchable'] = ['branch','address','city','state'];
        $dataTable['branch_locations']['action'] = route('ajaxemailtemplate');

        /* Candidate listing page datatable configuration */
        $dataTableCandidateFields = [
            'id'=> 'id',
            'fullName' => 'Full Name',
            'jbtitle' => 'Title',
            'jbcity' => 'City',
            'application_date' => 'Application Date',
            'mobile' => 'Phone',
            'email' => 'Email',
            'appWorkFlowState' => 'Workflow State',
            'jbcategory' => 'Category',
            'caction' => 'Action',
        ];
        $dataTableCandidateFieldWidth = [
            'id'=> '0',
            'fullName' => '15%',
            'jbtitle' => '20%',
            'jbcity' => '10%',
            'application_date' => '10%',
            'mobile' => '10%',
            'email' => '5%',
            'appWorkFlowState' => '10%',
            'jbcategory' => '15%',
            'caction' => '5%',
        ];
        if (LD::checkFeatureFlag('show-mass-text-option') && H::canPermissions('save.massmessage')) {
            $dataTableCandidateFieldsNew = ['select'=>'select'];
            $dataTableCandidateFields =  array_merge( array_slice( $dataTableCandidateFields, 0, 1 ), $dataTableCandidateFieldsNew, array_slice( $dataTableCandidateFields, 1 ) );
            $dataTableCandidateFieldWidthNew = ['select'=>'5%'];
            $dataTableCandidateFieldWidth =  array_merge( array_slice( $dataTableCandidateFieldWidth, 0, 1 ), $dataTableCandidateFieldWidthNew, array_slice( $dataTableCandidateFieldWidth, 1 ) );
        }
        $dataTable['candidates']['fields'] = $dataTableCandidateFields;
        $dataTable['candidates']['fieldswidth'] = $dataTableCandidateFieldWidth;
        $dataTable['candidates']['defaultsort'] = '2';
        $dataTable['candidates']['searchable'] = ['fullName', 'jobs.title', 'jobs.locationCity', 'candidates.application_date', 'IF(TRIM(COALESCE(`mobile`, "")) = "", `HomePhone`, `mobile`)', 'email', 'appWorkFlowState', 'jobs.category'];
        $dataTable['candidates']['action'] = route('candidatesAjaxListing');

        /* Whiteboard listing page datatable configuration */
        $dataTable['whiteboard']['fields'] = [
            'id'=> 'id',
            'user_name' => 'Recruiter',
            'region_name' => 'Region',
            'row_id' => 'Service',
            'territory' => 'Territory',
            'hire_date' => 'Hired',
            'waction' => 'Action',
        ];

        $dataTable['whiteboard']['defaultsort'] = '1';
        $dataTable['whiteboard']['searchable'] = ['user_name', 'row_id', 'territory', 'hire_date', 'region_name'];
        $dataTable['whiteboard']['action'] = route('managewhiteboard.getwhiteboardlistajax');

        /* Whiteboard listing page datatable configuration */
        $dataTable['map_ignore']['fields'] = [
            'id'=> 'id',
            'map_name' => 'Map',
            'unique_identifier' => 'Unique Identifier',
            'mapaction' => 'Action',
        ];

        $dataTable['map_ignore']['defaultsort'] = '1';
        $dataTable['map_ignore']['searchable'] = ['unique_identifier', 'value'];
        $dataTable['map_ignore']['action'] = route('map.ajaxignoredata');

        return $dataTable[$tablename];
    }

    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    public function returnDate($dd, $format = 'Y-m-d')
    {
        if ($this->validateDate($dd, 'Y-m-d H:i:s')) {
            $date = new \DateTime($dd);
            $formattedDate = $date->format('m/d/Y');

            return $formattedDate;
        } else if ($this->validateDate($dd, 'Y-m-d')) {
            $date = new \DateTime($dd);
            $formattedDate = $date->format('m/d/Y');

            return $formattedDate;
        } else {
            return '-';
        }
    }

    public function convertRecordToType($data)
    {
        $records = [];
        foreach ($data as $key => $value) {
            $rec = [];
            foreach ($value as $k => $v) {
                if ($type = $this->checkType($k)) {
                    if ($type == 'Date') {
                        $data[$key]->{$k} = $this->returnDate($v);
                    } else if ($type == 'DateTime') {
                        $data[$key]->{$k} = H::getFormatedDate($v) . ' ' . H::getFormatedTime($v);
                    }
                }
            }
        }

        return $data;
    }

    public function checkType($field)
    {
        if (isset(self::$fieldTypes[$field])) {
            return self::$fieldTypes[$field];
        } else {
            return false;
        }
    }

    public function ajaxDataResponse($request, $tableName, $filter = 0)
    {
        $dataTable = $this->getConfiguration($tableName);

        $params = $request->all();
        $sqlParameters = [];
        $where = ' 1=1   ';
        $select = ' ';
        $orderBy = ' ';
        $limit = ' ';
        $isShowAll = 0;
        if ($filter == 1) {
            if ($tableName == 'mssql_facilities') {
                $where .= " and email_body != '' ";
            }
        }

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchArray = [];
            foreach ($dataTable['searchable'] as $search) {
                $searchArray[] = $search . ' like :'.str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t';
                $sqlParameters[str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t']="%".$request['search']['value']."%";
            }
            if (!empty($searchArray)) {
                $where .= ' and (' . implode(' OR ', $searchArray) . ')';
            }
        }

        $join = '';

        if($tableName=='map_ignore') {
            $join = " left join map_configurations on map_configurations.map_id = map_ignore.map_id ";
            $select = ', map_configurations.map_name as map_name ';
        }
        if($tableName == 'mssql_recruitertracker') {

            $join = " left join mssql_patients on mssql_recruitertracker.aoc_patient_id = mssql_patients.id ";
            $join .= " left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id ";
            $select = ', mssql_patients.PatientName , mssql_facilities.FacilityName as FacilityName,'."(select concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes) from notes left join users on users.id = notes.created_by where entity = 'tracker' and entity_id = mssql_recruitertracker.id order by notes.id desc limit 1) as Candidate_Notes";
            $filter = [];
            $isIncludeComplete = false;

            if(isset($request['filters'])) {

                if(LD::checkFeatureFlag('recruiter-tracker-show-all-page-multiselect-filter')) {
                    $fl = [];
                    foreach($request['filters'] as $key => $value){
                        $fl[$value['name']][] = $value['value'];
                    }

                    foreach($fl as $key => $val) {

                         if($key=='rec_state' && !empty($val)) {
                            $state = $val;

                            if(!empty($state)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':state_' . $cntr++;
                                }, $state));
                                $cntr = 0;
                                foreach ($state as $c) {
                                    $sqlParameters['state_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and mssql_facilities.State in ($inPlaceholders) ";
                        }

                        if($key=='rec_aoc_facility_id' && !empty($val)) {
                            $rec_aoc_facility_id = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_aoc_facility_id)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':aoc_facility_id_' . $cntr++;
                                }, $rec_aoc_facility_id));
                                $cntr = 0;
                                foreach ($rec_aoc_facility_id as $c) {
                                    $sqlParameters['aoc_facility_id_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and mssql_patients.aoc_facility_id in ($inPlaceholders) ";
                        }

                        if($key=='rec_recruiter' && !empty($val)) {
                            $rec_recruiter = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_recruiter)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':recruiter_' . $cntr++;
                                }, $rec_recruiter));
                                $cntr = 0;
                                foreach ($rec_recruiter as $c) {
                                    $sqlParameters['recruiter_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Recruiter in ($inPlaceholders) ";
                        }

                        if($key=='rec_Candidate_Current_Status' && !empty($val)) {
                            $rec_Candidate_Current_Status = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_Candidate_Current_Status)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':candidate_current_status_' . $cntr++;
                                }, $rec_Candidate_Current_Status));
                                $cntr = 0;
                                foreach ($rec_Candidate_Current_Status as $c) {
                                    $sqlParameters['candidate_current_status_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Candidate_Current_Status in ($inPlaceholders) ";
                        }

                        if($key=='rec_Bonus_Status' && !empty($val)) {
                            $rec_Bonus_Status = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_Bonus_Status)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':bonus_status_' . $cntr++;
                                }, $rec_Bonus_Status));
                                $cntr = 0;
                                foreach ($rec_Bonus_Status as $c) {
                                    $sqlParameters['bonus_status_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Bonus_Status in ($inPlaceholders) ";
                        }

                         if($key=='rec_user_id' && !empty($val)) {
                            $rec_user_id = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_user_id)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':rec_user_id_' . $cntr++;
                                }, $rec_user_id));
                                $cntr = 0;
                                foreach ($rec_user_id as $c) {
                                    $sqlParameters['rec_user_id_'.$cntr++] = $c;
                                }
                            }
                            $join .= " left join users on mssql_recruitertracker.user_id = users.id ";

                            $where .= " and users.recruiting_admin in ($inPlaceholders) ";
                        }

                        if($key=='incl_complete') {
                            $isIncludeComplete = true;
                        }
                    }

                } else {
                    foreach($request['filters'] as $key => $value){
                        if($value['name']!='incl_complete') {

                            if($value['name']=='state' && !empty($value['value'])) {
                                $where.=" and mssql_facilities.State = :State ";
                                $sqlParameters["State"] = $value['value'];
                            } else {
                                if(!empty($value['value'])) {
                                    if (LD::checkFeatureFlag('add-RA-filter-recruting-dashboard')) {
                                        $filtername = 'mssql_recruitertracker.'.$value['name'];
                                        if($value['name']=='aoc_facility_id'){
                                            $filtername="mssql_patients.aoc_facility_id";
                                        }
                                        if($value['name']=='user_id'){
                                            $join .= " left join users on mssql_recruitertracker.user_id = users.id ";
                                            $filtername="users.recruiting_admin";
                                        }
                                        $filter[] = $filtername . " = :".$value['name'];
                                        $sqlParameters[$value['name']] = $value['value'];
                                    } else {
                                        $filtername = 'mssql_recruitertracker.'.$value['name'];
                                        if($value['name']=='aoc_facility_id'){
                                            $filtername="mssql_patients.aoc_facility_id";
                                        }
                                        $filter[] = $filtername . " = :".$value['name'];
                                        $sqlParameters[$value['name']] = $value['value'];
                                    }
                                }
                            }
                        } else if($value['name']=='incl_complete') {
                            $isIncludeComplete = true;
                        }
                    }
                }
                if (!empty($filter)) {
                    $where .= ' and (' . implode(' AND ', $filter) . ') ';
                }
            }
            if(!$isIncludeComplete) {
                $where.=" and is_active='1' ";
            }

            $where.=" and mssql_recruitertracker.is_deleted <> 1 ";
        }

        if($tableName == 'candidates') {
            $workflow = [];
            $category = [];
            $workflowList = config('constant')['appWorkflowState'];

            $categoryList = JobCategories::query()->where('is_enabled', true)
                ->pluck('category')
                ->toArray();

            foreach ($workflowList as $key => $value) {
                if (in_array($value, ['Added to Potential Nurse List','Offer Rejected','Rejected by Agency'])) {
                    continue;
                }
                //temp change to be fixed later
                if($value=='Nursing Student Pending Graduation') {
                    $value = 'Nursing Student: Pending Graduation';
                }

                $workflow[] = $value;
            }

            if(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value) && $request['jobviteType'] === 'therapy') {
                $workflowList = config('constant')['appWorkflowStateTherapist'];
                foreach ($workflowList as $key => $value) { //TODO will be used for different cases
                    if($value=='Therapist Student Pending Graduation') {
                        $value = 'Therapist Student: Pending Graduation';
                    }

                    $workflow[] = $value;
                }
            }

            $join = " left join candidate_applications on x.maxId = candidate_applications.id ";
            $join .= " left join candidates on x.candidate_id = candidates.id ";
            $join .= " left join jobs on candidate_applications.job_id = jobs.id ";
            $select = 'candidates.*,candidates.fullName, jobs.title as jbtitle,jobs.locationCity as jbcity,IF(TRIM(COALESCE(`mobile`, \'\')) = \'\', `HomePhone`, `mobile`) as mobile, appWorkFlowState, jobs.category as jbcategory ';
            $where .= " and candidates.is_deleted = 0 ";
            $isCompleted = 0;
            if (isset($request['filters'])) {
                foreach($request['filters'] as $key => $value) {
                    if ($value['name'] == 'Workflow' && !empty($value['value'])) {
                        $where .= " and appWorkFlowState =:appWorkFlowState ";

                        if ($value['value'] === 'Therapist Student Pending Graduation') {
                            $value['value'] = 'Therapist Student: Pending Graduation';
                        }

                        $sqlParameters["appWorkFlowState"]=$value['value'];
                    } else if ($value['name'] == 'Recruiter' && !empty($value['value'])) {
                        $where .= " and candidates.user_id = :user_id ";
                        $sqlParameters["user_id"]=$value['value'];
                    } else if ($value['name'] == 'Completed') {
                        $isCompleted = 1;
                    } else if ($value['name'] == 'showall') {
                        $isShowAll = 1;
                    }else if ($value['name'] == 'City' && !empty($value['value'])) {
                        $where .= " and jobs.locationCity like :locationCity ";
                        $sqlParameters["locationCity"]="%".$value['value']."%";
                    } else if ($value['name'] == 'Category' && !empty($value['value'])) {
                        $category[] = $value['value'];
                       // $sqlParameters["category"] = "('".implode('\',\'',$category)."')";
                    }
                }
            }
            if($isCompleted == 0) {
                $where .= " and appWorkflowState in ('" . implode('\',\'', $workflow) . "') ";
            }

            if(!empty($category)) {
                $inPlaceholders = implode(',', array_map(function ($c) {
                    static $cntr = 0;
                    return ':category_' . $cntr++;
                }, $category));
                $cntr = 0;
                foreach ($category as $c) {
                    $sqlParameters['category_'.$cntr++] = $c;
                }

                $where .= " and jobs.category in ($inPlaceholders) ";
            } elseif(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value) && $request['jobviteType'] === 'nursing') {
                $where .= " and jobs.category like '%nurs%' ";
            } elseif(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value) && $request['jobviteType'] === 'therapy') {
                $where .= " and jobs.category like '%therap%' ";
            }
        }

        if($tableName == 'whiteboard') {
            $join = " left join regions on whiteboard.region = regions.id ";
            $select = ' ,regions.region_name ';
            $where .= " and whiteboard.is_deleted = 0 ";
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $orderBy = 'Order by ' . $params['columns'][intval($request['order'][0]['column'])]['data'] . ' ' . $request['order'][0]['dir'];
        }

        $newtableName = '(select candidate_id, max(id) as maxId from candidate_applications group by candidate_id) as x';

        if($tableName == 'candidates' && $isShowAll == 0) {
            $sql = 'select ' . $select . ' from ' . $newtableName . '  '.$join.' where ' . $where . $orderBy;
        } elseif($isShowAll == 1) {
            $searchable = ['fullName', 'email', 'appWorkFlowState'];
            $where = '';
            $sqlParameters = [];
            if (isset($request->search['value']) && $request->search['value'] != '') {
                $searchArray = [];
                foreach ($searchable as $search) {
                    $searchArray[] = $search . ' like :'.$search.'_text';
                    $sqlParameters[$search.'_text']="%".$request['search']['value']."%";
                }
                $where .= ' (' . implode(' OR ', $searchArray) . ')';
            }
            $select = 'candidates.*,candidates.fullName, "" as jbtitle,"" as jbcity,IF(TRIM(COALESCE(`mobile`, \'\')) = \'\', `HomePhone`, `mobile`) as mobile, appWorkFlowState, "" as jbcategory ';
            $where = ($where !='')?' WHERE '.$where:'';
            $sql = 'select ' . $select . ' from '.$tableName. $where .' '. $orderBy;
        } else {
            $sql = 'select '.$tableName.'.* ' . $select . ' from `' . $tableName . '`  '.$join.' where ' . $where . $orderBy;
        }

        $totalRecords = DB::select($sql,$sqlParameters);

        if (isset($request['start']) && $request['length'] != '-1') {
            $limit = ' Limit ' . $request['start'] . ' , ' . $request['length'];
        }

        if ($tableName == 'candidates' && $isShowAll == 0) {
            $sql = 'select ' . $select . ' from ' . $newtableName . ' '.$join.' where ' . $where . $orderBy . $limit;
        } elseif ($isShowAll == 1) {
            $searchable = ['fullName', 'email', 'appWorkFlowState'];
            $where = '';
            $sqlParameters = [];

            if (isset($request->search['value']) && $request->search['value'] != '') {
                $searchArray = [];
                foreach ($searchable as $search) {
                    $searchArray[] = $search . ' like :'.$search.'_text';
                    $sqlParameters[$search.'_text']="%".$request['search']['value']."%";
                }

                $where .= ' (' . implode(' OR ', $searchArray) . ')';
            }

            $where = ($where !='')?' WHERE '.$where:'';
            $select = 'candidates.*,candidates.fullName, "" as jbtitle,"" as jbcity,IF(TRIM(COALESCE(`mobile`, \'\')) = \'\', `HomePhone`, `mobile`) as mobile, appWorkFlowState, "" as jbcategory ';
            $sql = 'select ' . $select . ' from '.$tableName .$where .' '. $orderBy . $limit;
        }else {
            $sql = 'select '.$tableName.'.* ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . $orderBy . $limit;
        }

        H::ExcelLog($sql,'testtracker.log');

        $records = DB::select($sql,$sqlParameters);
        $records = $this->convertRecordToType($records);

        if (count($totalRecords)) {
            $data = [
                'draw'            => intval($request['draw']),
                'recordsTotal'    => intval(count($totalRecords)),
                'recordsFiltered' => intval(count($totalRecords)),
                'data'            => $records,
            ];

            return $data;
        } else {
            $data = [
                'draw'            => intval($request['draw']),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ];

            return $data;
        }
    }
    public function ExportTrackerRecordWithFilter($request, $tableName, $filter = 0)
    {
        $dataTable = $this->getConfiguration($tableName);
        $params = $request->all();
        $where = '1=1   ';
        $select = ' ';
        $orderBy = ' ';
        $limit = ' ';
        $sqlParameters = [];

        if ($filter == 1) {
            if ($tableName == 'mssql_facilities') {
                $where .= " and email_body != '' ";
            }
        }

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchArray = [];
            foreach ($dataTable['searchable'] as $search) {
                $searchArray[] = $search . ' like :'.str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t';
                $sqlParameters[str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t']="%".$request['search']['value']."%";
            }
            if (!empty($searchArray)) {
                $where .= ' and (' . implode(' OR ', $searchArray) . ')';
            }
        }

        $join = '';
        if($tableName == 'mssql_recruitertracker') {
            $join = " left join mssql_patients on mssql_recruitertracker.aoc_patient_id = mssql_patients.id ";
            $join .= " left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id ";
            $select = ', mssql_patients.PatientName, mssql_patients.is_intake, mssql_patients.CF_GoalSOCDate, mssql_patients.SOCDate,mssql_patients.payer_group, mssql_facilities.FacilityName as FacilityName, mssql_patients.Payer as Payer';

            $filter = [];
            $isIncludeComplete = false;

            if(isset($request['filters'])) {

                if(LD::checkFeatureFlag('recruiter-tracker-show-all-page-multiselect-filter')) {
                    $fl = [];
                    foreach($request['filters'] as $key => $value){
                        $fl[$value['name']][] = $value['value'];
                    }

                    foreach($fl as $key => $val) {

                         if($key=='rec_state' && !empty($val)) {
                            $state = $val;

                            if(!empty($state)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':state_' . $cntr++;
                                }, $state));
                                $cntr = 0;
                                foreach ($state as $c) {
                                    $sqlParameters['state_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and mssql_facilities.State in ($inPlaceholders) ";
                        }

                        if($key=='rec_aoc_facility_id' && !empty($val)) {
                            $rec_aoc_facility_id = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_aoc_facility_id)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':aoc_facility_id_' . $cntr++;
                                }, $rec_aoc_facility_id));
                                $cntr = 0;
                                foreach ($rec_aoc_facility_id as $c) {
                                    $sqlParameters['aoc_facility_id_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and mssql_patients.aoc_facility_id in ($inPlaceholders) ";
                        }

                        if($key=='rec_recruiter' && !empty($val)) {
                            $rec_recruiter = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_recruiter)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':recruiter_' . $cntr++;
                                }, $rec_recruiter));
                                $cntr = 0;
                                foreach ($rec_recruiter as $c) {
                                    $sqlParameters['recruiter_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Recruiter in ($inPlaceholders) ";
                        }

                        if($key=='rec_Candidate_Current_Status' && !empty($val)) {
                            $rec_Candidate_Current_Status = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_Candidate_Current_Status)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':candidate_current_status_' . $cntr++;
                                }, $rec_Candidate_Current_Status));
                                $cntr = 0;
                                foreach ($rec_Candidate_Current_Status as $c) {
                                    $sqlParameters['candidate_current_status_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Candidate_Current_Status in ($inPlaceholders) ";
                        }

                        if($key=='rec_Bonus_Status' && !empty($val)) {
                            $rec_Bonus_Status = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_Bonus_Status)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':bonus_status_' . $cntr++;
                                }, $rec_Bonus_Status));
                                $cntr = 0;
                                foreach ($rec_Bonus_Status as $c) {
                                    $sqlParameters['bonus_status_'.$cntr++] = $c;
                                }
                            }
                            $where .= " and Bonus_Status in ($inPlaceholders) ";
                        }

                         if($key=='rec_user_id' && !empty($val)) {
                            $rec_user_id = $val;
                            $inPlaceholders= '';
                            if(!empty($rec_user_id)) {
                                $inPlaceholders = implode(',', array_map(function ($c) {
                                    static $cntr = 0;
                                    return ':rec_user_id_' . $cntr++;
                                }, $rec_user_id));
                                $cntr = 0;
                                foreach ($rec_user_id as $c) {
                                    $sqlParameters['rec_user_id_'.$cntr++] = $c;
                                }
                            }
                            $join .= " left join users on mssql_recruitertracker.user_id = users.id ";

                            $where .= " and users.recruiting_admin in ($inPlaceholders) ";
                        }

                        if($key=='incl_complete') {
                            $isIncludeComplete = true;
                        }
                    }

                } else {
                    foreach($request['filters'] as $key => $value){
                        if($value['name']!='incl_complete') {

                            if($value['name']=='state' && !empty($value['value'])) {
                                $where.=" and mssql_facilities.State = :State ";
                                $sqlParameters["State"] = $value['value'];
                            } else {
                                if(!empty($value['value'])) {
                                    if (LD::checkFeatureFlag('add-RA-filter-recruting-dashboard')) {
                                        $filtername = 'mssql_recruitertracker.'.$value['name'];
                                        if($value['name']=='aoc_facility_id'){
                                            $filtername="mssql_patients.aoc_facility_id";
                                        }
                                        if($value['name']=='user_id'){
                                            $join .= " left join users on mssql_recruitertracker.user_id = users.id ";
                                            $filtername="users.recruiting_admin";
                                        }
                                        $filter[] = $filtername . " = :".$value['name'];
                                        $sqlParameters[$value['name']] = $value['value'];
                                    } else {
                                        $filtername = 'mssql_recruitertracker.'.$value['name'];
                                        if($value['name']=='aoc_facility_id'){
                                            $filtername="mssql_patients.aoc_facility_id";
                                        }
                                        $filter[] = $filtername . " = :".$value['name'];
                                        $sqlParameters[$value['name']] = $value['value'];
                                    }
                                }
                            }
                        } else if($value['name']=='incl_complete') {
                            $isIncludeComplete = true;
                        }
                    }
                }
                if (!empty($filter)) {
                    $where .= ' and (' . implode(' AND ', $filter) . ') ';
                }
            }
            if(!$isIncludeComplete) {
                $where.=" and is_active='1' ";
            }

            $where.=" and mssql_recruitertracker.is_deleted <> 1 ";

        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $orderBy = 'Order by ' . $params['columns'][intval($request['order'][0]['column'])]['data'] . ' ' . $request['order'][0]['dir'];
        }

        if (isset($request['start']) && $request['length'] != '-1') {
            $limit = ' Limit ' . $request['start'] . ' , ' . $request['length'];
        }

        $sql = 'select '.$tableName.'.* ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . $orderBy . $limit;
        H::ExcelLog($sql,'testtracker.log');
        $records = DB::select($sql,$sqlParameters);

        $records = $this->convertRecordToType($records);
        return $records;
    }

    public function exportCandidatesRecordWithFilter($request, $tableName)
    {
        $workflowOfferRejected = 'Offer Rejected';
        $workflowPotentialNurseList = 'Added to Potential Nurse List';
        $workflowRejectedByAgency = 'Rejected by Agency';
        $dataTable = $this->getConfiguration($tableName);
        $params = $request->all();
        $where = ' 1=1   ';
        $select = ' ';
        $orderBy = ' ';
        $limit = ' ';
        $sqlParameters = [];

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchArray = [];
            foreach ($dataTable['searchable'] as $search) {
                $searchArray[] = $search . ' like :'.str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t';
                $sqlParameters[str_replace( array( '\'','"',',',';','<','>','.','`','(',')','&','^','%','$','@','#','!','=',':',';',' '), '_', $search).'_t']="%".$request['search']['value']."%";
            }
            if (!empty($searchArray)) {
                $where .= ' and (' . implode(' OR ', $searchArray) . ')';
            }
        }
        $join = '';
        if($tableName == 'candidates') {
            $workflow = [];
            $category = [];
            $workflowList = config('constant')['appWorkflowState'];

            $categoryList = JobCategories::query()->where('is_enabled', true)
                ->pluck('category')
                ->toArray();

            foreach ($workflowList as $key => $value) {
                if (in_array($value, [$workflowPotentialNurseList,$workflowOfferRejected,$workflowRejectedByAgency])) {
                    continue;
                }
                //temp change to be fixed later
                if($value=='Nursing Student Pending Graduation') {
                    $value = 'Nursing Student: Pending Graduation';
                }
                $workflow[] = $value;
            }

            if(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value) && $request['jobviteType'] === 'therapy') {
                $workflowList = config('constant')['appWorkflowStateTherapist'];
                foreach ($workflowList as $key => $value) {
                    if($value=='Therapist Student Pending Graduation') {
                        $value = 'Therapist Student: Pending Graduation';
                    }
                    $workflow[] = $value;
                }
            }

            $join = " left join candidate_applications on x.maxId = candidate_applications.id ";
            $join .= " left join candidates on x.candidate_id = candidates.id ";
            $join .= " left join jobs on candidate_applications.job_id = jobs.id ";
            $select = 'candidates.*,candidates.fullName, jobs.title as jbtitle,jobs.locationCity as jbcity,IF(TRIM(COALESCE(`mobile`, \'\')) = \'\', `HomePhone`, `mobile`) as mobile, appWorkFlowState, jobs.category as jbcategory ';
            if (LD::checkFeatureFlag('add-column-candidates-export')) {
                $select .= ', (CASE WHEN candidates.donothireDate IS NOT NULL
                THEN candidates.outcomeNotes
                ELSE \'\'
                END) as donothireDetailReason,
                (CASE WHEN candidates.rejectedByAgencyDate IS NOT NULL
                THEN candidates.outcomeNotes
                ELSE \'\'
                END) as RejectedByAgencyDetailReason';
            }
            $where .= " and candidates.is_deleted = 0 ";
            if (isset($request['filters'])) {
                foreach($request['filters'] as $key => $value) {
                    if ($value['name'] == 'Workflow' && !empty($value['value'])) {
                        $where .= " and appWorkFlowState = :appWorkFlowState ";
                        $sqlParameters["appWorkFlowState"]=$value['value'];
                    } else if ($value['name'] == 'Recruiter' && !empty($value['value'])) {
                        $where .= " and candidates.user_id = :user_id ";
                        $sqlParameters["user_id"]=$value['value'];
                    } else if ($value['name'] == 'Completed') {
                        $workflow[] = $workflowPotentialNurseList;
                        $workflow[] = $workflowOfferRejected;
                        $workflow[] = $workflowRejectedByAgency;
                    } else if ($value['name'] == 'City' && !empty($value['value'])) {
                        $where .= " and jobs.locationCity like :locationCity ";
                        $sqlParameters["locationCity"]="%".$value['value']."%";
                    } else if ($value['name'] == 'Category' && !empty($value['value'])) {
                        $category[] = $value['value'];
                    }
                }
            }
            $where .= " and appWorkflowState in ('" . implode('\',\'', $workflow) . "') ";

            if(!empty($category)) {
                $inPlaceholders = implode(',', array_map(function ($c) {
                    static $cntr = 0;
                    return ':category_' . $cntr++;
                }, $category));
                $cntr = 0;
                foreach ($category as $c) {
                    $sqlParameters['category_'.$cntr++] = $c;
                }
                $where .= " and jobs.category in ($inPlaceholders) ";
            } elseif(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value) && $request['jobviteType'] === 'therapy') {
                $where .= " and jobs.category like '%therap%' ";
            } else {
                $inPlaceholders = implode(',', array_map(function ($c) {
                    static $cc = 0;
                    return ':category_' . $cc++;
                }, $categoryList));
                $cntr = 0;
                foreach ($categoryList as $c) {
                    $sqlParameters['category_' . $cntr++] = $c;
                }
                $where .= " and jobs.category in ($inPlaceholders) ";
            }
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $orderBy = 'Order by ' . $params['columns'][intval($request['order'][0]['column'])]['data'] . ' ' . $request['order'][0]['dir'];
        }
        $newtableName = '(select candidate_id, max(id) as maxId from candidate_applications group by candidate_id) as x';
        if($tableName == 'candidates') {
            $sql = 'select ' . $select . ' from ' . $newtableName . '  '.$join.' where ' . $where . $orderBy;
        } else {
            $sql = 'select '.$tableName.'.* ' . $select . ' from `' . $tableName . '`  '.$join.' where ' . $where . $orderBy;
        }
        $totalRecords = DB::select($sql,$sqlParameters);

        if (isset($request['start']) && $request['length'] != '-1') {
            $limit = ' Limit ' . $request['start'] . ' , ' . $request['length'];
        }
        if($tableName == 'candidates') {
            $sql = 'select ' . $select . ' from ' . $newtableName . ' '.$join.' where ' . $where . $orderBy . $limit;
        } else {
            $sql = 'select '.$tableName.'.* ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . $orderBy . $limit;
        }
        H::ExcelLog($sql,'testtracker.log');
        $records = DB::select($sql,$sqlParameters);

        $records = $this->convertRecordToType($records);
        return $records;
    }
}
