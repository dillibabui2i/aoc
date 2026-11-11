<?php

namespace App\Helpers;

use App\BranchLocations as BranchLocations;
use App\Enums\NTHAExcludeCandidateStatus;
use App\Map;
use App\Permissions;
use App\Roles;
use App\RolesPermissions;
use App\UserPermissions;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\User;
use \DOMDocument;
use App\Renewables;
use App\Schedule;
use Illuminate\Support\Facades\Route;
use App\MssqlRecruitertracker;

/**
 * @deprecated
 */
class H
{
    public static $sqlParameters = [];

    public static function getRequiredFields(){
       return Session::get("required");
    }

    public static function getCurl($url = 'https://api.jobvite.com/api/v2/job')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public static function getHelper($className)
    {
        $clsname = 'App\\Helpers\\' . $className;

        return new $clsname;
    }

    public static function encryptFieldName($field, $imp)
    {
        return $field . '##' . $imp;
    }

    public static function decryptFieldName($field)
    {
        $f = explode('##', $field);

        return $f;
    }

    public static function getDateTimeFromTimestamp($timestamp)
    {
        $date = date('Y-m-d H:i:s', ($timestamp / 1000));

        return $date;
    }

    public static function prepareGlobalSearchQuery($val)
    {
        H::ExcelLog(auth()->user()->name . ' in H.prepareGlobalSearchQuery','mapQuery.log');
        $fields = self::getTableFieldsForSelectedMap();

        $preparedQuery = '';
        $query = [];

        foreach ($fields as $key => $v) {
            $query[] = ' ( `' . $v->Field . '` like "%' . $val . '%" ) ';
        }

        if (!empty($query)) {
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

            return $preparedQuery;
        } else {
            return '';
        }
    }

    public static function getFilterTypes()
    {
        return ['No', 'Skills', 'Full Time', 'Day-Night', 'Shift Duration', 'Specialized', 'Day of Week', 'Partial Match', 'Exclude Matches', 'Calendar',"Yes/No"];
    }

    public static function viewPreferences()
    {
        return ['Map View', 'Chart View'];
    }

    public static function getDistinctValue($field)
    {
        $data = null;

        $data = DB::table(self::getSelectedMapInfo()[0]->map_tablename)->distinct()->orderBy($field)->get($field)->toArray();

        return $data;
    }

    public static function ignoreFilterOptionSorting()
    {
        return ['6'];
    }

    public static function isSpecialFilter($fieldName, $val, $imp)
    {
        $mapFilters = json_decode(self::getSelectedMapInfo()[0]->map_filters, true);
        foreach ($mapFilters as $filter) {
            if ($filter['field'] == $fieldName && $filter['imp'] > 0 && $filter['imp'] == $imp) {
                if (isset($filter['value']) && !empty($filter['value'])) {
                    $options = explode('|', $filter['value']);
                } else {
                    $dbData = self::getDistinctValue($fieldName);
                    $options = [];
                    foreach ($dbData as $data) {
                        $options[] = $data->$fieldName;
                    }
                }

                if ($filter['imp'] == 3) {
                    return self::dayNightFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 1) {
                    return self::skillFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 2) {
                    return self::fullTimeFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 4) {
                    return self::shiftDurationFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 5) {
                    return self::specialzedFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 6) {
                    return self::weekDayFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 7) {
                    return self::partialMatchFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 8) {
                    return self::excludeMatchFilter($fieldName, $val, $options);
                }

                if ($filter['imp'] == 9) {
                    return self::calendarFilter($fieldName, $val, $options);
                }
            }
        }

        return ' ';
    }

    public static function calendarFilter($field, $selectedValue, $options)
    {
        $query = ' ';
        if (!empty($selectedValue)) {
            $dates = explode(',', $selectedValue);
            $tableName = self::getSelectedMapInfo()[0]->map_tablename;

            $inPlaceholders = implode(',', array_map(function ($d) {
                static $cntr = 0;
                return ':calender_' . $cntr++;
            }, $dates));
            $cntr = 0;
            foreach ($dates as $d) {
                self::$sqlParameters['calender_'.$cntr++] = $d;
            }
           // echo $field;
            //$dateString = implode("','", $dates);
            $query = ' AND ' . $tableName . '.' . $field . " IN(" . $inPlaceholders . ")";
        }

        return $query;
    }

    public static function excludeMatchFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];
        $cntr = 0;
        foreach ($selectedValue as $key => $v) {
            $query[] = ' ( ' . $field . ' NOT LIKE :'.$field.'_exclude_'.$cntr.' ) ';
            self::$sqlParameters[$field.'_exclude_'.$cntr] = "%".$v."%";
            $cntr++;
        }

        if (!empty($query)) {
            $preparedQuery = ' AND (' . implode(' AND ', $query) . ') ';

            return $preparedQuery;
        } else {
            return ' ';
        }
    }

    public static function partialMatchFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];
        $cntr = 0;
        foreach ($selectedValue as $key => $v) {
            $query[] = ' ( ' . $field . ' like :'.$field.'_partial_'.$cntr.' ) ';
            self::$sqlParameters[$field.'_partial_'.$cntr] = "%".$v."%";
            $cntr++;
        }

        if (!empty($query)) {
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

            return $preparedQuery;
        } else {
            return ' ';
        }
    }

    public static function addDummyColumn($fieldName, $val, $imp)
    {
        $mapFilters = json_decode(self::getSelectedMapInfo()[0]->map_filters, true);
        $strSelect = '';
        foreach ($mapFilters as $filter) {
            if ($filter['field'] == $fieldName && $imp == 6) {
                $strSelect = ',LOWER(DAYNAME(str_to_date(' . $fieldName . ",'%m/%d/%Y'))) as day_of_week";

            }
        }

        return $strSelect;
    }

    public static function weekDayFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];

        $weekday = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $weekend = ['saturday', 'sunday'];
        $cntr = 0;
        foreach ($selectedValue as $key => $v) {
            if (strtolower($v) == 'weekday' || strtolower($v) == 'weekend') {
                $arra = strtolower($v);
                foreach ($$arra as $dayindex => $day) {
                    $query[] = ' ( day_of_week like :_day_of_week_'.$cntr.' ) ';
                    self::$sqlParameters['_day_of_week_'.$cntr] = "%".$day."%";
                    $cntr++;
                }
            } else {
                $query[] = ' ( day_of_week like :_day_of_week_'.$cntr.' ) ';
                self::$sqlParameters['_day_of_week_'.$cntr] = "%".strtolower($v)."%";
                $cntr++;
            }
        }

        if (!empty($query)) {
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

            return $preparedQuery;
        } else {
            return ' ';
        }
    }

    public static function specialzedFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];
        $cntr = 0;
        foreach ($selectedValue as $key => $v) {
            $spl = explode(' ', $v);

            if (count($spl) > 1 && strtolower($spl[0]) == 'not') {
                $query[] = ' ( ' . $field . ' not like :' . $field.'_specialized_'.$cntr . ' ) ';
                self::$sqlParameters[$field.'_specialized_'.$cntr] = "%".$spl[1]."%";
                $cntr++;
            } else {
                $query[] = ' ( ' . $field . ' like :' . $field.'_specialized_'.$cntr . ' ) ';
                self::$sqlParameters[$field.'_specialized_'.$cntr] = "%".$spl[0]."%";
                $cntr++;
            }
        }

        if (!empty($query)) {
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';
            self::ExcelLog($preparedQuery, 'lynnexc.log');

            return $preparedQuery;
        } else {
            return ' ';
        }
    }

    public static function shiftDurationFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];
        foreach ($selectedValue as $k => $value) {
            if (strtolower($value) == 'less than 8 hours') {
                $query[] = ' ( ' . $field . ' < 8 ) ';
            }
            if (strtolower($value) == '8-12 hours') {
                $query[] = ' (' . $field . ' >=8 and ' . $field . ' <12 )';
            }
            if (strtolower($value) == '12+ hours') {
                $query[] = ' ( ' . $field . ' >= 12 ) ';
            }
            if (strtolower($value) == '0-30') {
                $query[] = ' ( ' . $field . ' < 30 ) ';
            }
            if (strtolower($value) == '30-40') {
                $query[] = ' (' . $field . ' >=30 and ' . $field . ' <40 )';
            }
            if (strtolower($value) == '40+') {
                $query[] = ' ( ' . $field . ' >= 40 ) ';
            }
        }
        $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

        return $preparedQuery;
    }

    public static function dayNightFilter($field, $selectedValue, $options)
    {
        $preparedQuery = '';
        $query = [];
        if(LD::checkFeatureFlag('day-night-shift-filter-map-coverageneeds')) {
            foreach ($selectedValue as $k => $value) {
                if (strtolower($value) == 'day' || strtolower($value)=='day shift') {
                    $query[] = ' ( ' . $field . ' like "%AM -%" ) ';
                }
                if (strtolower($value) == 'night'|| strtolower($value)=='night shift') {
                    $query[] = ' (' . $field . ' like "%PM -%" and Hours >= 8 )';
                }
                if (strtolower($value) == 'evening' || strtolower($value)=='evening shift') {
                    $query[] = ' ( ' . $field . ' like "%PM -%" and Hours <= 5 ) ';
                    $query[] = ' ( ' . $field . ' like "%AM -%" and Hours >= 12 ) ';
                }
            }
        } else {
            foreach ($selectedValue as $k => $value) {
                if (strtolower($value) == 'day') {
                    $query[] = ' ( ' . $field . ' like "%AM -%" ) ';
                }
                if (strtolower($value) == 'night') {
                    $query[] = ' (' . $field . ' like "%PM -%" and Hours >= 8 )';
                }
                if (strtolower($value) == 'evening') {
                    $query[] = ' ( ' . $field . ' like "%PM -%" and Hours <= 5 ) ';
                    $query[] = ' ( ' . $field . ' like "%AM -%" and Hours >= 12 ) ';
                }
            }
        }

        $preparedQuery = (!empty($query)) ? ' AND (' . implode(' OR ', $query) . ') ' : '';

        return $preparedQuery;
    }

    public static function skillFilter($fieldName, $val, $options)
    {
        $where = '';
        $cntr = 0;
        foreach ($options as $opt) {
            if (!in_array($opt, $val)) {
                $where .= ' and ' . $fieldName . " not like :".$fieldName."_skill_".$cntr." ";
                self::$sqlParameters[$fieldName.'_skill_'.$cntr] = "%".$opt."%";
                $cntr++;
            }
        }
        return $where;
    }

    public static function fullTimeFilter($fieldName, $val, $options)
    {
        $query = [];

        foreach ($val as $k => $value) {
            if (strtolower($value) == 'yes') {
                $query[] = ' ' . $fieldName . ' > 36 ';

            }
            if (strtolower($value) == 'no') {
                $query[] = ' ' . $fieldName . ' <= 36 ';

            }

        }
        if (!empty($query)) {
            return $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';
        } else {
            return '';
        }
    }

    public static function generateQueryForStaticFilter($tablename, $fieldName, $selectedValue)
    {
        if ($tablename == 'map1' && $fieldName == 'SumofhoursonNTH') {
            $preparedQuery = '';
            $query = [];
            foreach ($selectedValue as $k => $value) {
                if (strtolower($value) == 'yes') {
                    $query[] = ' ' . $fieldName . ' > 36 ';
                }
                if (strtolower($value) == 'no') {
                    $query[] = ' ' . $fieldName . ' <= 36 ';
                }
            }
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

            return $preparedQuery;
        }

        if ($tablename == 'map2' && $fieldName == 'Schedule_Time') {
            $preparedQuery = '';
            $query = [];
            foreach ($selectedValue as $k => $value) {
                if (strtolower($value) == 'day') {
                    $query[] = ' ' . $fieldName . ' like "%AM -%" ';
                }
                if (strtolower($value) == 'night') {
                    $query[] = ' ' . $fieldName . ' like "%PM -%" and Hours >= 8 ';
                }
                if (strtolower($value) == 'evening') {
                    $query[] = ' ' . $fieldName . ' like "%PM -%" and Hours <= 5  ';
                    $query[] = ' ' . $fieldName . ' like "%AM -%" and Hours >= 12 ';
                }
            }
            $preparedQuery = ' AND (' . implode(' OR ', $query) . ') ';

            return $preparedQuery;
        }
    }

    public static function getDynamicFiltersHTMLForMap()
    {
        $html = '';
        $fClass = [];
        $searchData = [];
        $map_filters_json = json_decode(self::getSelectedMapInfo()[0]->map_filters);
        $searchDataSession = Session::get(self::getSelectedMapInfo()[0]->map_tablename);
        if (isset($searchDataSession['lastmapsearch'])) {
            $searchData = $searchDataSession['lastmapsearch'];
        }
        $calendarCount = 0;
        Session::put('required', []);
        if (!empty($map_filters_json)) {
            $html .= '<ul class="list-unstyled components filter-item-cnt">';

            usort($map_filters_json, function ($item1, $item2) {
                return $item1->sort_order <=> $item2->sort_order;
            });

            foreach ($map_filters_json as $fields) {
                $fieldsField = '';
                $fieldsImp = '';
                if (isset($fields->field)) {
                    $fieldsField = $fields->field;
                }
                if (isset($fields->imp)) {
                    $fieldsImp = $fields->imp;
                }

                $fieldClass = '';
                if(isset($fields->is_required) && $fields->is_required=='true') {
                    $fieldClass = $fieldsField.'-'.$fieldsImp;
                    $fClass[]=$fieldClass;

                }
                if ($fields->type == 'C' && $fields->imp != 9) {

					if($fields->field == 'Schedule_Time' && !LD::checkFeatureFlag('day-night-shift-filter-map-coverageneeds')) {
						continue;
					}

                    $ulId = str_replace(' ', '_', $fields->filter_label . $fieldsField);
                    $html .= '<li style="clear:both;">
                                 <a href="#' . $ulId . '" data-toggle="collapse" class="dropdown-toggle collapsed"></i>' . $fields->filter_label . '</a>
                                                <ul class="list-unstyled collapse parent-'.$fieldClass.'"  id="' . $ulId . '">
                                 <p class="alert alert-danger" style="display:none;">This is required.</p>
                            <p>' . $fields->filter_description . '</p>';

                    // Add Select all checkbox
                    $selectAllId = 'select-all-' . $ulId;
                    $html .= '<li>
                            <input type="checkbox" id="' . $selectAllId . '" />
                            <label for="' . $selectAllId . '"><b>Select all</b></label>
                          </li>';

                    if (empty($fields->value)) {
                        if(LD::checkFeatureFlag('nthmap-priority-filter')) {
                            if($fields->imp == 10) {

                                $distinct = self::getDistinctValue($fieldsField);

                                foreach ($distinct as $value) {
                                    $optionlabel = '';
                                    if (isset($value->{$fieldsField})) {
                                        $selected = '';
                                        if (isset($searchData[self::encryptFieldName($fieldsField, $fieldsImp)])) {
                                            foreach ($searchData[self::encryptFieldName($fieldsField, $fieldsImp)] as $key => $searchValue) {
                                                if ($value->{$fieldsField} == $searchValue) {
                                                    $selected = 'checked';
                                                }
                                            }
                                        }
                                        if($value->{$fieldsField}=='0') {
                                            $optionlabel = "No";
                                        } else if($value->{$fieldsField}=='1') {
                                            $optionlabel = "Yes";
                                        }

                                        $html.='<li>
                                                    <input class="dynamic_filter_elements '.$fieldClass.'" type="checkbox" ' . $selected . ' name="' . self::encryptFieldName($fieldsField, $fieldsImp) . '[]" id="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '" value="' . $value->{$fieldsField} . '"/>
                                                    <label class="dynamic_filter_elements_label" for="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '">' . $optionlabel . '</label>
                                                </li>';
                                    }
                                }

                            } else {
                                $distinct = self::getDistinctValue($fieldsField);
                                sort($distinct);
                                foreach ($distinct as $value) {
                                    if (!empty($value->{$fieldsField})) {
                                        $selected = '';
                                        if (isset($searchData[self::encryptFieldName($fieldsField, $fieldsImp)])) {
                                            foreach ($searchData[self::encryptFieldName($fieldsField, $fieldsImp)] as $key => $searchValue) {
                                                if ($value->{$fieldsField} == $searchValue) {
                                                    $selected = 'checked';
                                                }
                                            }
                                        }
                                        $html.='<li>
                                                    <input class="dynamic_filter_elements '.$fieldClass.'" type="checkbox" ' . $selected . ' name="' . self::encryptFieldName($fieldsField, $fieldsImp) . '[]" id="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '" value="' . $value->{$fieldsField} . '"/>
                                                    <label class="dynamic_filter_elements_label" for="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '">' . $value->{$fieldsField} . '</label>
                                                </li>';
                                    }
                                }
                            }
                        } else {
                            $distinct = self::getDistinctValue($fieldsField);
                            sort($distinct);
                            foreach ($distinct as $value) {
                                if (!empty($value->{$fieldsField})) {
                                    $selected = '';
                                    if (isset($searchData[self::encryptFieldName($fieldsField, $fieldsImp)])) {
                                        foreach ($searchData[self::encryptFieldName($fieldsField, $fieldsImp)] as $key => $searchValue) {
                                            if ($value->{$fieldsField} == $searchValue) {
                                                $selected = 'checked';
                                            }
                                        }
                                    }
                                    $html.='<li>
                                                <input class="dynamic_filter_elements '.$fieldClass.'" type="checkbox" ' . $selected . ' name="' . self::encryptFieldName($fieldsField, $fieldsImp) . '[]" id="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '" value="' . $value->{$fieldsField} . '"/>
                                                <label class="dynamic_filter_elements_label" for="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value->{$fieldsField} . '">' . $value->{$fieldsField} . '</label>
                                            </li>';
                                }
                            }
                        }
                    } else {
                        $distinct = explode('|', $fields->value);
                        if (!in_array($fieldsImp, self::ignoreFilterOptionSorting())) {
                            sort($distinct);
                        }
                        foreach ($distinct as $value) {
                            $selected = '';
                            if (isset($searchData[self::encryptFieldName($fieldsField, $fieldsImp)])) {
                                foreach ($searchData[self::encryptFieldName($fieldsField, $fieldsImp)] as $key => $searchValue) {
                                    if ($value == $searchValue) {
                                        $selected = 'checked';
                                    }
                                }
                            }

                            $html.='<li>
                                        <input class="dynamic_filter_elements '.$fieldClass.'" type="checkbox" ' . $selected . ' name="' . self::encryptFieldName($fieldsField, $fieldsImp) . '[]" id="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value . '" value="' . $value . '"/>
                                        <label class="dynamic_filter_elements_label" for="element-' . self::encryptFieldName($fieldsField, $fieldsImp) . '-' . $value . '">' . $value . '</label>
                                    </li>';
                        }
                    }

                    $html .= '<script>
                            $(function() {
                                var $ul = $("#' . $ulId . '");
                                var $selectAll = $("#' . $selectAllId . '");

                                function updateSelectAll() {
                                    var allChecked = $ul.find("input.dynamic_filter_elements[type=checkbox]").length ===
                                     $ul.find("input.dynamic_filter_elements[type=checkbox]:checked").length;
                                    $selectAll.prop("checked", allChecked);
                                }

                                updateSelectAll();

                                $selectAll.change(function() {
                                    $ul.find("input.dynamic_filter_elements[type=checkbox]").prop("checked", this.checked);
                                    updateSelectAll();
                                });

                                $ul.find("input.dynamic_filter_elements[type=checkbox]").change(function() {
                                    updateSelectAll();
                                });
                            });
                          </script>';

                    $html .= '</ul>
							</li>';
                } else {
                    $calendarCount++;
                    $calValue = '';
                    if (isset($searchData[self::encryptFieldName($fieldsField, $fieldsImp)])) {
                        $calValue = $searchData[self::encryptFieldName($fieldsField, $fieldsImp)];
                    }
                    $html .= '<li style="clear:both;">
					<a href="#' . str_replace(' ', '_', $fields->filter_label . $fieldsField) . '" data-toggle="collapse" class="dropdown-toggle collapsed"></i>' . $fields->filter_label . '</a>

					<ul class="list-unstyled collapse datepicker-filter parent-'.$fieldClass.'" id="' . str_replace(' ', '_', $fields->filter_label . $fieldsField) . '">
                    <p class="alert alert-danger" style="display:none;">This is required.</p><p>' . $fields->filter_description . '</p>';
                    $html .= '<li class="datepicker-filter2" >
                    <input type="hidden" id="element-' . $fieldsField . '__' . $fieldsImp . '"  name="' . self::encryptFieldName($fieldsField, $fieldsImp) . '" value="' . $calValue . '"/>

                    <div></div>
                    <script>
                    var datecntr = 1;
                    var obj_' . $calendarCount . ' = $("#' . str_replace(' ', '_', $fields->filter_label . $fieldsField) . ' div").datepicker({
                        todayBtn: false,
                        format:"yyyy-mm-dd",
                        clearBtn: true,
                        multidate: true,
                        todayHighlight: true,

                    }).on("changeDate", function(e) {

                            var dateObjects = $(this).datepicker("getDates");
                            var dd = [];

                            for(var i=0; i < dateObjects.length; i++) {
                             // dd[i] = dateObjects[i].getFullYear()+"-"+getFullNumber(dateObjects[i].getMonth()+1)+"-"+getFullNumber(dateObjects[i].getDate());
                             //dd[i] = +"-"++"-"+;
                             dd[i] =getFullNumber(dateObjects[i].getMonth()+1)+"/"+getFullNumber(dateObjects[i].getDate())+"/"+dateObjects[i].getFullYear();
                            }
                          var dateString =  dd.join(",");
                          $("#element-' . $fieldsField . '__' . $fieldsImp . '").val(dateString);
                          if(datecntr==1 && isSearched > 0) {
                           // console.log("change");
                             performSearch();
                          }
                          datecntr = 1;

                    });
                    $(document).ready(function(){
                       var dateString = $("#element-' . $fieldsField . '__' . $fieldsImp . '").val();
                       var dateArray = dateString.split(",");
                       var dateObject = [];
                        console.log(dateArray.length);
                        for(var i=0; i<dateArray.length;i++){
                             dateObject[i] = new Date(dateArray[i]);

                           // console.log(dateObject);
                        }
                        datecntr = 0;
                        $(obj_' . $calendarCount . ').datepicker("setDates",dateObject);
                    });
                    </script>
					</li>';

                    $html .= '</ul>';
                    $html .= '</li>';
                }
            }
            $html .= '</ul>';
        }
        Session::put('required', $fClass);
        return $html;
    }

    public static function prepareFieldsMapFilters($data)
    {
        $arr = [];

        for ($i = 0; $i < count($data['table_filter']); $i++) {
            if (!empty($data['table_filter'][$i])) {
                $tempArray = ['field'=>$data['table_filter'][$i], 'filter_label'=>$data['filter_label'][$i], 'filter_description'=>$data['filter_description'][$i], 'type'=>'C', 'value'=>$data['default_value'][$i], 'is_required'=>$data['is_required'][$i], 'sort_order'=>$data['sort_order'][$i], 'imp'=>$data['important'][$i]];
                $arr[] = $tempArray;
            }
        }

        return json_encode($arr);
    }

    public static function prepareDefaultFilters($data)
    {
        $arr = [];

        for ($i = 0; $i < count($data['default_filter_field']); $i++) {
            if (!empty($data['default_filter_field'][$i])) {
                $tempArray = ['field'=>$data['default_filter_field'][$i], 'operator'=>$data['default_filter_operator'][$i], 'value'=>$data['default_filter_value'][$i]];
                $arr[] = $tempArray;
            }
        }

        return json_encode($arr);
    }

    public static function prepareFieldsOnMap($data)
    {
        $fdata = [];

        if(isset($data['card_heading'])) {
            foreach ($data['card_heading'] as $cardHeading) {
                if (!empty($cardHeading)) {
                    $fdata['card_heading'][] = $cardHeading;
                }
            }
        }
        if(isset($data['card_subheading'])) {
            foreach ($data['card_subheading'] as $card_subheading) {
                if (!empty($card_subheading)) {
                    $fdata['card_subheading'][] = $card_subheading;
                }
            }
        }

        foreach ($data['card_fields']['fields'] as $key => $cardFields) {

            if (!empty($data['card_fields']['fields'][$key])) {
                $title = !empty($data['card_fields']['title'][$key])?$data['card_fields']['title'][$key]:str_replace('_', ' ', $data['card_fields']['fields'][$key]);
                $name = $data['card_fields']['fields'][$key];
                $temp = [];
                $temp = ['title'=>$title, 'name'=>$name];
                $fdata['fields'][] = $temp;
            }
        }

        return json_encode($fdata);
    }

    public static function prepareFieldsDatatable($data)
    {
        $fdata = [];
        $fdata['table_heading'][] = 'Distance (in Miles)';
        $fdata['datatable'][] = 'distance';
        foreach ($data['table_heading']['field'] as $key=> $dataTable) {
            if (!empty($data['table_heading']['field'][$key])) {
                $table_heading = !empty($data['table_heading']['title'][$key])?$data['table_heading']['title'][$key]:str_replace('_', ' ', $data['table_heading']['field'][$key]);

                $fdata['table_heading'][] = $table_heading;
                $fdata['datatable'][] = $data['table_heading']['field'][$key];
            }
        }

        return json_encode($fdata);
    }

    public static function isLoggedIn()
    {
        if (Auth::check()) {
            return true;
        } else {
            return false;
        }
    }

    public static function getLoggedInUser()
    {
        if (self::isLoggedIn()) {
            return Auth::getUser()->getAttributes();
        } else {
            return '';
        }
    }

    public static function getSelectedMapInfo(bool $withLayers = false, int $mapId = null)
    {
        H::ExcelLog(auth()->user()->name . ' in H.getSelectedMapInfo','mapQuery.log');

        $data = null;
        $data = DB::table('map_configurations')->where('map_id', $mapId ?? self::getSelectedMap())->get();
        if (count($data)) {

            if ($withLayers) {
                $data[0]->layers = Map::where('parent_id', $data[0]->map_id)->get();
            }

            H::ExcelLog(auth()->user()->name . ' getSelectedMapInfo: ' . print_r($data,true),'mapQuery.log');
            return $data;
        } else {
            H::ExcelLog(auth()->user()->name . ' getSelectedMapInfo not found','mapQuery.log');
            return null;
        }
    }

    public static function getSelectedMap()
    {
        H::ExcelLog(auth()->user()->name . ' in H.getSelectedMap: ' . print_r(Session::get('selectedmap'),true),'mapQuery.log');
        H::ExcelLog(auth()->user()->name . ' session: ' . print_r(Session::all(),true),'mapQuery.log');

        if(LD::checkFeatureFlag("map-error-handling-table-map-view")) {
            $ignoreRoute = ['map.setmapcolors','map.getdistinct','map.downloaddata','map.configurations','map.map','map.getmainlocations','map.getlocationdata','map.getmultiplelocations','map.table'];

            if(in_array(Route::currentRouteName(),$ignoreRoute) && !Session::get('selectedmap')){
                if(request()->ajax()) {
                    return response()->json(['mapunset'=>true])->send();
                } else {
                    return redirect(route('map.mapIntro'))->send();
                }

            }
        }

        return Session::get('selectedmap');
    }

    public static function getMapTableData()
    {
        H::ExcelLog(auth()->user()->name . ' in H.getMapTableData','mapQuery.log');

        $tableName = self::getSelectedMapInfo()[0]->map_tablename;
        $data = null;

        $data = DB::select('select * from ' . $tableName . ' limit 1');

        return addslashes(json_encode($data));
    }

    public static function getConfigurationOperators()
    {
        return ['=', '!=', '>', '>=', '<', '<='];
    }

    public static function getTableFieldsForSelectedMap($table='')
    {
        $tableName='';

        if(!empty($table)) {
            $tableName = $table;
        } else {
            $tableName = self::getSelectedMapInfo()[0]->map_tablename;
        }

        $fields = DB::select('DESCRIBE ' . $tableName);

        $excluded = ['needs_geocoding', 'lat', 'lng', 'created_at', 'updated_at', 'id', 'is_updated'];
        foreach ($fields as $key => $value) {
            if (in_array($value->Field, $excluded)) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    public static function getMapData($dTableRequest)
    {
        H::ExcelLog(auth()->user()->name . ' in H.getMapData','mapQuery.log');
        //self::$sqlParameters=[];

        error_reporting(0);
        $totalHours = 0;

        $parentMapInfo = self::getSelectedMapInfo();
        $sessionTableName = $parentMapInfo[0]->map_tablename;
        $lastmapsearch = Session::get($sessionTableName);

        if (isset($dTableRequest['layer_id']) && !empty($dTableRequest['layer_id'])) {
            $mapInfo = DB::table('map_configurations')->where('map_id', $dTableRequest['layer_id'])->get();
        } else {
            $mapInfo = $parentMapInfo;
        }

        $tableName = $mapInfo[0]->map_tablename;

        if (isset($lastmapsearch['lastmapsearch']) && is_array($lastmapsearch['lastmapsearch'])) {
            $search = $lastmapsearch['lastmapsearch'];
        } else {
            $search = [];
        }

        $where = '1=1 ';
        $select = ' ';
        $having = ' 1 = 1 ';
        $orderBy = ' ';
        $join = '';
        $currentSort = null;
        $forPrefixTables = [
            'maps_nthalerts',
            'maps_nth',
            'maps_covclin',
        ];
        $forEscapeSpecificColumns = [
            'maps_coldcall',
            'maps_potential_nurse',
            'maps_potential_nurse_new'
        ];

        $prefix = in_array($tableName, $forPrefixTables) ? $tableName . '.' : '';

        if (count($search)) {
            foreach ($search as $k =>$val) {
                if ($k != 'isbtnclicked' && $k != 'ismappage' && $k != 'search_text' && $k != 'colors' && $k != 'grouping-field' && $k != 'enable_grouping' && $k != 'radius' && $k != 'lat' && $k != 'lng' && $k != 'state' && $k != 'locationString' && $k != 'location' && $k != 'formatedString' && $k != 'country' && $k != 'mode' && $k != 'already_search') {
                    $fieldSearch = [];
                    $queryStr = '';
                    $fieldExtract = self::decryptFieldName($k);
                    $k = $fieldExtract[0];
                    $imp = $fieldExtract[1];
                    $dummyColumn = self::addDummyColumn($k, $val, $imp);
                    $select .= $dummyColumn;
                    $specialString = self::isSpecialFilter($k, $val, $imp);

                    if (empty(trim($specialString))) {
                        $cnt = 0;
                        foreach ($val as $kv => $fieldVal) {

                            $fieldSearch[] = ' `' . $k . '` =  :'.$k.'_cusotm'.$cnt;
                            self::$sqlParameters[$k.'_cusotm'.$cnt] =  $fieldVal;
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
            self::ExcelLog(auth()->user()->name . ' ' . $where, 'mapfilter.log');
            if (isset($search['radius']) && $search['radius'] != '') {
                if ($search['lat'] != '' && $search['lng'] != '') {
                    if(LD::checkFeatureFlag('round-map-distance-column')){
                            // Round the distance to 2 decimal places
                            $select .= sprintf(", ROUND((3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos(radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat))))) AS distance", $search['lat'], $search['lng'], $search['lat']);

                            $where .= 'AND lat <> "" AND lng <> "" ';
                            $having .= sprintf(' and distance <= ' . ('%f+0.5'), $search['radius']);
                            $orderBy = ' ORDER BY distance, ' . $prefix . 'id';
                    }else{
                        $select .= sprintf(", (3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos( radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat)))) AS distance",$search['lat'],$search['lng'],$search['lat']);
                        $where .= 'AND lat <> "" AND lng <> "" ';
                        $having .= sprintf(' and distance <= ' . ('%f+0.5'),$search['radius']);
                        $orderBy = ' ORDER BY distance, ' . $prefix . 'id';
                    }
                } else {
                    $res = ['err'=>'Latitude and longitude not defined', 'status'=>false];
                }
            } elseif ($search['radius'] == '' && !empty($search['state'])) {
                $where .= ' AND lat <> "" AND lng <> "" AND `State` = :State';
                self::$sqlParameters["State"] = $search['state'];
            } else {
                if(LD::checkFeatureFlag('changes-specific-to-map_nthalerts')) {
                    if(H::getHelper('ConstantHelper')->isNTHAlertMap($tableName)) {
                        $where .= ' ';
                    } else {
                        $where .= ' AND lat <> "" AND lng <> ""';
                    }
                } else {
                    $where .= ' AND lat <> "" AND lng <> ""';
                }
            }

            if (isset($mapInfo[0]->map_default_filter)) {
                $defaultFilters = json_decode($mapInfo[0]->map_default_filter, true);

                if (count($defaultFilters)) {
                    $where .= ' AND ' . $tableName. '.' . $defaultFilters[0]['field'] . ' ' . $defaultFilters[0]['operator'] . ' :' . $defaultFilters[0]['field'] . '_default';
                    self::$sqlParameters[$defaultFilters[0]['field'].'_default'] = $defaultFilters[0]['value'];
                }
            }

            if(LD::checkFeatureFlag('coverage-clinician-map-prn-process')){
                if(H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {

                    $select.= ", DATE_FORMAT(
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

                    $select.= sprintf(", CASE WHEN Days_Available = '' THEN Shift_Type ELSE IF(
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

                    $join.= sprintf(" JOIN temp_number n ON
                        CHAR_LENGTH(maps_covclin.Days_Available) - CHAR_LENGTH(
                        REPLACE
                            (
                                maps_covclin.Days_Available,
                                ',',
                                ''
                            )
                    ) >= n.num - 1 ");

                    if(LD::checkFeatureFlag('coverage-clinician-map-remove-based-on-date')) {
                        $having.= " and
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

                    foreach ($search as $k =>$val) {
                        if ($k != 'isbtnclicked' && $k != 'ismappage' && $k != 'search_text' && $k != 'colors' && $k != 'grouping-field' && $k != 'enable_grouping' && $k != 'radius' && $k != 'lat' && $k != 'lng' && $k != 'state' && $k != 'locationString' && $k != 'location' && $k != 'formatedString' && $k != 'country' && $k != 'mode' && $k != 'already_search') {
                            $fieldSearch = [];
                            $queryStr = '';
                            $fieldExtract = self::decryptFieldName($k);
                            $k = $fieldExtract[0];
                            $imp = $fieldExtract[1];

                            if ($k == 'Shift_Type') {
                                $field = "Shift_Type2";

                                $preparedQuery = '';
                                $query = [];
                                $cntr = 0;
                                foreach ($val as $key => $v) {
                                    $query[] = ' ( ' . $field . ' like :'.$field.'_partial_'.$cntr.' ) ';
                                    self::$sqlParameters[$field.'_partial_'.$cntr] = "%".$v."%";
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

            if (isset($dTableRequest['search']['value']) && $dTableRequest['search']['value'] != '') {
                $fields = self::getTableFieldsForSelectedMap();
                $qry = [];

                foreach ($fields as $field) {
                    $fieldName = $field->Field;
                    $paramName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName)) . '_text';
                    $requestPattern = in_array($tableName, $forEscapeSpecificColumns) ? '(`%s%s` LIKE :%s)' : '(%s%s LIKE :%s)';

                    $qry[] = sprintf($requestPattern, $prefix, $fieldName, $paramName);
                    self::$sqlParameters[$paramName] = "%" . $dTableRequest['search']['value'] . "%";
                }

                $where .= ' AND (' . implode(' OR ', $qry) . ')';
            }

            if (isset($dTableRequest['order'][0]) && $dTableRequest['order'][0] != '') {
                $col = 0;

                if (!isset($search['radius']) && intval($dTableRequest['order'][0]['column']) == 1) {
                    $col = 2;
                } else {
                    $col = intval($dTableRequest['order'][0]['column']);
                }

                if (isset($dTableRequest['isCheckBox']) && $dTableRequest['isCheckBox'] == 1 && intval($dTableRequest['order'][0]['column']) != 1) {
                    $col = intval($dTableRequest['order'][0]['column']) - 1;
                }

                if (LD::checkFeatureFlag('coverage-tracker-map') && H::getHelper('ConstantHelper')->isCoverageMap($tableName) && intval($dTableRequest['order'][0]['column']) != 1) {
                    $col = intval($dTableRequest['order'][0]['column']) - 1;
                }

                $cl = $dTableRequest['columns'][$col]['name'];

                if ($cl === 'distance') {
                    $orderB = sprintf(' ORDER BY distance %s', $dTableRequest['order'][0]['dir']);
                } else {
                    $orderB = sprintf(' ORDER BY ' . $prefix . '%s %s', $cl, $dTableRequest['order'][0]['dir']);
                }

                if (!empty(trim($orderBy))) {
                    $orderB .= ' , ' . str_replace("ORDER BY", "", $orderBy);
                }
                $orderBy = $orderB;


                if (LD::checkFeatureFlag('coverage-clinician-map-prn-process')) {
                    if (H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {
                        $col = intval($dTableRequest['order'][0]['column']);

                        if ($col == 1) {
                            $orderBy = sprintf(' ORDER BY `%s` %s', "custom_sort_prn", "desc");
                        }
                    }
                }
            }

            if ($tableName === 'maps_nth') {
                $join .= sprintf('LEFT JOIN NeedToHire_Accounts nta ON nta.ClientID = '.$tableName.'.ClientID');
                $where .= ' AND nta.IsActive = 1';
            }

            $needToDisableIsIgnoreFilter = $tableName === 'maps_covclin' && LD::checkFeatureFlag('maps-covclin-include-is-ignored');
            if ($tableName == 'maps_nthalerts') {
                $join .= sprintf('LEFT JOIN mssql_providers mp ON mp.ClinicianID = '.$tableName.'.ClinicianID');
                $where .= ' AND ifnull('.$tableName.'.is_ignored,0) != 1';
                $where .= ' AND mp.IsOfficeStaff != 1';
            } elseif (!$needToDisableIsIgnoreFilter) {
                $where .= ' AND ifnull(is_ignored,0) != 1';
            }

            $totalRecords = DB::select(
                'select * ' . $select .
                ' from `' . $tableName . '` ' . $join .
                ' where ' . $where .
                ' having ' . $having .
                $orderBy,
                self::$sqlParameters
            );

            if (isset($dTableRequest['type']) && $dTableRequest['type'] == 'download') {
                return $totalRecords;
            }

            if (Schema::hasColumn($tableName, 'NTH_Hours')) {
                foreach ($totalRecords as $rec) {
                    $totalHours += $rec->NTH_Hours;
                }
            }

            $limit = '';
            if (isset($dTableRequest['start']) && $dTableRequest['length'] != '-1') {
                 $limit = sprintf(' Limit %d , %d',$dTableRequest['start'],$dTableRequest['length']);
            }

            self::ExcelLog('select * ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . ' having ' . $having . $orderBy . $limit, 'mapChartQuery.log');
            $map1Data = DB::select('select * ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . ' having ' . $having . $orderBy . $limit,self::$sqlParameters);
            H::ExcelLog("User: " . auth()->user()->name . " : " . 'select * ' . $select . ' from `' . $tableName . '` '.$join.' where ' . $where . ' having ' . $having . $orderBy . $limit, 'mapQuery.log');
            if (empty($lastmapsearch)) {
                return [];
            }

            $total_records = count($totalRecords);

            //P1-3904 -  Adding moon to night shift for coverage map
            if($tableName == 'maps_coverageneeds') {
                if (Schema::hasColumn($tableName, 'Schedule_Time')) {
                    foreach ($map1Data as $key => $value) {
                        if($value->Schedule_Time != ''){
                            $scheduleArr = explode("-",$value->Schedule_Time);
                            $day = date('l', strtotime($value->Date));
                            if(trim($scheduleArr[0])) {
                                $dbStartHour = date('H:i', strtotime(trim($scheduleArr[0])));
                                $dbEndHour = date('H:i', strtotime(trim($scheduleArr[1])));
                                $time = H::getHelper("ConstantHelper")->getTimeDifference($dbStartHour,$dbEndHour);
                                $shiftType = H::getHelper("ConstantHelper")->getShiftType($day,$time['startAMPM'],$time['endAMPM']);
                                if($shiftType->ShiftTypeDescription=='Weekend Nights (PM-AM)' || $shiftType->ShiftTypeDescription=='Weekday Nights (PM-AM)') {
                                    $map1Data[$key]->Schedule_Time = $value->Schedule_Time.'&nbsp;&nbsp;<span class="moon"><i class="fa fa-moon-o" aria-hidden="true"></i></span>';
                                }
                            }
                        }
                    }
                }
            }
            if(LD::checkFeatureFlag('coverage-clinician-map-prn-process')){
                if(H::getHelper("ConstantHelper")->isCoverageClinicianMap($tableName)) {
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
                $modified = self::convertToBooleanValue($modified);
                $data = [
                    'draw'            => intval($dTableRequest['draw']),
                    'totalhours'=>$totalHours,
                    'recordsTotal'    => intval($total_records),
                    'recordsFiltered' => intval($total_records),
                    'data'            => $modified,
                ];
                if (self::getSelectedMapInfo()[0]->map_type == 'PHI') {
                    $loggedInUser = self::getLoggedInUser();
                    $phiData['user']['id'] = $loggedInUser['id'];
                    $phiData['user']['name'] = $loggedInUser['name'];
                    $phiData['user']['ip'] = request()->ip();
                    $phiData['map_name'] = self::getSelectedMapInfo()[0]->map_name;

                    foreach ($modified as $key =>$value) {
                        $phiData['results'][] = $value->id;
                    }

                    self::ExcelLog(json_encode($phiData), 'mapChartSearch.log');
                }
                return $data;
            }
            $map1Data = self::convertToBooleanValue($map1Data);

            $data = [
                'draw'            => intval($dTableRequest['draw']),
                'totalhours'=>$totalHours,
                'recordsTotal'    => intval($total_records),
                'recordsFiltered' => intval($total_records),
                'data'            => $map1Data,
            ];

            if (self::getSelectedMapInfo()[0]->map_type == 'PHI') {
                $loggedInUser = self::getLoggedInUser();
                $phiData['user']['id'] = $loggedInUser['id'];
                $phiData['user']['name'] = $loggedInUser['name'];
                $phiData['user']['ip'] = request()->ip();
                $phiData['map_name'] = self::getSelectedMapInfo()[0]->map_name;

                foreach ($map1Data as $key =>$value) {
                    $phiData['results'][] = $value->id;
                }

                self::ExcelLog(json_encode($phiData), 'chartView.log');
            }

            return $data;
        }
        $data = [
            'draw'            => intval($dTableRequest['draw']),
            'totalhours'=>$totalHours,
            'recordsTotal'    => 0,
            'recordsFiltered' => 0,
            'data'            => [],
        ];

        return $data;
    }

    public static function convertToBooleanValue($data)
    {
        $map_boolean_field = json_decode(self::getSelectedMapInfo()[0]->map_boolean_field, true);

        $records = [];
        foreach ($data as $key => $value) {
            $rec = [];
            foreach ($value as $k => $v) {
                if (is_array($map_boolean_field) && in_array($k, $map_boolean_field)) {
                    if ($data[$key]->{$k} == '0') {
                        $data[$key]->{$k} = 'No';
                    } elseif ($data[$key]->{$k} == '1') {
                        $data[$key]->{$k} = 'Yes';
                    }
                }
            }
        }

        return $data;
    }

    public static function objToArr($dbResults)
    {
        return json_decode(json_encode($dbResults), true);
    }

    public static function getAllMaps()
    {
        $mapConfiguration = null;

        $mapConfiguration = DB::table('map_configurations')->where('map_status', '1')->get()->toArray();

        return $mapConfiguration;
    }

    public static function getMapById($id)
    {
        H::ExcelLog(auth()->user()->name . ' in H.getMapById','mapQuery.log');

        $mapConfiguration = null;

        $mapConfiguration = DB::table('map_configurations')->where('map_status', '1')->where('map_id', $id)->get()->toArray();

        return isset($mapConfiguration[0]) ? $mapConfiguration[0] : [];
    }

    public static function getCoreSettings($index = '')
    {
        $res = [];
        if (Schema::hasTable('core_setting')) {
            if (empty($index)) {
                $coreSetting = DB::table('core_setting')->get();
                foreach ($coreSetting as $key => $value) {
                    $res[$value->opt] = $value->val;
                }
            } else {
                $coreSetting = DB::table('core_setting')->where('opt', $index)->get()->toArray();
                if (count($coreSetting) > 0) {
                    return $coreSetting[0]->val;
                }
            }
        }

        return $res;
    }

    public static function getRestrictedPasswordList()
    {
        $res = [];
        $data = null;
        if (Schema::hasTable('restricted_password')) {
            $data = DB::table('restricted_password')->get();

            foreach ($data as $key => $value) {
                $res[] = $value->password;
            }
        }

        return $res;
    }

    public static function isMFAEnable()
    {
        $data = self::getCoreSettings();
        if (isset($data['mfa']) && $data['mfa'] == 'true') {
            return true;
        } else {
            return false;
        }
    }

    public static function removeSpecialChar($datasting)
    {
        $symbols = [' ', '!', '"', '#', '$', '%', '&', '\'', '(', ')', '*', '+', ',', '-', '.', '/', ':', ';', '<', '>', '=', '?', '@', '[', ']', '\\', '^', '_', '{', '}', '|', '~', '`'];
        $replacement = [' ']; // you can enter more replacements.

        $datasting = str_replace($symbols, $replacement, $datasting);
        $datasting = preg_replace('/\s+/', ' ', $datasting);

        return $datasting;
    }

    public static function ExcelLog($mytext = '', $logfile = '')
    {
        if (!empty($mytext) && !empty($logfile)) {
            $fname = explode('.', $logfile);
            $logfile = $fname[0] . '_' . date('Y-m-d') . '.log';

            $logtime = date('Y-m-d H:i:s');
            $msg = $logtime . '--->' . $mytext;
            $dirname = storage_path() . '/excelss/';
            $path = $dirname . $logfile;
            if (!file_exists($dirname)) {
                mkdir($dirname);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, "$msg\n");
            fclose($fp);
        }
    }

    public static function arrGroupBy($array, $key,$useMultiSort=true)
    {
        $keys = array_column($array, $key);
        if($useMultiSort){
            array_multisort($keys, SORT_ASC, $array);
        }
        $return = [];
        foreach ($array as $val) {
            if (is_array($val)) {
                $return[$val[$key]][] = $val;
            } else {
                $return[$val->$key][] = $val;
            }
        }

        return $return;
    }

    /* Sync functions end */

    public static function getChunkedCsvData($isFieldErr, $prepareDBFields, $heading, $tableName, $file, $chunk_size, $queryValuePrefix, $ignoreArray, $callback)
    {
        try {
            $handle = fopen($file, 'r');
            $i = 0;
            while (!feof($handle)) {
                call_user_func_array($callback, [
                    fread($handle, $chunk_size),
                    &$handle,
                    $i,
                    &$queryValuePrefix,
                    $tableName,
                    &$heading,
                    $prepareDBFields,
                    &$isFieldErr,
                    &$ignoreArray
                ]);
                $i++;
            }
            fclose($handle);
        } catch (\Exception $e) {
            return false;
        }
        if ($isFieldErr) {
            return -1;
        }

        return true;
    }

    public static function removeHiddenChars($arr)
    {
        foreach ($arr as $key => &$value) {
            $value = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $value);
        }

        return $arr;
    }

    public static function getColumnName($name)
    {
        $name = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $name);
        $name = str_replace(' ', '_', $name);

        return $name;
    }

    public static function getMapMarkerImage($dataIndex)
    {
        $totalMarker = 9;
        $index = $dataIndex % $totalMarker;

        return $index;
    }

    public static function getResourceMarkerPath()
    {
        return asset('resources/images/markers/');
    }

    public static function canPermissions($permissions, $source = ''): bool
    {
        if (LD::checkFeatureFlag('permissions-with-routes')) {
            //starting new way
            $role = auth()->user()->role;
            $userid = auth()->user()->id;
            $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userid);

            $roleDetails = Roles::whereIn('id', $roles)->get();
            foreach ($roleDetails as $roleDetail) {
                if ($roleDetail->id == 1 || $roleDetail->name == 'Super Admin') {
                    return true;
                }
            }

            if ($source == 'route') {
                //if this came from the routes,  check required permissions and return true if it's not a required permission
                $requiredPermissions = config('constant.requiredPermissions');
                //can $permissions be an array?
                if (is_array($permissions)) {
                    if (empty(array_intersect($permissions, $requiredPermissions))) {
                        return true;
                    }
                } else {
                    if (!in_array($permissions, $requiredPermissions)) {
                        return true;
                    }
                }
            }

            //routes not in requiredPermissons returned true abovee - now check permissions for anything else
            $rolesPermissions = Cache::tags(['{roles_permissions_cache}'])
                ->rememberForever('user_'.$userid.'_roles_permissions', function () use ($roles) {
                return RolesPermissions::whereIn('roles_id', $roles)->get()->toArray();
            });

            $assignPermissionsData = array_column($rolesPermissions, 'permissions_id');

            $userPermissions = Cache::tags(['{user_permissions_cache}'])
                ->rememberForever('user_'.$userid.'_permissions', function () use ($userid) {
                return UserPermissions::where('user_id', $userid)->get()->toArray();
            });

            $userAssignPermissionsData = array_column($userPermissions, 'permissions_id');
            $assignPermissionsData = array_merge($assignPermissionsData, $userAssignPermissionsData);

            $permissionsData = Cache::tags(['{permissions_cache}'])
                ->rememberForever('user_'.$userid.'assigned_permissions', function () use ($assignPermissionsData) {
                return Permissions::whereIn('id', $assignPermissionsData)->get()->toArray();
            });

            $permissionRouteData = [];

            foreach ($permissionsData as $key => $value) {
                $permissionRouteData[] = $value['route_name'];
                if (!empty($value['related_routes'])) {
                    $permissionRouteData = array_merge($permissionRouteData, explode(',', $value['related_routes']));
                }
            }

            if (is_array($permissions)) {
                if (empty(array_intersect($permissions, $permissionRouteData))) {
                    return false;
                } else {
                    return true;
                }
            } else {
                if (!in_array($permissions, $permissionRouteData)) {
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            ///todo this part might be removed because the FF has Targeting off status. This part of code isn't used

            //starting old way
            //H::ExcelLog('Old Way canPermissions: ' . print_r($permissions,true), 'tsPermissions.log');
            H::ExcelLog('source: ' . $source, 'tsPermissions.log');
            $role = auth()->user()->role;
            $userid = auth()->user()->id;
            $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userid);
            //H::ExcelLog('roles: ' . print_r($roles,true), 'tsPermissions.log');
            if (LD::checkFeatureFlag('multiple-roles-to-user')) {
                $roleDetails = Roles::whereIn('id', $roles)->get();
                foreach ($roleDetails as $roleDetail) {
                    if ($roleDetail->id == 1 || $roleDetail->name == 'Super Admin') {
                        return true;
                    }
                }
            } else {
                $roleDetail = Roles::where('id', $role)->first();
                if ($role == 1 || $roleDetail->name == 'Super Admin') {
                    return true;
                }
            }
            //new required permissions
                $requiredPermissions = config('constant.requiredPermissions');
                //H::ExcelLog('checking required canPermissions: ' . print_r($permissions,true), 'tsPermissions.log');
                if (is_array($permissions)) {
                    if (empty(array_intersect($permissions, $requiredPermissions))) {
                        H::ExcelLog('returning true', 'tsPermissions.log');
                        return true;
                    }
                } else {
                    if (!in_array($permissions, $requiredPermissions)) {
                        H::ExcelLog('returning true2', 'tsPermissions.log');
                        return true;
                    }
                }
                if (LD::checkFeatureFlag('multiple-roles-to-user')) {
                    $assignPermissionsData = array_column(RolesPermissions::whereIn('roles_id', $roles)->get()->toArray(), 'permissions_id');
                } else {
                    $assignPermissionsData = array_column(RolesPermissions::where('roles_id', $role)->get()->toArray(), 'permissions_id');
                }
                //H::ExcelLog(print_r($assignPermissionsData,true), 'tsPermissions.log');
                $userAssignPermissionsData = array_column(UserPermissions::where('user_id', $userid)->get()->toArray(), 'permissions_id');
                $assignPermissionsData = array_merge($assignPermissionsData, $userAssignPermissionsData);
                $permissionsData = Permissions::whereIn('id', $assignPermissionsData)->get()->toArray();
                $permissionRouteData = [];
                foreach ($permissionsData as $key => $value) {
                    $permissionRouteData[] = $value['route_name'];
                    if (!empty($value['related_routes'])) {
                        $permissionRouteData = array_merge($permissionRouteData, explode(',', $value['related_routes']));
                    }
                }
                //H::ExcelLog(print_r($permissionRouteData,true), 'tsPermissions.log');
                if (is_array($permissions)) {
                    if (empty(array_intersect($permissions, $permissionRouteData))) {
                        H::ExcelLog('returning false3', 'tsPermissions.log');
                        return false;
                    } else {
                        H::ExcelLog('returning true3', 'tsPermissions.log');
                        return true;
                    }
                } else {
                    if (!in_array($permissions, $permissionRouteData)) {
                        H::ExcelLog('returning false4', 'tsPermissions.log');
                        return false;
                    } else {
                        H::ExcelLog('returning true4', 'tsPermissions.log');
                        return true;
                    }
                }
        }
    }

    public static function getPolygonList()
    {
        $mapInfo = self::getSelectedMapInfo();

        if (!empty($mapInfo)) {
            return DB::table('polygons')->where('map_id', $mapInfo[0]->map_id)->get()->toArray();
        } else {
            return [];
        }
    }

    public static function getMssqlDate($date)
    {
        if ($date == '' || $date == '0000-00-00 00:00:00') {
            return null;
        } else {
            return date('Y-m-d H:i:s', strtotime($date));
        }
    }

    public static function getFormatedDate($date)
    {
        if ($date != '0000-00-00' && $date != '0000-00-00 00:00:00' && $date != '') {
            return date('m/d/Y', strtotime($date));
        }

        return '';
    }

    public static function getFormatedTime($datetime)
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new \DateTime($datetime);

            return $datetime->format('g:i A');
        }

        return '';
    }

    public static function getFormatedTimeWithCurrentUserTimezone($datetime): string
    {
        $timezone = config('constant.usaTimeZone')[Auth::user()->user_timezone] ?? null;

        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new \DateTime($datetime, new \DateTimeZone(config('app.timezone')));

            if ($timezone) {
                $datetime->setTimezone(new \DateTimeZone($timezone));
            }
            return $datetime->format('g:i A');
        }

        return '';
    }

    public static function getFormatedDateTime($datetime)
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new \DateTime($datetime);

            return $datetime->format('m/d/y g:i A');
        }

        return '';
    }

     public static function getFormatedLongDateTime($datetime)
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new \DateTime($datetime);

            return $datetime->format('m/d/Y g:i A');
        }

        return '';
    }

    /**
     * getMondayFromDate returns the date for the monday that contains the passed date
     * It is used for reports that have weeks as the columns
     */
    public static function getMondayFromDate($date, $weeksAgo = 0)
    {
        $dt = new \DateTime($date);
        if($weeksAgo>0){
            $dt->modify("-" . $weeksAgo . " weeks");
        }
        //now get Monday of that week
        if ($dt->format('N') == 1) {
            // If the date is already a Monday, return it as-is
            return $dt->format('Y-m-d');
        } else {
            // Otherwise, return the date of the nearest Monday in the past
            // This includes Sunday in the previous week instead of it being the start of a new week
            $dt->modify('last monday');
            return $dt->format('Y-m-d');
        }
    }
    public static function getFirstFromDate($date, $monthsAgo = 0)
    {
        $dt = new \DateTime($date);
        if($monthsAgo>0){
            $dt->modify("-" . $monthsAgo . " months");
        }
        //now get the first of that month
        if ($dt->format('N') == 1) {
            // If the date is already a Monday, return it as-is
            return $dt->format('Y-m-d');
        } else {
            // Otherwise, return the date of the nearest Monday in the past
            // This includes Sunday in the previous week instead of it being the start of a new week
            $dt->modify('last monday');
            return $dt->format('Y-m-d');
        }
    }
    /**
     * getReportStartingDate returns the first date to include in a report query based on the period and how many previous periods to include
     * pass the period and the number of periods to cover.  It defaults to 13 weeks or 12 months.  Right now it's ALWAYS the most recent full periods
     */
    public static function getReportStartingDate($period = 'month', $count = 0, $startDate = '')
    {
        if($period=='week'){
            if (!empty($startDate)) {
                $startDate = H::getMondayFromDate($startDate,$count);
            } else {
                if($count==0) {
                    $count = 13;
                }
                $startDate = H::getMondayFromDate(date('Y-m-d'),$count);
            }
        }elseif($period=='day'){
            if (empty($startDate)) {
                $dt = new \DateTime($startDate);
                $dt->modify("-7 days");
                $startDate = $dt->format('Y-m-d');
            }
        }else{
            if (!empty($startDate)) {
                $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->startOfMonth()->subMonths($count)->toDateString();
            } else {
                if($count==0) {
                    $count = 12;
                }
                $startDate = Carbon::now()->startOfMonth()->subMonths($count)->toDateString();
            }
        }
        return $startDate;
    }
    /**
     * getReportEndingDate returns the last date to include in a report query (last day of the last month or Sunday of the last full week)
     * pass the period
     */
    public static function getReportEndingDate($period = 'month', $endDate = ''){
        if($period=='week'){
            if (!empty($endDate)) {
                $endingDate = date('Y-m-d', strtotime('sunday this week', strtotime($endDate)));
            } else {
                $day = date('w');
                if (LD::checkFeatureFlag('nrr-report-changes')) {
                    $endingDate = date('Y-m-d');
                } else {
                    $endingDate = date('Y-m-d', strtotime('last Sunday'));
                }
            }
        }elseif($period=='day'){
            if (empty($endDate)) {
                $dt = new \DateTime($endDate);
                $dt->modify("-1 day");
                $endingDate = $dt->format('Y-m-d');
            }else{
                $endingDate = $endDate;
            }
        }elseif($period=='quarter'){
            if (empty($endDate)) {
                $dt = new \DateTime($endDate);
                $dt->modify("-1 day");
                $endingDate = $dt->format('Y-m-d');
            }else{
                $endingDate = $endDate;
            }
        }else{
            if (!empty($endDate)) {
                $endingDate = Carbon::createFromFormat('Y-m-d', $endDate)->endOfMonth()->toDateString();
            } else {
                $endingDate = Carbon::now()->subMonth()->endOfMonth()->toDateString();
            }
        }
        return $endingDate;
    }
    /**
     * getReportPeriodDefinition creates the mysql code to include to create the buckets.
     * pass the period and the fieldname from the datasource (include the table name or alias)
     */
    public static function getReportPeriodDefinition($period = 'month', $fieldname = '', $convertIntoDateFormat = false) {
        $periodDef = '';
        if($fieldname != '') {
            if($period == 'week') {
                if ($convertIntoDateFormat) {
                    $periodDef = " date_add(DATE(".$fieldname."), interval  -WEEKDAY(".$fieldname.") day) ";
                } else {
                    $periodDef = " date_add(".$fieldname.", interval  -WEEKDAY(".$fieldname.") day) ";
                }
            } elseif($period == 'day') {
                if ($convertIntoDateFormat) {
                    $periodDef = " DATE(".$fieldname.") ";
                } else {
                    $periodDef = " ".$fieldname." ";
                }
            } elseif($period == 'quarter') {
                $periodDef = "DATE_FORMAT(MAKEDATE(YEAR(".$fieldname."), 1) + INTERVAL QUARTER(".$fieldname.") QUARTER - INTERVAL 1 QUARTER,'%Y-%m')";
            } else {
                //month
                $periodDef = " DATE_FORMAT(".$fieldname.",'%Y-%m') ";
            }
        }
        return $periodDef;
    }

    /**
     * getReportPeriodColumns calculates the date value (which will match value of query) and label (formatted for output)
     * for each period covered in columns on the report.
     * pass the period and number of periods to include.  Currently it's always the most recent time periods
     */
    public static function getReportPeriodColumns($period = 'month', $count = 0, $toDate = '')
    {
        $columnArr = [];
        if($period=='week'){
            if (!empty($toDate)) {
                for($index=1;$index <= $count;$index+=1) {
                    $name = H::getMondayFromDate($toDate,$index-1);
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            } else {
                if (!LD::checkFeatureFlag('nrr-report-changes')) {
                    if($count==0) {
                        $count = 1;
                    }
                }
                for($index=$count;$index <= 13;$index+=1) {
                    $name = H::getMondayFromDate(date('Y-m-d'),$index);
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            }
        }elseif($period=='day'){
            if (!empty($toDate)) {
                for($index=1;$index <= $count;$index+=1) {
                    $day = Carbon::createFromFormat('Y-m-d', $toDate)->subDays($index-1)->toDateString();
                    $name = Carbon::parse($day)->format('Y-m-d');
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            } else {
                if($count==0) {
                    $count = 1;
                }
                for($index=$count;$index <= 7;$index+=1) {
                    $day = Carbon::createFromFormat('Y-m-d', $toDate)->subDays($index-1)->toDateString();
                    $name = Carbon::parse($day)->format('Y-m-d');
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            }
        }elseif($period=='quarter'){
            if (!empty($toDate)) {
				for($index=$count;$index > 0;$index-=1) {
					$qtr = self::get_quarters($toDate, $toDate);
					$toDate = Carbon::createFromFormat('Y-m-d', $qtr[0]['period_start'])->subMonths(3)->toDateString();
					$columnArr[] = ['name' => Carbon::parse($qtr[0]['period_start'])->format('Y-m'), 'label' => $qtr[0]['period']];
				}
            } else {
                if($count==0) {
                    $count = 12;
                }
                for($index=$count;$index > 0;$index-=3) {
                    $month = Carbon::now()->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            }
        }else{
            //month
            if (!empty($toDate)) {
                for($index=0;$index <= $count - 1;$index+=1) {
                    $month = Carbon::createFromFormat('Y-m-d', $toDate)->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            } else {
                if($count==0) {
                    $count = 1;
                }
                for($index=$count;$index <= 12;$index+=1) {
                    $month = Carbon::now()->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            }
        }
        return $columnArr;
    }

	public static function getReportPeriodColumnsNew($period = 'month', $count = 0, $toDate = '')
    {
        $columnArr = [];
        if($period=='week'){
            if (!empty($toDate)) {
                for($index=1;$index <= $count;$index+=1) {
                    $name = H::getMondayFromDate($toDate,$index-1);
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            } else {
                if (!LD::checkFeatureFlag('nrr-report-changes')) {
                    if($count==0) {
                        $count = 1;
                    }
                }
                for($index=$count;$index <= 13;$index+=1) {
                    $name = H::getMondayFromDate(date('Y-m-d'),$index);
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            }
        }elseif($period=='day'){
            if (!empty($toDate)) {
                for($index=1;$index <= $count;$index+=1) {
                    $day = Carbon::createFromFormat('Y-m-d', $toDate)->subDays($index-1)->toDateString();
                    $name = Carbon::parse($day)->format('Y-m-d');
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            } else {
                if($count==0) {
                    $count = 1;
                }
                for($index=$count;$index <= 7;$index+=1) {
                    $day = Carbon::createFromFormat('Y-m-d', $toDate)->subDays($index-1)->toDateString();
                    $name = Carbon::parse($day)->format('Y-m-d');
                    $columnArr[] = ['name' => $name, 'label' => self::getFormatedDate($name)];
                }
            }
        }elseif($period=='quarter'){
            if (!empty($toDate)) {
                if (LD::checkFeatureFlag('fix-nurse-churn-quarter-calculation')) {
                    for($index=$count;$index > 0;$index-=1) {
                        $qtr = self::get_quarters($toDate, $toDate);
                        $toDate = Carbon::createFromFormat('Y-m-d', $qtr[0]['period_start'])->subMonths(3)->toDateString();
                        $columnArr[] = ['name' => Carbon::parse($qtr[0]['period_start'])->format('Y-m'), 'label' => $qtr[0]['period']];
                    }
                } else {
                    for($index=$count;$index > 0;$index-=3) {
                        $month = Carbon::createFromFormat('Y-m-d', $toDate)->startOfMonth()->subMonths($index)->toDateString();
                        $name = Carbon::parse($month)->format('Y-m');
                        $label = Carbon::parse($month)->format('M Y');
                        $columnArr[] = ['name' => $name, 'label' => $label];
                    }
                }
            } else {
                if($count==0) {
                    $count = 12;
                }
                for($index=$count;$index > 0;$index-=3) {
                    $month = Carbon::now()->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            }
        }else{
            //month
            if (!empty($toDate)) {
                for($index=0;$index <= $count - 1;$index+=1) {
                    $month = Carbon::createFromFormat('Y-m-d', $toDate)->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            } else {
                if($count==0) {
                    $count = 1;
                }
                for($index=$count;$index <= 12;$index+=1) {
                    $month = Carbon::now()->startOfMonth()->subMonths($index)->toDateString();
                    $name = Carbon::parse($month)->format('Y-m');
                    $label = Carbon::parse($month)->format('M Y');
                    $columnArr[] = ['name' => $name, 'label' => $label];
                }
            }
        }
        return $columnArr;
    }

    // get month name from number
    public static function month_name($month_number){
        return date('F', mktime(0, 0, 0, $month_number, 10));
    }


    // get get last date of given month (of year)
    public static function month_end_date($year, $month_number){
        return date("t", strtotime("$year-$month_number-1"));
    }

    // return two digit month or day, e.g. 04 - April
    public static function zero_pad($number){
        if($number < 10) {
            return "0$number";
        }

        return "$number";
    }

    // Return quarters between tow dates. Array of objects
    public static function get_quarters($start_date, $end_date){

        $quarters = array();

        $start_month = date( 'm', strtotime($start_date) );
        $start_year = date( 'Y', strtotime($start_date) );

        $end_month = date( 'm', strtotime($end_date) );
        $end_year = date( 'Y', strtotime($end_date) );

        $start_quarter = ceil($start_month/3);
        $end_quarter = ceil($end_month/3);

        $quarter = $start_quarter; // variable to track current quarter

        // Loop over years and quarters to create array
        for( $y = $start_year; $y <= $end_year; $y++ ){
            if($y == $end_year) {
                $max_qtr = $end_quarter;
            } else {
                $max_qtr = 4;
            }

            for($q=$quarter; $q<=$max_qtr; $q++){

                $current_quarter = [];

                $end_month_num = self::zero_pad($q * 3);
                $start_month_num = ($end_month_num - 2);

                $q_start_month = self::month_name($start_month_num);
                $q_end_month = self::month_name($end_month_num);

                $current_quarter['period'] = "Q$q $y";
                $current_quarter['period_start'] = "$y-$start_month_num-01";  // yyyy-mm-dd
                $current_quarter['period_end'] = "$y-$end_month_num-" .self:: month_end_date($y, $end_month_num);

                $quarters[] = $current_quarter;
                unset($current_quarter);
            }

            $quarter = 1; // reset to 1 for next year
        }

        return $quarters;

    }

    public static function datediffInQuarters($date1, $date2)
    {
        return count(self::get_quarters($date1, $date2));
    }

	public static function datediffInQuartersNew($date1, $date2)
    {
        if (LD::checkFeatureFlag('fix-nurse-churn-quarter-calculation')) {
            return count(self::get_quarters($date1, $date2));
        } else {
            $begin = new \DateTime( $date1 );
            $end = new \DateTime( $date2 );
            $interval = \DateInterval::createFromDateString('1 month');
            $period = new \DatePeriod($begin, $interval, $end);
            $counter = 0;
            foreach($period as $dt) {
                $counter++;
            }
            return floor($counter);
        }
    }


    public static function datediffInWeeks($date1, $date2)
    {
        $now = strtotime($date2);
        $your_date = strtotime($date1);
        $datediff = $now - $your_date;
        return ceil(round($datediff / (60 * 60 * 24)) / 7);
    }

    public static function monthDiff($date1, $date2)
    {
        $begin = new \DateTime( $date1 );
        $end = new \DateTime( $date2 );

        $interval = \DateInterval::createFromDateString('1 month');

        $period = new \DatePeriod($begin, $interval, $end);
        $counter = 0;
        foreach($period as $dt) {
            $counter++;
        }
        return $counter;
    }


    public static function getFilterInputArr($inputs)
    {
        $arr = [];
        if (!empty($inputs['filters'])) {
            foreach ($inputs['filters'] as $key => $value) {
                if ($value['value'] != '') {
                    $arr[$value['name']] = $value['value'];
                }
            }
        }

        return $arr;
    }

    public static function getMapGeoCode($mapTable)
    {
        if (Schema::hasTable($mapTable)) {
            $geoCoding = DB::table($mapTable)->where('needs_geocoding', '1')->get();

            return $geoCoding->count();
        } else {
            return 0;
        }
    }

    public static function getMapAddressError($mapTable)
    {
        if (Schema::hasTable($mapTable)) {
            $geoCoding = DB::table($mapTable)->where('needs_geocoding', '0')->where(function ($geoCoding) {
                $geoCoding->whereNull('lat')->orWhereNull('lng')->orWhere('lng', 0)->orWhere('lat', 0);
            })->get();

            return $geoCoding->count();
        } else {
            return 0;
        }
    }
    public static function getRecruiterAdmin($recruiterId,$needsId=false){
        $recruiterUser = DB::table('users')->select('id','name')->where('id' ,function ($q) use ($recruiterId) {
            $q->select('recruiting_admin')->from('users')->where('id',$recruiterId)->first();
        })->first();
        if($needsId && isset($recruiterUser->id) && $recruiterUser->id != ''){
            return $recruiterUser->id;
        }else if(isset($recruiterUser->name) && $recruiterUser->name != ''){
            return $recruiterUser->name;
        }else{
            return '';
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getAllRecruitersAdmin(): array
    {
        if (LD::checkFeatureFlag('add-RA-filter-recruting-dashboard')) {
            $roles = Roles::select('id')->where('name', 'Recruiting Admin')->pluck('id')->toArray();

            $users = User::select('id', 'name')
                ->whereIn('role', $roles)
                ->orWhereHas('roles', function ($q) use ($roles) {
                    $q->whereIn('role_id', $roles);
                })
                ->where('status', 1)
                ->orderBy('name')
                ->get();

            $recruiterAdmin = [];
            foreach ($users as $v) {
                $recruiterAdmin[$v->id] = $v->name;
            }
        } else {
            $users = DB::table('users')->select('*')->get()->toArray();
            $recruiterAdmin = [];
            foreach ($users as $v) {
                foreach ($users as $vv) {
                    if ($v->recruiting_admin != '' && $vv->id == $v->recruiting_admin) {
                        $recruiterAdmin[$v->id] = $vv->name;
                    }
                }
            }
        }

        return $recruiterAdmin;
    }

    public static function getOrientationCoordinator($branchId)
    {
        if(LD::checkFeatureFlag('multiple-roles-to-user')){
            $ocUser = DB::table('users')
            ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
            ->select('users.id')->whereRaw('FIND_IN_SET(?, users.branchid)', [$branchId])->where('roles.name','Orientation Coordinator')->where('status',1)->first();
        }else{
            $ocUser = DB::table('users')->join('roles', 'users.role', '=', 'roles.id')->select('users.id')->whereRaw('FIND_IN_SET(?, users.branchid)', [$branchId])->where('roles.name','Orientation Coordinator')->where('status',1)->first();
        }
        if(isset($ocUser->id)){
            return $ocUser->id;
        }else{
            return '';
        }
    }

    public static function getRecruitersByBranchId(int $branchId)
    {
        if (LD::checkFeatureFlag('multiple-roles-to-user')){
            $recruiters = DB::table('users')
            ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
            ->select('users.id')->whereRaw('FIND_IN_SET(?, users.branchid)', [$branchId])->where('roles.name','Recruiter')->where('status',1)->get();
        } else {
            $recruiters = DB::table('users')->join('roles', 'users.role', '=', 'roles.id')->select('users.id')->whereRaw('FIND_IN_SET(?, users.branchid)', [$branchId])->where('roles.name','Recruiter')->where('status',1)->get();
        }

        return $recruiters->pluck('id')->toArray();
    }

    public static function getBranch($branchId)
    {
        $branch = BranchLocations::select('branch')->where('id', $branchId)->first();
        return $branch->branch;
    }
    public static function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    function dateDiffInDays($date1, $date2){
      $diff = strtotime($date2) - strtotime($date1);
      return abs(round($diff / 86400));
    }

    public static function getOrientationCoordinatorList() {
        if(LD::checkFeatureFlag('multiple-roles-to-user')){
            $ocUser = DB::table('users')
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->select('users.branchid', 'users.name','users.id')
                ->where('status',1)
                ->where('roles.name','Orientation Coordinator')
                ->orderBy('users.name')
                ->get()
                ->toArray();
        }else{
            $ocUser = DB::table('users')
                ->join('roles', 'users.role', '=', 'roles.id')
                ->select('users.branchid', 'users.name','users.id')
                ->where('roles.name','Orientation Coordinator')
                ->orderBy('users.name')
                ->get()
                ->toArray();
        }
        return $ocUser;
    }

    public static function getAdvocacySpecialist() {

            $adUser = DB::table('users')
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->select('users.branchid', 'users.name','users.id')
                ->where('status',1)
                ->where(function($q){
                    $q->where('roles.name','Advocacy')->orWhere("roles.name","Client and Employee Advocacy Supervisor");
                })->orderBy('users.name')
                ->get()
                ->toArray();

            $adArray  = [];
            foreach($adUser as $br) {
                $adArray[] = ["value"=>$br->id,'text'=>$br->name];
            }
        return $adArray;
    }

    //This will run after editable perform in the editableController
    public static function extraOperationOnInlineEditable($reqData){
        $tableName = config('constant')['tables'][$reqData['table']];
        if ($reqData['name'] == 'CurrentStep' && $tableName == 'NeedToHire_Checklist') {
            $nthBucketList = H::getHelper('NTHChecklist')->getBuckets();
            $allKeys = array_keys($nthBucketList);
            $fieldsName = $nthBucketList[$allKeys[$reqData['value']]]['start'];
            $updateArray[$fieldsName] = date('Y-m-d H:i:s');
            DB::table('NeedToHire_Checklist')->where('id',$reqData['pk'])->whereNull($fieldsName)->update($updateArray);
        }
        if(LD::checkFeatureFlag('tagging-branch-notes')){
            // Adding the code to send the Notification on tagging on the Branch Notes on IPEA Template
            if ($reqData['name'] == 'branch_notes' && $tableName == 'recruiting_schedule') {
                $mentions = explode(',',$reqData['mentions']);
                foreach($mentions as $key => $val){
                    Notifications::addNotification(['user_id'=>$val,'title'=>'Recruiter tracker branch notes added by '.auth()->user()->name,'type'=>'ipea','related_item'=>$reqData['pk'],'notes'=>$reqData['value']]);
                }
            }
        }
        if(LD::checkFeatureFlag('tagging-clinician-next-steps-notes')){
            // Adding the code to send the Notification on tagging on the Clinician Availability Notes on IPEA Template
            if ($reqData['name'] == 'clinician_availability' && $tableName == 'mssql_recruitertracker') {
                $mentions = explode(',',$reqData['mentions']);
                foreach($mentions as $key => $val){
                    Notifications::addNotification(['user_id'=>$val,'title'=>'Recruiter tracker clinician availability notes added by '.auth()->user()->name,'type'=>'ipea','related_item'=>$reqData['schedule'],'notes'=>$reqData['value']]);
                }
            }
        }

        if(LD::checkFeatureFlag('IPEAs-changes-to-activity-log')){
            if ($tableName == 'mssql_recruitertracker') {
                if($reqData['name']=='clinician_availability'){
                    $note = 'Clinician availability notes was set to '.$reqData['value'].' by '.Auth::user()->name;
                    $notesData = ['entity' => 'tracker-activity', 'notes' => $note, 'type' => 'ipea','entity_id' => $reqData['pk']];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
                if($reqData['name']=='why_nurse_chosen'){
                    $note = 'Nurse Chosen notes was set to '.$reqData['value'].' by '.Auth::user()->name;
                    $notesData = ['entity' => 'tracker-activity', 'notes' => $note, 'type' => 'ipea','entity_id' => $reqData['pk']];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
            }
            if ($tableName == 'recruiting_schedule') {
                $scheduleDetails = Schedule::find($reqData['pk']);
                if($reqData['name']=='why_not_qualified'){
                    $note = 'Not Qualified Reason notes was set to '.$reqData['value'].' by '.Auth::user()->name;
                    $notesData = [
                        'entity' => 'tracker-activity',
                        'notes' => $note, 'type' => 'ipea',
                        'entity_id' => $scheduleDetails->tracker_id
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
                if($reqData['name']=='branch_notes'){
                    $note = 'Branch notes was set to '.$reqData['value'].' by '.Auth::user()->name;
                    $notesData = [
                        'entity' => 'tracker-activity',
                        'notes' => $note, 'type' => 'ipea',
                        'entity_id' => $scheduleDetails->tracker_id
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
            }
        }
    }

    public static function getCurrentValueOfFields($tableName,$pk,$field){
        $data = DB::table($tableName)->select($field)->where('id',$pk)->first();
        return $data->{$field};
    }

    public static function getUserNameById($id){
        if(!empty($id)){
            return User::where('id', $id)->value('name');
        }
        return '';
    }

    public static function getUserById($id){
        if(!empty($id)){
            return User::where('id', $id)->first();
        }
        return '';
    }

    public static function getWeekRange($date) {
        $ts = strtotime($date);
        $start = (date('w', $ts) == 1) ? $ts : strtotime('last monday', $ts);
        return array(date('Y-m-d', $start), date('Y-m-d', strtotime('next sunday', $start)));
    }

    public static function rebuitNTHMap() {
        try {
            $chunkSize = 100;
            DB::table('NeedToHire_Accounts')->select('id')->where('IsActive',1)->orderBy('id')->chunk($chunkSize, function ($records) {
                foreach ($records as $record) {
                    H::getHelper('NTHChecklist')->AddEditNTHMapRecord($record->id);
                    H::getHelper('NTHChecklist')->setNTHScheduleToMap($record->id);
                }
            });
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    public static function isTherapyEvaluation ($disciplineName) {
        return in_array($disciplineName, self::getTherapyEvaluationDiscipline());
    }

    public static function getTherapyEvaluationDiscipline(){
        return ['PT', 'OT', 'ST', 'PTA', 'OTA', 'STA'];
    }
    public static function filterMultiArray($arr,$searchValues){
        $res = array_filter($arr, function ($item) use ($searchValues) {
            $item = json_decode(json_encode($item), true);
            return count(array_intersect_assoc($item, $searchValues)) === count($searchValues);
        });
        return array_values(json_decode(json_encode($res), true));
    }
    public static function getProviderIdFromRenewableId($id)
    {
        $providerId = Renewables::where('id', $id)->value('aoc_provider_id');
        return $providerId;
    }

    /**
     * Generates a string with a specified number of question marks separated by commas.
     *
     * This function takes an array as input and returns a string with the number of
     * question marks equal to the count of elements in the array, separated by commas.
     * @param array $array The input array for which question marks are generated.
     * @return string A string with question marks separated by commas.
     */
    public static function generateQuestionMarksArray($array) {
        $count = count($array);
        if ($count > 0) {
            return implode(',', array_fill(0, $count, '?'));
        } else {
            return '';
        }
    }

    /**
     * Filters an array based on specified values for a given key.
     *
     * This function takes an input array and removes elements where the value of a specified key
     * matches any of the values provided in the $valuesToRemove array.
     *
     * @param array $inputArray The input array to be filtered.
     * @param array $valuesToRemove An array containing values to be removed.
     * @return array The filtered array.
     */
    public static function filterArrayByValues($inputArray, $valuesToRemove) {
        return array_filter($inputArray, function ($element) use ($valuesToRemove) {
            return !in_array($element["value"], $valuesToRemove);
        });
    }

    public static function getStatusChangeRequestUsersByRole(){
        $adUser = DB::table('users')->distinct()->select("users.id","users.name")
                ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                ->select('users.branchid', 'users.name','users.id')
                ->where('status',1)
                ->where(function($q){
                    $q->whereIn('roles.name',['Orientation Coordinator','Director of Branch Operations','Regional Director of Operations','State VP','Executive Team','Client Care Manager']);
                })->orderBy('users.name')
                ->get()
                ->toArray();

            $adArray  = [];
            foreach($adUser as $br) {
                $adArray[] = ["value"=>$br->id,'text'=>$br->name];
            }
        return $adArray;
    }

    public static function arraySwap(&$array,$swap_a,$swap_b){
		list($array[$swap_a],$array[$swap_b]) = array($array[$swap_b],$array[$swap_a]);
	}

    public static function getWorkingdays($startDate, $endDate) {
		// Array of all holidays
		$holidays = array_map('trim', explode(",", H::getCoreSettings('holiday_schedule')));

		$start = new \DateTime($startDate);
		$end = new \DateTime($endDate);

		if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
			return 0;
		}

		if ($start > $end) {
			list($start, $end) = [$end, $start];
		}

		// Exclude the start date
		$start->modify('+1 day');

		$workingDays = 0;
		while ($start <= $end) {
			$currentDate = $start->format('Y-m-d');

			// Check if it's a weekday and not a holiday
			if ($start->format('N') < 6 && !in_array($currentDate, $holidays)) {
				$workingDays++;
			}

			// Move to the next day
			$start->modify('+1 day');
		}

		return $workingDays;
	}

    public static function calculateWorkingHours($startDate, $endDate, $format = 'decimal') {
        $holidays = array_map('trim', explode(",", H::getCoreSettings('holiday_schedule')));

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        if ($start > $end) {
            list($start, $end) = [$end, $start];
        }

        $workingMinutes = 0;
        $current = clone $start;

        while ($current < $end) {
            $dayOfWeek = $current->format('N'); // 1 = Monday, 7 = Sunday
            $currentDate = $current->format('Y-m-d');
            $hour = (int)$current->format('G'); // 0 to 23

            if ($dayOfWeek < 6 && !in_array($currentDate, $holidays)) {
                $nextHour = clone $current;
                $nextHour->modify('+1 hour');
                $intervalEnd = $nextHour < $end ? $nextHour : $end;

                $diff = $current->diff($intervalEnd);
                $minutes = ($diff->h * 60) + $diff->i;
                $workingMinutes += $minutes;
            }

            $current->modify('+1 hour');
        }

        if ($format === 'decimal') {
            return round($workingMinutes / 60, 2); // e.g., 3.9
        } else {
            $hours = floor($workingMinutes / 60);
            $minutes = $workingMinutes % 60;
            return "{$hours}h {$minutes}m"; // e.g., 3h 54m
        }
    }


    public static function formSafeJson($json) {
        $json = empty($json) ? '[]' : $json ;
        $search = array('\\',"\n","\r","\f","\t","\b","'") ;
        $replace = array('\\\\',"\\n", "\\r","\\f","\\t","\\b", "&#039");
        $json = str_replace($search,$replace,$json);
        return $json;
    }

    public function getClientInsuranceById($id){
        $paysourcedata = [];
        if(!empty($id)){
            $primary = DB::table('client_payment_source')
            ->leftJoin('payment_source', 'payment_source.payersource_id', 'client_payment_source.primary_payer_id')
            ->where('client_payment_source.primary_payer_id', $id->kantime_primary_payer_id)
            ->where('client_payment_source.intake_id', $id->IntakeID)
            ->where('client_payment_source.additional_payer_id', 0)
            ->first();
            if(!empty($primary)){
                $paysourcedata[0]['name'] =  $primary->payergroup_name;
                $paysourcedata[0]['priority'] =  'Primary';
                $paysourcedata[0]['effective_date'] =  self::createDateTextFromDate($primary);
                $paysourcedata[0]['id'] =  $primary->unique_id;
            }
            $secondary = DB::table('client_payment_source')
            ->leftJoin('payment_source', 'payment_source.payersource_id', 'client_payment_source.additional_payer_id')
            ->where('client_payment_source.additional_payer_id', $id->secondary_payer_id)
            ->where('client_payment_source.intake_id', $id->IntakeID)
            ->first();
            if(!empty($secondary)){
                $paysourcedata[1]['name'] =  $secondary->payergroup_name;
                $paysourcedata[1]['priority'] =  'Secondary';
                $paysourcedata[1]['effective_date'] =  self::createDateTextFromDate($secondary);
                $paysourcedata[1]['id'] =  $secondary->unique_id;
            }
        }
        return $paysourcedata;
    }

    public function createDateTextFromDate($data){
        $text = '';
        if(!empty($data)){
            if((empty($data->start_date) || $data->start_date == '0000-00-00 00:00:00')
            && (empty($data->end_date) || $data->end_date == '0000-00-00 00:00:00')){
                $text = 'From Beginning - No End Date';
            }elseif(!empty($data->start_date) &&
            (empty($data->end_date) || $data->end_date == '0000-00-00 00:00:00')){
                $text = self::getFormatedDate($data->start_date). '- No End Date';
            }elseif((empty($data->start_date) || $data->start_date == '0000-00-00 00:00:00') && !empty($data->end_date)){
                $text = 'From Beginning - '.self::getFormatedDate($data->end_date);
            }
        }
        return $text;
    }
    public static function getReportAverage($data, $countData, $week) {
        $weekNumberCount = 0;
        $count = 0;
        $sum = 0;
        foreach ($data as $key => $val) {
            if ($key == 'coverage_booked_goal') {
                continue;
            } else {
                if ($count == 0) {
                    $count++;
                    continue;
                } else {
                    if ($val > 0) {
                        $weekNumberCount++;
                        if (request()->view == 'percentage') {
                            $sum = $sum + round($val / $countData[$key]);
                        } else {
                            $sum = $sum + $val;
                        }
                    }
                    $count++;
                    if ($count > $week) {
                        break;
                    }
                }
            }
        }
        return ($weekNumberCount > 0) ? round($sum / $weekNumberCount) : 0;
    }

    public function createNotificationForNth($nthAcId){
        if(!empty($nthAcId)){
            $data = DB::table('NeedToHire_Accounts')
            ->select('NeedToHire_Accounts.user_id as assigned_recruiter',
            'users.recruiting_teamleader as recruiting_team_lead',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName, ' ', IFNULL(mssql_patients.Patient_MiddleName, ''), ' ', mssql_patients.Patient_LastName) as full_name"))
            ->leftJoin('mssql_patients', 'mssql_patients.id', 'NeedToHire_Accounts.aoc_patient_id')
            ->leftJoin('users', 'users.id', 'NeedToHire_Accounts.user_id')
            ->where('NeedToHire_Accounts.id', $nthAcId)->first();
            if(!empty($data)){
                $title = $data->full_name."'s NTH Schedule has changed. Please review.";
                foreach($data as $key => $id){
                    if($key != 'full_name'){
                        if(!empty($id)){
                            Notifications::addNotification(['user_id'=>$id,'title'=>$title,'type'=>'nthAccount','related_item'=>$nthAcId]);
                        }
                    }
                }
            }
        }
    }

    public static function getReportFiltersFieldVal($field, $type) {
        $userDetail = User::find(auth()->user()->id);
        $filters = json_decode($userDetail->report_filters, true);
        if (isset($filters[$field])) {
            return $filters[$field];
        }
        if ($type == 'multiselect') {
            return [];
        } else {
            return '';
        }
    }
    public static function getFormatedAmountWithCurrency($amt)
    {
        $formatedAmount = ($amt >= 0) ? '$' : '-$';
        $formatedAmount = $formatedAmount.self::getFormatedAmount(abs($amt));
        return $formatedAmount;
    }
    public static function getFormatedAmount($amount)
    {
        return number_format($amount, 2);
    }

    public static function getRolesAbbreviationForReport($userid)
    {
        $roleAbbreviation = [];
        $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userid);
        $rolesName = Roles::whereIn('id', $roles)->pluck('name')->toArray();
        $cccRoleExists = in_array('Client Care Coordinator', $rolesName);
        if ($cccRoleExists) {
            $roleAbbreviation[] = 'CCC';
        }

        $ccRoleExists = in_array('Coverage Coordinator', $rolesName);
        if ($ccRoleExists) {
            $roleAbbreviation[] = 'CC';
        }

        $ocRoleExists = in_array('Orientation Coordinator', $rolesName);
        if ($ocRoleExists) {
            $roleAbbreviation[] = 'OC';
        }

        return implode(", ", $roleAbbreviation);
    }

    //pass ids in array
    public static function getUsersByRoles($ids){
        $users = [];
        if(!empty($ids)){
            $query = DB::table('users_has_roles')
            ->select('user_id', 'users.name')
            ->LeftJoin('users', 'users.id', '=', 'users_has_roles.user_id')
            ->whereIn('users_has_roles.role_id', $ids)
            ->where('users.status', '=', 1)
            ->groupBy('users_has_roles.user_id')
            ->get();
            if(count($query) > 0){
                foreach($query as $user){
                    $users[$user->user_id] = $user->name;
                }
            }
        }
        return $users;
    }

    public static function getPhyAndRefNamebyId($type, $id){
        if($type == 'phy'){
            $name = DB::table('physician_master')
            ->select(DB::raw("CONCAT(physician_master.first_name, ' ',
        IF(physician_master.middle_name != '',
            CONCAT(physician_master.middle_name, ' '),
            ''
        ),
        physician_master.last_name
    ) AS full_name"))
            ->where('physician_id', $id)->first();
            if(!empty($name)){
                return $name->full_name;
            }else{
                return '';
            }
        }else{
            return DB::table('referralsource_master')->where('source_id', $id)->value('name');
        }
    }

    public static function getPatientNameById($id){
        $query = DB::table('mssql_patients')
        ->select(DB::raw("CONCAT(Patient_FirstName, ' ', IFNULL(Patient_MiddleName, ''), ' ', Patient_LastName) AS full_name"))
        ->where('id', $id)
        ->first();
        return $query->full_name;
    }

    public static function checkPatientHasLobInCol($patientId)
    {
        $patientData = DB::table('mssql_patients')
            ->where('id', $patientId)
            ->where('isLatestAdmit', 1)
            ->first();

        if ($patientData->LOBID == '1023') {
            $intakeData = DB::table('intake')
                ->where('aoc_patient_id', $patientData->id)
                ->whereNull('LOBID')
                ->where('intake_completed', '<>', 1)
                ->exists();

            if ($intakeData) {
                return false;
            }
        }

        return true;
    }

    public static function getProviderNameById($data){
        if(!empty($data->aoc_provider_id)){
            $query = DB::table('mssql_providers')
            ->select('fullName')
            ->where('id', $data->aoc_provider_id)
            ->first();
            return $query->fullName;
        }else{
            $query = DB::table('candidates')
            ->select('fullName')
            ->where('id', $data->aoc_candidate_id)
            ->first();
            return $query->fullName;
        }

    }

    public static function getRecruterIdFromTrackerId($id){
        $query = DB::table('mssql_recruitertracker')
        ->select('user_id')
        ->where('id', $id)
        ->first();
        return $query->user_id;
    }

    public static function getListOfActiveClinican(){
        return DB::table('mssql_schedulemaster')
        ->join('intake', 'intake.intake_admission_visit', '=', 'mssql_schedulemaster.CGTaskID')
        ->join('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
        ->distinct()
        ->pluck('mssql_providers.fullName')
        ->map(fn($name) => [
            'value' => $name,
            'text'  => $name,
        ])
        ->values()
        ->all();
    }

    public static function getUserToAssignCcr($trackerId){
        $role_id = Roles::where('name', 'Clinical Quality Nurse')->value('id');
        $user_data =  self::getUsersByRoles([$role_id]);
        if(!empty($user_data) && is_array($user_data)){
            return  array_key_first($user_data);
        }
        $trackerDetail = MssqlRecruitertracker::findorfail($trackerId);
        if(!empty($trackerDetail->aoc_patient_id)){
            $patientDetail = DB::table('mssql_patients')->where('id',$trackerDetail->aoc_patient_id )->value('ccm_id');
            if(!empty($patientDetail)){
                return $patientDetail;
            }
        }
        return '';
    }

    public static function updateLeadData($id){
        $data = session('nth_lead_data_' . $id);
        DB::table('nth_inquiries')->where('id', $id)->update($data['nth_inquiries']);
        H::getHelper('ActivityNotes')->setActivityNotes($data['nots_data']);
        session()->forget('nth_lead_data_' . $id);
    }
}
