<?php

namespace App\Services\Map;

use App\DTO\Map\MapTableParams;
use App\Helpers\H;
use App\Helpers\LD;
use App\Traits\Map\Need2HireTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class NeedToHireService
{
    use Need2HireTrait;

    /**
     * @param MapTableParams $params
     * @return array
     * @throws \Exception
     */
    public function getNeedToHireData(MapTableParams $params): array
    {
        $sqlParameters = [];
        H::$sqlParameters = [];
        $totalHours = 0;
        $tableName = H::getSelectedMapInfo()[0]->map_tablename;
        $lastmapsearch = Session::get($tableName);

        if (!empty($lastmapsearch)) {
            if (count($lastmapsearch['lastmapsearch'])) {
                $search = $lastmapsearch['lastmapsearch'];
            } else {
                $search = [];
            }
        } else {
            $search = [];
        }
        $where = '1=1 ';
        $select = ' ';
        $having = ' 1 = 1 ';
        $orderBy = ' ';
        $join = '';
        $currentSort = null;
        $forPrefixTables = ['maps_nthalerts'];
        $forEscapeSpecificColumns = [
            'maps_coldcall',
            'maps_potential_nurse',
            'maps_potential_nurse_new'
        ];
        $skipFields = config('Map.need2hire.table.skipFields');
        $strictOrder = config('Map.need2hire.table.strictOrder');

        $prefix = in_array($tableName, $forPrefixTables) ? $tableName . '.' : '';

        if (count($search)) {
            foreach ($search as $k => $val) {
                if (!in_array($k, $skipFields, true)) {
                    $fieldSearch = [];
                    $queryStr = '';
                    $fieldExtract = H::decryptFieldName($k);
                    $k = $fieldExtract[0];
                    $imp = $fieldExtract[1];
                    $dummyColumn = H::addDummyColumn($k, $val, $imp);
                    $select .= $dummyColumn;
                    $specialString = H::isSpecialFilter($k, $val, $imp);
                    $sqlParameters = array_merge($sqlParameters, H::$sqlParameters);
                    H::$sqlParameters = [];

                    if (empty(trim($specialString))) {
                        $cnt = 0;
                        foreach ($val as $kv => $fieldVal) {
                            $fieldSearch[] = ' `' . $tableName . '`.`' . $k . '` =  :' . $k . '_cusotm' . $cnt;
                            $sqlParameters[$k . '_cusotm' . $cnt] = $fieldVal;
                            $cnt++;
                        }
                        if (is_array($fieldSearch) && !empty($fieldSearch)) {
                            $queryStr = implode(' OR ', $fieldSearch);
                        }
                        if (!empty($queryStr)) {
                            $where .= ' AND (' . $queryStr . ') ';
                        }
                    } else {
                        if (!empty($dummyColumn)) {
                            $having .= $specialString;
                        } else {
                            $where .= $specialString;
                        }
                    }
                }
            }

            if (isset($search['radius']) && $search['radius'] != '') {
                if ($search['lat'] != '' && $search['lng'] != '') {
                    if (LD::checkFeatureFlag('round-map-distance-column')) {
                        // Round the distance to 2 decimal places
                        $select .= sprintf(", ROUND((3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos(radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat))))) AS distance", $search['lat'], $search['lng'], $search['lat']);

                        $where .= 'AND lat <> "" AND lng <> "" ';
                        $having .= sprintf(' and distance <= ' . ('%f+0.5'), $search['radius']);
                        $orderBy = ' ORDER BY distance';
                    } else {
                        $select .= sprintf(", (3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos( radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat)))) AS distance", $search['lat'], $search['lng'], $search['lat']);
                        $where .= 'AND lat <> "" AND lng <> "" ';
                        $having .= sprintf(' and distance <= ' . ('%f+0.5'), $search['radius']);
                        $orderBy = ' ORDER BY distance';
                    }

                    if (LD::checkFeatureFlag('update-priority-payer-ranking-logic') && $tableName === 'maps_nth') {
                        $strictOrderList = [];

                        if (is_array($strictOrder)) {
                            foreach ($strictOrder as $config) {
                                if (isset($config['list']) && is_array($config['list'])) {
                                    $strictOrderList = array_merge($strictOrderList, $config['list']);
                                }
                            }
                        }

                        $strictOrderList = array_unique($strictOrderList);

                        if (!empty($strictOrderList)) {
                            $payerListSql = "'" . implode("','", array_map('addslashes', $strictOrderList)) . "'";
                            $payerPrioritySql = "CASE ";

                            foreach ($strictOrderList as $index => $payer) {
                                $payerPrioritySql .= sprintf("WHEN %sPayer = '%s' THEN %d ", $prefix, addslashes($payer), $index);
                            }

                            $payerPrioritySql .= "ELSE " . count($strictOrderList) . " END";

                            $orderBy = " ORDER BY CASE
                                WHEN distance < 10 AND " . $prefix . "Payer IN ($payerListSql) THEN 1
                                WHEN distance >= 10 AND " . $prefix . "Payer IN ($payerListSql) THEN 2
                                ELSE 3 END,
                                $payerPrioritySql,
                                distance";
                        }
                    }
                } else {
                    $res = ['err' => 'Latitude and longitude not defined', 'status' => false];
                }
            } elseif (!isset($search['radius']) && !empty($search['state'])) {
                $where .= ' AND lat <> "" AND lng <> "" AND `' . $tableName . '`.`State` = :State';
                $sqlParameters["State"] = $search['state'];
            } else {
                if (LD::checkFeatureFlag('changes-specific-to-map_nthalerts')) {
                    if (H::getHelper('ConstantHelper')->isNTHAlertMap($tableName)) {
                        $where .= ' ';
                    } else {
                        $where .= ' AND lat <> "" AND lng <> ""';
                    }
                } else {
                    $where .= ' AND lat <> "" AND lng <> ""';
                }
            }

            if (isset(H::getSelectedMapInfo()[0]->map_default_filter)) {
                $defaultFilters = json_decode(H::getSelectedMapInfo()[0]->map_default_filter, true);

                if (count($defaultFilters)) {
                    $where .= ' AND ' . $tableName .'.' .
                        $defaultFilters[0]['field'] . ' ' .
                        $defaultFilters[0]['operator'] . ' :' .
                        $defaultFilters[0]['field'] . '_default';
                    $sqlParameters[$defaultFilters[0]['field'] . '_default'] = $defaultFilters[0]['value'];
                }
            }

            if (LD::checkFeatureFlag('coverage-clinician-map-prn-process')) {
                if (H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {
                    $select .= ", DATE_FORMAT(
                                TRIM(
                                    SUBSTRING_INDEX(
                                        SUBSTRING_INDEX(
                                            maps_covclin.Days_Available,
                                            ',',
                                            n.num
                                        ),
                                        ',',
                                        -1
                                    )
                                ),
                                '%m/%d/%Y'
                            ) AS Days_Available";

                    $select .= sprintf(", CASE WHEN Days_Available = '' THEN Shift_Type ELSE IF(
                                    DAYOFWEEK(
                                        TRIM(
                                            SUBSTRING_INDEX(
                                                SUBSTRING_INDEX(
                                                    maps_covclin.Days_Available,
                                                    ',',
                                                    n.num
                                                ),
                                                ',',
                                                -1
                                            )
                                        )
                                    ) = 1 OR DAYOFWEEK(
                                        TRIM(
                                            SUBSTRING_INDEX(
                                                SUBSTRING_INDEX(
                                                    maps_covclin.Days_Available,
                                                    ',',
                                                    n.num
                                                ),
                                                ',',
                                                -1
                                            )
                                        )
                                    ) = 7,
                                    CONCAT(
                                        'Weekend ',
                                        TRIM(
                                            SUBSTRING_INDEX(
                                                SUBSTRING_INDEX(maps_covclin.Shift_Type, 'Weekend', -1),
                                                ',',
                                                1
                                            )
                                        )
                                    ),
                                    CONCAT(
                                        'Weekday ',
                                        TRIM(
                                            SUBSTRING_INDEX(
                                                SUBSTRING_INDEX(maps_covclin.Shift_Type, 'Weekday', -1),
                                                ',',
                                                1
                                            )
                                        )
                                    )
                                )
                            END AS Shift_Type2");

                    $join .= sprintf(" JOIN temp_number n ON
                        CHAR_LENGTH(maps_covclin.Days_Available) - CHAR_LENGTH(
                        REPLACE
                            (
                                maps_covclin.Days_Available,
                                ',',
                                ''
                            )
                    ) >= n.num - 1 ");

                    if (LD::checkFeatureFlag('coverage-clinician-map-remove-based-on-date')) {
                        $having .= " and
                            if(DATE_FORMAT(TRIM(
                                    SUBSTRING_INDEX(
                                        SUBSTRING_INDEX(
                                            maps_covclin.Days_Available,
                                            ',',
                                            n.num
                                        ),
                                        ',',
                                        -1
                                    )
                                ),
                                '%Y-%m-%d'
                            )!='',DATE_FORMAT(
                                TRIM(
                                    SUBSTRING_INDEX(
                                        SUBSTRING_INDEX(
                                            maps_covclin.Days_Available,
                                            ',',
                                            n.num
                                        ),
                                        ',',
                                        -1
                                    )
                                ),
                                '%Y-%m-%d'
                            ) > CURRENT_DATE(),true)";
                    }

                    foreach ($search as $k => $val) {
                        if (!in_array($k, $skipFields, true)) {
                            $fieldSearch = [];
                            $queryStr = '';
                            $fieldExtract = H::decryptFieldName($k);
                            $k = $fieldExtract[0];
                            $imp = $fieldExtract[1];

                            if ($k == 'Shift_Type') {
                                $field = "Shift_Type2";

                                $preparedQuery = '';
                                $query = [];
                                $cntr = 0;
                                foreach ($val as $key => $v) {
                                    $query[] = ' ( ' . $field . ' like :' . $field . '_partial_' . $cntr . ' ) ';
                                    $sqlParameters[$field . '_partial_' . $cntr] = "%" . $v . "%";
                                    $cntr++;
                                }

                                if (!empty($query)) {
                                    $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';
                                }
                                if (!empty(trim($preparedQuery))) {
                                    $having .= $preparedQuery;
                                }
                            }
                        }
                    }
                }
            }

            if (isset($params->search['value']) && $params->search['value'] != '') {
                $fields = H::getTableFieldsForSelectedMap();
                $qry = [];

                foreach ($fields as $field) {
                    $fieldName = $field->Field;
                    $paramName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName)) . '_text';
                    $requestPattern = in_array($tableName, $forEscapeSpecificColumns) ? '(`%s%s` LIKE :%s)' : '(%s%s LIKE :%s)';

                    $qry[] = sprintf($requestPattern, $prefix, $fieldName, $paramName);
                    $sqlParameters[$paramName] = "%" . $params->search['value'] . "%";
                }

                $where .= ' AND (' . implode(' OR ', $qry) . ')';
            }

            if (isset($params->order[0]) && $params->order[0] != '') {
                $col = 0;

                if (!isset($search['radius']) && intval($params->order[0]['column']) == 1) {
                    $col = 2;
                } else {
                    $col = intval($params->order[0]['column']);
                }

                if (isset($params->isCheckBox) && $params->isCheckBox === 1 && intval($params->order[0]['column']) != 1) {
                    $col = intval($params->order[0]['column']) - 1;
                }

                $cl = $params->columns[$col]['name'];

                $stateByCoords = null;
                if (!empty($search['lat']) && !empty($search['lng'])) {
                    $stateByCoords = $this->detectStateByCoords(
                        (float) $search['lat'],
                        (float) $search['lng']
                    );
                }

                if ($stateByCoords && isset($strictOrder[$stateByCoords])) {
                    $priorityOrder = $strictOrder[$stateByCoords]['list'];
                    $caseStatements = [];
                    foreach ($priorityOrder as $index => $payerName) {
                        $caseStatements[] = "WHEN Payer = '{$payerName}' THEN {$index}";
                    }
                    $caseSQL = implode(' ', $caseStatements);
                    $orderB = "
                        ORDER BY
                            distance ASC,
                            CASE
                                WHEN non_vbc = 'Yes' THEN 9999
                                {$caseSQL}
                                ELSE 9999
                            END ASC,
                            Payer ASC
                    ";
                } else {
                    $orderB = sprintf(' ORDER BY ' . $prefix . '%s %s', $cl, $params->order[0]['dir']);
                }

                if (!empty(trim($orderBy))) {
                    if (LD::checkFeatureFlag('update-priority-payer-ranking-logic') && $tableName === 'maps_nth') {
                        $orderB = $orderBy . ' , ' . str_replace("ORDER BY", "", $orderB);
                    } else {
                        $orderB .= ' , ' . str_replace("ORDER BY", "", $orderBy);
                    }
                }

                $orderBy = $orderB;

                if (LD::checkFeatureFlag('coverage-clinician-map-prn-process')) {
                    if (H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {
                        $col = intval($params->order[0]['column']);

                        if ($col == 1) {
                            $orderBy = sprintf(' ORDER BY `%s` %s', "custom_sort_prn", "desc");
                        }
                    }
                }
            }

            $join .= sprintf('LEFT JOIN NeedToHire_Accounts nta ON nta.ClientID = '.$tableName.'.ClientID');
            $where .= ' AND IFNULL(is_ignored,0) != 1';
            $where .= ' AND nta.IsActive = 1';

            $totalRecords = DB::select(
                'select * ' . $select .
                ' from `' . $tableName . '` ' . $join .
                ' where ' . $where .
                ' having ' . $having .
                $orderBy,
                $sqlParameters
            );

            if (isset($params->type) && $params->type === 'download') {
                return $totalRecords;
            }

            if (Schema::hasColumn($tableName, 'NTH_Hours')) {
                foreach ($totalRecords as $rec) {
                    $totalHours += $rec->NTH_Hours;
                }
            }

            $limit = '';
            if (isset($params->start) && $params->length !== '-1') {
                $limit = sprintf(' Limit %d , %d', $params->start, $params->length);
            }

            $map1Data = DB::select(
                'select * ' . $select .
                ' from `' . $tableName .
                '` ' . $join .
                ' where ' . $where .
                ' having ' . $having . $orderBy . $limit,
                $sqlParameters
            );

            if (empty($lastmapsearch)) {
                return [];
            }

            $total_records = count($totalRecords);

            if (LD::checkFeatureFlag('coverage-clinician-map-prn-process')) {
                if (H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {
                    foreach ($map1Data as $value) {
                        $value->Shift_Type = $value->Shift_Type2;
                    }
                }
            }

            $modified = [];
            if (!isset($search['radius'])) {
                foreach ($map1Data as $value) {
                    $value->distance = '-';
                    $modified[] = $value;
                }
                $modified = H::convertToBooleanValue($modified);

                $this->checkIsInPriorityOrderingList($modified, $strictOrder);
                usort($modified, static function ($a, $b) {
                    return (int) $b->isPriorityzed <=> (int) $a->isPriorityzed;
                });

                $data = [
                    'draw' => intval($params->draw),
                    'totalhours' => $totalHours,
                    'recordsTotal' => intval($total_records),
                    'recordsFiltered' => intval($total_records),
                    'data' => $modified,
                ];
                if (H::getSelectedMapInfo()[0]->map_type === 'PHI') {
                    $loggedInUser = H::getLoggedInUser();
                    $phiData['user']['id'] = $loggedInUser['id'];
                    $phiData['user']['name'] = $loggedInUser['name'];
                    $phiData['user']['ip'] = request()->ip();
                    $phiData['map_name'] = H::getSelectedMapInfo()[0]->map_name;

                    foreach ($modified as $key => $value) {
                        $phiData['results'][] = $value->id;
                    }
                }
                return $data;
            }
            $map1Data = H::convertToBooleanValue($map1Data);
            $this->checkIsInPriorityOrderingList($map1Data, $strictOrder);

            $data = [
                'draw' => intval($params->draw),
                'totalhours' => $totalHours,
                'recordsTotal' => intval($total_records),
                'recordsFiltered' => intval($total_records),
                'data' => $map1Data,
            ];

            if (H::getSelectedMapInfo()[0]->map_type === 'PHI') {
                $loggedInUser = H::getLoggedInUser();
                $phiData['user']['id'] = $loggedInUser['id'];
                $phiData['user']['name'] = $loggedInUser['name'];
                $phiData['user']['ip'] = request()->ip();
                $phiData['map_name'] = H::getSelectedMapInfo()[0]->map_name;

                foreach ($map1Data as $key => $value) {
                    $phiData['results'][] = $value->id;
                }
            }

            return $data;
        }

        $data = [
            'draw' => intval($params->draw),
            'totalhours' => $totalHours,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];

        return $data;
    }

    /**
     * @param float $lat
     * @param float $lng
     * @return string|null
     */
    public function detectStateByCoords(float $lat, float $lng): ?string
    {
        $states = config('Map.need2hire.table.strictOrder');

        foreach ($states as $state => $data) {
            $b = $data['bounds'];
            if (
                $lat >= $b['lat_min'] && $lat <= $b['lat_max'] &&
                $lng >= $b['lng_min'] && $lng <= $b['lng_max']
            ) {
                return $state;
            }
        }

        return null;
    }
}
