<?php

namespace App\Helpers;

use App\RecruitingSchedule as RecruitingSchedule;
use App\User;
use App\MssqlProviders;
use App\Renewables as RenewalbesTable;
use Carbon\Carbon;
use DB;
use App\Helpers\LD;

class Renewables
{
    /**
     * @param $viewType
     * @param $bucketType
     * @param $searchfilter
     * @param $showCount
     * @param $request
     * @return array|mixed|string
     * @throws \Exception
     */
    public function getRenewablesData(
        $viewType = 'dashboard',
        $bucketType = '',
        $searchfilter = [],
        $showCount = 0,
        $request = ''
    ) {
        $loggedInUser = H::getLoggedInUser();
        $showAllData = [];
        $search = '';
        $limit = '';
        $start = '';
        $order = '';
        $dir = '';

        $ocQuery = "(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id
        left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator'
        and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc";

        if ($bucketType == 'cccfifteenzeroexpiring') {
            $ocQuery = "(SELECT u.name from users u
            left join provider_patient pp on pp.aoc_provider_id = mssql_providers.id
            left join mssql_patients p on pp.aoc_patient_id = p.id
            where u.id = p.ccc_id AND pp.last_schedule_date >= '" . date('Y-m-d') . "' limit 1) as ccc";
        }

		$bucket = RenewalbesTable::select(
            'mssql_providers.fullName',
            'mssql_providers.ClinicianID',
            'mssql_providers.DisciplineName',
            'mssql_employmentstatus.EmploymentStatus',
            'branch_locations.branch',
            'regions.region_name',
            DB::raw($ocQuery),
            DB::raw("DATEDIFF(expiration_date, now()) as days_till_expiration"),
            DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%Y') as expiration_date_format"),
            'renewables_types.name',
            'renewables.*',
            DB::raw('DATE_FORMAT(renewables.updated_at,"'.config('constant')['mysqlsearchdatetimeformat'].'") as lastUpdated'),
            DB::raw("(CASE
            WHEN renewables.expiration_date >= CURDATE()
                AND renewables.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                AND renewables.isMissing = 0
                THEN 'Expiring'
            WHEN renewables.isMissing = 1
                AND (renewables.last_follow_up IS NULL OR renewables.last_follow_up < '".date('Y-m-d')."')
                THEN 'Missing'
            WHEN renewables.expiration_date < CURDATE() AND renewables.isMissing = 0
            THEN 'Expired'
            WHEN renewables.outcome = 'Uploaded to KanTime' AND NOT (
                 (renewables.isMissing = 0 AND renewables.expiration_date < CURDATE()) OR
                 (renewables.isMissing = 0 AND renewables.expiration_date >= CURDATE()
                  AND renewables.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 120 DAY))
             )
            THEN 'Completed'
            END) AS ItemStatus"),
            DB::raw('DATE_FORMAT(GREATEST(mssql_providers.HiredDate, mssql_providers.RehireDate), "'.config('constant')['mysqlsearchdateformat'].'") AS recent_date'),
            DB::raw("CASE
            WHEN mssql_providers.IsOfficeStaff = 1 AND mssql_providers.IsClinician = 1 THEN 'Both'
            WHEN mssql_providers.IsOfficeStaff = 1 THEN 'Office'
            ELSE 'Field'
        END AS Type"),
            'mssql_providers.Phone',
            'mssql_providers.Mobile',
            'mssql_providers.External_Email'
        )
        ->leftJoin('mssql_providers', 'renewables.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('renewables_types', 'renewables.renewables_type', '=', 'renewables_types.renewable_type_id')
        ->leftJoin("mssql_facilities", "mssql_facilities.FacilityID", "=", "mssql_providers.FacilityID")
        ->leftJoin("branch_locations", "branch_locations.id", "=", "mssql_facilities.branchid")
        ->leftJoin("regions", "regions.id", "=", "mssql_facilities.region")
        ->leftJoin("mssql_employmentstatus", "mssql_employmentstatus.id", "=", "mssql_providers.EmploymentStatusID")
        ->whereNotNull('renewables.aoc_provider_id')
            ->whereIn('mssql_providers.EmploymentStatusID', config('constant')['renewableClinicianStatusIds'])
        ->where('mssql_providers.fullName', 'not like', '%testing%')
        ->where('mssql_providers.fullName', 'not like', '%test%');

		$bucket->where('renewables.is_deleted', '<>', '1');
		$bucket->where('mssql_providers.DisciplineName', '<>', 'MSW');

        $bucket->where('mssql_providers.is_deleted', '<>', '1');
        $bucket->where(function ($query) {
            $query->where('mssql_providers.FirstName', '!=', 'test')
                  ->where('mssql_providers.LastName', '!=', 'test');
        });
        if (LD::checkFeatureFlag('add-notes-to-oc-renewables')) {
            $bucket->addSelect(DB::raw("(SELECT notes FROM notes WHERE entity='renewables' AND entity_id=renewables.id ORDER BY created_at DESC LIMIT 1) as latest_note"));
        }
        $bucket->leftJoin('renewable_rules', function ($join) {
            if (LD::checkFeatureFlag('fix-renewable-for-non-clinician-office-staff')) {
                $join->on('renewables_types.id', '=', 'renewable_rules.renewable_type_id')
                    ->whereRaw('FIND_IN_SET(mssql_facilities.State, renewable_rules.states)')
                    ->whereRaw('(FIND_IN_SET(mssql_providers.DisciplineName, renewable_rules.discipline) OR renewable_rules.discipline="ALL STAFF")')
                    ->whereRaw('( mssql_providers.IsClinician >= (case when renewable_rules.isClinician = 1 then 1 else 0 end) OR mssql_providers.IsOfficeStaff >= (case when renewable_rules.isOfficeStaff = 1 then 1 else 0 end) )');
            } else {
                $join->on('renewables_types.id', '=', 'renewable_rules.renewable_type_id')
                    ->whereRaw('FIND_IN_SET(mssql_facilities.State, renewable_rules.states)')
                    ->whereRaw('(FIND_IN_SET(mssql_providers.DisciplineName, renewable_rules.discipline) OR renewable_rules.discipline="ALL STAFF")')
                    ->whereRaw('mssql_providers.IsClinician >= (case when renewable_rules.isClinician = 1 then 1 else 0 end)')
                    ->whereRaw('( mssql_providers.IsClinician >= (case when renewable_rules.isClinician = 1 then 1 else 0 end) OR mssql_providers.IsOfficeStaff >= (case when renewable_rules.isOfficeStaff = 1 then 1 else 0 end) )');
                //->whereRaw('mssql_providers.IsOfficeStaff >= (case when renewable_rules.isOfficeStaff = 1 then 1 else 0 end)');
            }
        });

        if ($bucketType=='cccfifteenzeroexpiring') {
            $bucket->addSelect(DB::raw("(select CONCAT(concat(Patient_FirstName,' ',Patient_LastName),'#',
		PatientAccount) from provider_patient pp1 left join mssql_patients p on pp1.aoc_patient_id = p.id where pp1.aoc_provider_id = mssql_providers.id and pp1.last_schedule_date >= '".date('Y-m-d')."' limit 1) as PatientData"));
        }

        switch ($viewType) {
            case 'dashboard':
                switch ($bucketType) {
                    case 'cccfifteenzeroexpiring':
                        $bucket->whereDate('expiration_date', '<=', date('Y-m-d', strtotime("+15 days")));
                        $bucket->where(function ($q) {
                            $q->whereNull("last_follow_up")->orWhereDate("last_follow_up", '<', date('Y-m-d'));
                        });
                        $bucket->havingRaw("PatientData is not null");

                        $bucket->where('isMissing', 0);
                        $bucket->where('renewable_rules.Expiring', 1);

                        $bucket->having(function ($q) {
                            $q->having('ItemStatus', '=', 'Expired')
                                ->orHaving('ItemStatus', '=', 'Expiring');
                        });

                        break;
                    case 'thirtyzeroexpiring':
                        $bucket->whereDate('expiration_date', '<=', date('Y-m-d', strtotime("+30 days")));
                        $bucket->where(function ($q) {
                            $q->whereNull("last_follow_up")->orWhereDate("last_follow_up", '<', date('Y-m-d'));
                        });

                        $bucket->where('isMissing', 0);
                        $bucket->where('renewable_rules.Expiring', 1);

                        $bucket->having(function ($q) {
                            $q->having('ItemStatus', '=', 'Expired')
                                ->orHaving('ItemStatus', '=', 'Expiring');
                        });

                        break;
                    case 'ninetythirtyexpiring':
                        $bucket->whereDate('expiration_date', '>=', date('Y-m-d', strtotime("+31 days")))->whereDate('expiration_date', '<=', date('Y-m-d', strtotime("+120 days")));
                        $bucket->where(function ($q) {
                            $q->whereNull("last_follow_up")->orWhereDate("last_follow_up", '<', date('Y-m-d'));
                        });
                        $bucket->where('isMissing', 0);
                        $bucket->where('renewable_rules.Expiring', 1);

                        $bucket->having(function ($q) {
                            $q->having('ItemStatus', '=', 'Expired')
                                ->orHaving('ItemStatus', '=', 'Expiring');
                        });

                        break;
                    case 'missing':
                        $bucket->where('isMissing', 1);
                        $bucket->where(function ($q) {
                            $q->whereNull("last_follow_up")->orWhereDate("last_follow_up", '<', date('Y-m-d'));
                        });
                        $bucket->where('renewable_rules.Missing', 1);
                        $bucket->where(function ($q) {
                            $q->where('renewables.outcome', '<>', 'Uploaded to KanTime')
                                ->orWhereNull('renewables.outcome');
                        });
                        break;
                    default:
                        break;
                }
                    break;
            case 'showall':
                    $filters = H::getFilterInputArr($request->all());
                    $columns = H::getHelper('Renewables')->getDataTableHtml('');
                    $search = $request->input('search.value');
                    $limit = $request->input('length');
                    $start = $request->input('start');
                    $order = $columns[$request->input('order.0.column')]['name'];
                    $order = $order == 'expiration_date_format' ? 'expiration_date' : $order;
                    $dir = $request->input('order.0.dir');
                    $showAllData['totalData'] = $bucket->count();
                    $showAllData['totalFiltered'] = $showAllData['totalData'];
                        $itemStatus=[];
                        $expiring = 0;
                        $missing= 0;
                        $bucket->addSelect('renewable_rules.Expiring as filterexpiring','renewable_rules.Missing as filtermissing');

                        $renewableType = [];
                        foreach($request->all()['filters'] as $item) {
                            if($item['name']=='renewable_type') {
                                $renewableType[] = $item['value'];
                            }
                        }
                        if(!in_array("all",$renewableType)) {
                            foreach($renewableType as $renewableStatus){
                                if($renewableStatus=='expiring') {
                                    $expiring = 1;
                                    $itemStatus[] = 'Expiring';

                                    $bucket->orHaving(function($q) {
                                        $q->having('filterexpiring',1);
                                        $q->having('ItemStatus', '=', 'Expiring');
                                    });
                                }
                                if($renewableStatus=='missing') {
                                    $missing = 1;
                                    $itemStatus[] = 'Missing';

                                    $bucket->orHaving(function($q) {
                                        $q->having('filtermissing',1);
                                        $q->having('ItemStatus', '=', 'Missing');
                                    });
                                }
                                if($renewableStatus=='expired') {
                                    $bucket->orHaving(function($q) {
                                        $q->having('filterexpiring',1);
                                        $q->having('ItemStatus', '=', 'Expired');
                                    });
                                }
                                if($renewableStatus=='completed') {
                                    $itemStatus[] = 'Completed';
                                    $bucket->orHaving(function($q) {
                                        $q->having('ItemStatus', '=', 'Completed');
                                    });
                            }
                            }
                        }
                    if (!empty($search)) {
                        $bucket->where(function ($query) use ($search){
                            $query->where('mssql_providers.fullName', 'LIKE', "%{$search}%");
                            $query->orWhere('renewables_types.name', 'LIKE', "%{$search}%");
                        });
                    }

                    break;
            case 'export':
                $filters = H::getFilterInputArr($request->all());
                $search = $request->input('search.value');
                    $itemStatus=[];
                    $expiring = 0;
                    $missing= 0;
                    $bucket->addSelect('renewable_rules.Expiring as filterexpiring','renewable_rules.Missing as filtermissing');

                    $renewableType = [];
                    foreach($request->all()['filters'] as $item) {
                        if($item['name']=='renewable_type') {
                            $renewableType[] = $item['value'];
                        }
                    }
                    if(!in_array("all",$renewableType)) {
                        foreach($renewableType as $renewableStatus){
                            if($renewableStatus=='expiring') {
                                $expiring = 1;
                                $itemStatus[] = 'Expiring';

                                $bucket->orHaving(function($q) {
                                    $q->having('filterexpiring',1);
                                    $q->having('ItemStatus', '=', 'Expiring');
                                });
                            }
                            if($renewableStatus=='missing') {
                                $missing = 1;
                                $itemStatus[] = 'Missing';

                                $bucket->orHaving(function($q) {
                                    $q->having('filtermissing',1);
                                    $q->having('ItemStatus', '=', 'Missing');
                                });
                            }
                            if($renewableStatus=='expired') {
                                $bucket->orHaving(function($q) {
                                    $q->having('filterexpiring',1);
                                    $q->having('ItemStatus', '=', 'Expired');
                                });
                            }
                            if($renewableStatus=='completed') {
                                $itemStatus[] = 'Completed';
                                $bucket->orHaving(function($q) {
                                    $q->having('ItemStatus', '=', 'Completed');
                                });
                        }
                        }
                    }
                if (!empty($search)) {
                    $bucket->where(function ($query) use ($search){
                        $query->where('mssql_providers.fullName', 'LIKE', "%{$search}%");
                        $query->orWhere('renewables_types.name', 'LIKE', "%{$search}%");
                    });
                }
                break;
            default:
                    break;
        }

        // Filter Section
        $isMeMode = 0;
        $state = $region = $branch = $oc = $clinicianStatus = $renewableItems=$discipline=$cccId=[];
        $fromExpirationDate = '';
        $toExpirationDate = '';
        $ccc = '';
        $textSearch = '';
        $type = '';

        if (!empty($searchfilter)) {
            foreach ($searchfilter as $filter) {
                $filterName = $filter['name'];
                $filterValue = $filter['value'];

                if ($filterName == 'ocdefault' || $filterName == 'cccexpiring_default') {
                    $isMeMode = 1;
                }
                if ($filterName == 'ocstate' || $filterName == 'cccexpiring_state') {
                    $state[] = $filterValue;
                }
                if ($filterName == 'ocregion' || $filterName == 'cccexpiring_region') {
                    $region[] = $filterValue;
                }
                if ($filterName == 'ipea_location_id' || $filterName == 'cccexpiring_ipea_location_id') {
                    $branch[] = $filterValue;
                }
                if ($filterName == 'ccc') {
                    $cccId[] = $filterValue;
                }
                if ($filterName == 'oc') {
                    $oc[] = $filterValue;
                }
                if ($filterName == 'renewable_clinicianstatus') {
                    $clinicianStatus[] = $filterValue;
                }
                if ($filterName == 'renewable_items') {
                    $renewableItems[] = $filterValue;
                }
                if ($filterName == 'renewable_textsearch') {
                    $textSearch = $filterValue;
                }
                if ($filterName == 'fromrenewable_expiration_date' || $filterName == 'cccexpiring_fromrenewable_expiration_date') {
                    $fromExpirationDate = $filterValue;
                }
                if ($filterName == 'torenewable_expiration_date' || $filterName == 'cccexpiring_torenewable_expiration_date') {
                    $toExpirationDate = $filterValue;
                }
                if ($filterName == 'type') {
                    $type = $filterValue;
                }
                if ($filterName == 'renewable_discipline') { // +3
                    $discipline[] = $filterValue;
                }
            }
        }

        if (!empty($textSearch)) {
            $bucket->where(function ($q) use ($textSearch) {
                $q->orWhere("renewables_types.name", "like", '%' . $textSearch . '%');
                $q->orWhere("mssql_providers.DisciplineName", "like", '%' . $textSearch . '%');
                $q->orWhere("mssql_providers.fullName", "like", '%' . $textSearch . '%');
                $q->orWhere("mssql_providers.ClinicianID", "like", '%' . $textSearch . '%');
                $q->orWhere("mssql_employmentstatus.EmploymentStatus", "like", '%' . $textSearch . '%');
                $q->orWhere("branch_locations.branch", "like", '%' . $textSearch . '%');
                $q->orWhere("regions.region_name", "like", '%' . $textSearch . '%');
                $q->orWhere("renewables.outcome", "like", '%' . $textSearch . '%');
                $q->orWhereRaw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid ) limit 1)  LIKE ?", ['%' . $textSearch . '%']);
            });
        }

        if ($isMeMode) {
            if ($bucketType == 'cccfifteenzeroexpiring') {
                $cccId[] = $loggedInUser['id'];
            } else {
                $oc[] = $loggedInUser['id'];
            }
        }

        if (count($clinicianStatus) > 0) {
            $bucket->where(function ($q) use ($clinicianStatus) {
                foreach ($clinicianStatus as $c) {
                    $q->orWhere("mssql_employmentstatus.EmploymentStatus", "=", $c);
                }
            });
        }

        if (count($renewableItems) > 0) {
            $bucket->where(function ($q) use ($renewableItems) {
                foreach ($renewableItems as $r) {
                    $q->orWhere("renewables_types.id", "=", $r);
                }
            });
        }

        if(count($oc)) {
            $questionMarkString = H::generateQuestionMarksArray($oc);
            $bucket->whereRaw("(SELECT users.id from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid ) limit 1) IN (" . $questionMarkString . ")", $oc);
        }

        if (count($cccId)) {
            $bucket->whereRaw("(SELECT u.id from users u
            left join provider_patient pp2 on pp2.aoc_provider_id = mssql_providers.id
            left join mssql_patients p on pp2.aoc_patient_id = p.id
            where u.id = p.ccc_id AND pp2.last_schedule_date >= '" . date('Y-m-d') . "' limit 1) IN
            ('" . implode("','", $cccId) . "')");
        }

        if (!empty($ccc)) {
            $bucket->where("mssql_providers.ccc_id", $ccc);
        }

        if ($type == 'both') {
            $bucket->where([
                ["mssql_providers.IsOfficeStaff", '=', 1],
                ["mssql_providers.IsClinician", '=', 1]
            ]);
        } elseif ($type == 'office') {
            $bucket->where("mssql_providers.IsOfficeStaff", '=', 1);
        } elseif ($type == 'field') {
            $bucket->where("mssql_providers.IsClinician", '=', 1);
        }

        if (count($state) > 0) {
            $bucket->where(function ($q) use ($state) {
                foreach ($state as $s) {
                    $q->orWhere("branch_locations.state", "=", $s);
                }
            });
        }

        if (count($branch) > 0) {
            $bucket->where(function ($q) use ($branch) {
                foreach ($branch as $b) {
                    $q->orWhere("mssql_facilities.branchid", "=", $b);
                }
            });
        }

        if (count($region) > 0) {
            $bucket->where(function ($q) use ($region) {
                foreach ($region as $b) {
                    $q->orWhere("regions.id", "=", $b);
                }
            });
        }

        if (!empty($fromExpirationDate)) {
            $convertedStartDate = date('Y-m-d', strtotime($fromExpirationDate));
            $bucket->whereDate("renewables.expiration_date", ">=", $convertedStartDate);
        }

        if (!empty($toExpirationDate)) {
            $convertedEndDate = date('Y-m-d', strtotime($toExpirationDate));
            $bucket->whereDate("renewables.expiration_date", "<=", $convertedEndDate);
        }

        if (!empty($discipline)) { // +1
            $bucket->where(function ($q) use ($discipline) {
                foreach ($discipline as $b) { // +3
                    $q->orWhere("mssql_providers.DisciplineName", "=", $b);
                }
            });
        }

        if ($showCount) {
            if ($bucketType == 'thirtyzeroexpiring') {
                $expQuery = $bucket;
                $bucket = $bucket->count();
                $expcount = $expQuery->having('ItemStatus', '=', 'Expired')->count();
                return '(' . $bucket . ' | ' . $expcount . ' Expired)';
            } elseif ($bucketType == 'cccfifteenzeroexpiring') {
                $expQuery = $bucket;
                $bucket = $bucket->count();
                $expcount = $expQuery->having('ItemStatus', '=', 'Expired')->count();
                return '(' . $bucket . ' | ' . $expcount . ' Expired)';
            }
            return '(' . $bucket->count() . ')';
        }

        if ($viewType == 'showall') {
            $showAllData['totalFiltered'] = $bucket->count();

            if ($order == 'recent_date') {
                $order = DB::raw('DATE(GREATEST(mssql_providers.HiredDate, mssql_providers.RehireDate))');
            }
            $showAllData['data'] = $bucket->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return $showAllData;
        } elseif ($viewType == 'export') {
            return json_decode(json_encode($bucket->get()->toArray()), true);
        } else {
            if ($bucketType == 'thirtyzeroexpiring') {
                return $bucket;
            } elseif ($bucketType == 'cccfifteenzeroexpiring') {
                return $bucket;
            }

            return $bucket->get();
        }
    }

    public function getDataTableHtml($type)
    {
        $clinicianNameConst = 'Clinician Name';
        $clinicianIDConst = 'Clinician ID';
        $clinicianStatusConst= ' Clinician Status';
        if($type == 'cccfifteenzeroexpiring') {
            $oCConst = 'Client Care Coordinator';
			$ocField = 'ccc';
        } else {
            $oCConst = 'Orientation Coordinator ';
			$ocField = 'oc';
        }
        $itemStatusConst = 'Item Status';
        $daysTillExpirationConst = 'Days till expiration';
        $expirationDateConst = 'Expiration date';
        $hireDate = 'Hire Date';
        $dataTable = [];
        switch($type) {

            case 'missing':
                $dataTable = [
                    'fullName'=>$clinicianNameConst,
                    'ClinicianID'=>$clinicianIDConst,
                    'DisciplineName'=>"Discipline",
                    'recent_date'=>$hireDate,
                    'EmploymentStatus'=>$clinicianStatusConst,
                    'branch'=>'Branch',
                    'region_name'=>'Region',
                    $ocField=>$oCConst,
                    'name'=>'Item',
                    'outcome'=>$itemStatusConst,
                ];
                break;
            case 'thirtyzeroexpiring':
            case 'ninetythirtyexpiring':
                $dataTable = [
                    'fullName'=>$clinicianNameConst,
                    'ClinicianID'=>$clinicianIDConst,
                    'DisciplineName'=>"Discipline",
                    'recent_date'=>$hireDate,
                    'EmploymentStatus'=>$clinicianStatusConst,
                    'branch'=>'Branch',
                    'region_name'=>'Region',
                    $ocField=>$oCConst,
                    'name'=>'Item',
                    'days_till_expiration'=>$daysTillExpirationConst,
                    'expiration_date_format'=>$expirationDateConst,
                    'outcome'=>$itemStatusConst,
                ];
                break;
            case 'cccfifteenzeroexpiring':
                $dataTable = [
                    'fullName'=>$clinicianNameConst,
                    'ClinicianID'=>$clinicianIDConst,
                    'recent_date'=>$hireDate,
                    'EmploymentStatus'=>$clinicianStatusConst,
                    'ClientName'=>' Client Name',
                    'PatientAccount'=>' ClientId',
                    'branch'=>'Branch',
                    'region_name'=>'Region',
                    $ocField=>$oCConst,
                    'name'=>'Item',
                    'days_till_expiration'=>$daysTillExpirationConst,
                    'expiration_date_format'=>$expirationDateConst,
                    'outcome'=>$itemStatusConst,
                ];
                break;
            case 'export':
                    return [
                        'fullName'=>$clinicianNameConst,
                        'ClinicianID'=>$clinicianIDConst,
                        'DisciplineName'=>"Discipline",
                        'EmploymentStatus'=>$clinicianStatusConst,
                        'branch'=>'Branch',
                        'region_name'=>'Region',
                        $ocField=>$oCConst,
                        'name'=>'Item',
                        'outcome'=>'Outcome',
                        'lastUpdated'=>'Date of Last Update',
                        'days_till_expiration'=>$daysTillExpirationConst,
                        'expiration_date_format'=>$expirationDateConst,
                        'ItemStatus'=>$itemStatusConst,
                        'Phone'=>'Phone',
                        'Mobile'=>'Mobile',
                        'External_Email'=>'Email',
                    ];
            default:
                return [
                    ['name'=>'Type','label'=>'Type'],
                    ['name'=>'fullName','label'=>$clinicianNameConst],
                    ['name'=>'DisciplineName','label'=>'Discipline'],
                    ['name'=>'recent_date','label'=>'Hire Date'],
                    ['name'=>'EmploymentStatus','label'=>$clinicianStatusConst],
                    ['name'=>'branch','label'=>'Branch'],
                    ['name'=>'region_name','label'=>'Region'],
                    ['name'=>$ocField,'label'=>$oCConst],
                    ['name'=>'name','label'=>'Item'],
                    ['name'=>'days_till_expiration','label'=>$daysTillExpirationConst],
                    ['name'=>'expiration_date_format','label'=>$expirationDateConst],
                    ['name'=>'outcome','label'=>'Outcome'],
                    ['name'=>'ItemStatus','label'=>$itemStatusConst],
                    ['name'=>'lastUpdated','label'=>'Recent Activity'],
                ];
        }
        if (LD::checkFeatureFlag('add-notes-to-oc-renewables') && in_array($type, ['missing', 'thirtyzeroexpiring', 'ninetythirtyexpiring'])) {
            $dataTable['latest_note'] = 'Notes';
        }
        if(($type == 'missing' || $type == 'thirtyzeroexpiring' || $type == 'ninetythirtyexpiring'
        || $type == 'cccfifteenzeroexpiring')){
            $addnewfiled['Type'] = "Type";
            $dataTable = $addnewfiled + $dataTable;
        }
       return view('orientation.expiringdatatable',["type"=>$type,'dataTable'=>$dataTable]);
    }


    public function getTitleFromType($type){
        $expiringRenewables = 'Expiring Renewables';
        $data  = [
            'thirtyzeroexpiring'=>$expiringRenewables,
            'ninetythirtyexpiring'=>$expiringRenewables,
            'cccfifteenzeroexpiring'=>$expiringRenewables,
            'missing'=>'Missing Renewables'
        ];

        return $data[$type];
    }

    public function getExpiringOutcome(){
        $expiringOutcome = config('constant.renewable_expiring_outcome');

        $status = [];
        foreach($expiringOutcome as $key => $value) {
            $status[] = $value;
        }
        return $status;
    }

    public function getMissingOutcome(){
        $missingOutcome = config('constant.renewable_missing_outcome');

        $status = [];
        foreach($missingOutcome as $key => $value) {
            $status[] = $value;
        }
        return $status;
    }

    public function setRenewableDates($eval){

        $providerId = $eval->entity_id;
        $issueDate = date("Y-m-d",strtotime($eval->send_date));
        $expiryDate=date('Y-m-d', strtotime('+1 year', strtotime($issueDate)) );

        $data["expiration_date"] = $expiryDate;
        $data["issued_date"] = $issueDate;
        $data['updated_at']=date('Y-m-d H:i:s');


        $renewablesRecords = DB::table("renewables")->where("aoc_provider_id",$providerId)
        ->where("renewables_type",17)->first();

        if($renewablesRecords) {

            $renewables = DB::table("renewables")->where("aoc_provider_id",$providerId)
            ->where("renewables_type",17)->update($data);

            $noteData = [];
            $noteData['entity'] = 'clinician';
            $noteData['entity_id'] = $providerId;
            $noteData['notes'] = "Annual Performance Evaluation renewable was updated with Issue Date of ".date("m/d/Y",strtotime($issueDate))." and Expiring on ".date("m/d/Y",strtotime($expiryDate))." due to completed evaluation document.";


            H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);

        }
        return true;
    }

}
