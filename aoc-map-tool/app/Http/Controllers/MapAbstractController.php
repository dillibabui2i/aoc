<?php

namespace App\Http\Controllers;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\Geocoding;
use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\Map\MapTableRequest;
use App\Location;
use App\Map;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use App\Helpers\PriorityPayersConfigHelper;

class MapAbstractController extends Controller
{
    private const RAW_TABLE = 'raw_coverage_needs';
    private const DONE_TILL_NEXT_UPLOAD = 'donetillnextupload';
    private const SYNC_TYPE = 'rawCoverageNeeds';

    public function index(Request $request)
    {
        $id = $request->input('id');
        $isprotected = Map::where('map_id', $id)->value('is_protected');
        if($isprotected == 1){
            return redirect()->back();
        }
        //H::ExcelLog(auth()->user()->name . ' in MapAbstractController.index: ' . print_r(Session::get('selectedmap'),true),'mapQuery.log');
        if ($id) {
            H::ExcelLog(auth()->user()->name . ' in MapAbstractController.index: ' . ' save selectedmap = ' . $id,'mapQuery.log');
            $request->session()->put('selectedmap', $id);
        }else{
            H::ExcelLog(auth()->user()->name . ' in MapAbstractController.index: ' . ' id not set','mapQuery.log');
            //H::ExcelLog(auth()->user()->name . ' ' . print_r($request->all(),true),'mapQuery.log');
        }

        $mapInfo = H::getSelectedMapInfo();
        if (empty($mapInfo)) {
            return redirect()->back()->with('error', 'Please Choose Map to import data.');
        }

        return view('map.import');
    }

    public function downloadData(Request $request)
    {
        $reqD = $request->all();
        $reqD['type'] = 'download';
        $reqD['search']['value'] = $reqD['dtsearch'];
        $fileName = 'downloaddata.csv';
        $data = H::getMapData($reqD);

        $mapInfo = H::getSelectedMapInfo(false,$reqD['layer_id'] ?? null);

        if ($mapInfo[0]->map_type == 'PHI') {
            $loggedInUser = H::getLoggedInUser();
            $phiData['user']['id'] = $loggedInUser['id'];
            $phiData['user']['name'] = $loggedInUser['name'];
            $phiData['user']['ip'] = request()->ip();
            $phiData['map_name'] = $mapInfo[0]->map_name;

            foreach ($data as $key =>$value) {
                $phiData['results'][] = $value->id;
            }
            H::ExcelLog(json_encode($phiData), 'mapDownload.log');
        }

        $jsonFields = $mapInfo[0]->fields_datatable;
        $dataTableFields = json_decode($jsonFields);

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [];

        foreach ($dataTableFields->table_heading as $rec) {
            $columns[] = $rec;
        }

        foreach ($dataTableFields->fields as $rec) {
            $columns[] = $rec->title;
        }

        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields->datatable as $fields) {
                    $csvData[] = $tableData->$fields;
                    $cntr++;
                }
                foreach ($dataTableFields->fields as $rec) {
                    $csvData[] = $tableData->{$rec->name};
                    $cntr++;
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function retrieveConfigurations()
    {
        return view('map.configurations');
    }

    public function saveConfigurations(Request $request, PriorityPayersConfigHelper $payerHelper, bool $isLayer = false)
    {
        $data = $request->all();

        if (isset($data['payer_priority_name'])) {
            $payerHelper->savePayerPriorityConfig($data['payer_priority_name']);
        }

        $mapInfo = H::getSelectedMapInfo();

        foreach ($request->input('layers', []) as $index => $layer) {
            $file = $request->file("layers.$index.file");

            $payload = [
                'map_id'    => $layer['map_id'] ?? null,
                'map_name'    => $layer['map_name'] ?? null,
                'pin_color'   => $layer['pin_color'] ?? null,
                'pin_icon'    => $layer['pin_shape'] ?? null,
                'parent_id'   => $mapInfo[0]->map_id ?? null,
                'card_fields' => !empty($layer['card_fields']) ? ['fields' => $layer['card_fields'], 'title' => $layer['card_fields']] : null,
                'latlong_fields' => $layer['latlong_fields'] ?? null,
                'isAppend' => $layer['isAppend'] ?? null,
            ];

            $files = [];
            if ($file) {
                $files['map_csv'] = $file;
            }

            $layerRequest = Request::create(
                $request->getUri(),
                $request->getMethod(),
                $payload,
                $request->cookies->all(),
                $files,
                $request->server->all()
            );

            $layerRequest->headers->replace($request->headers->all());
            $layerRequest->setUserResolver(fn () => $request->user());
            $layerRequest->setRouteResolver(fn () => $request->route());

            if(!isset($layer['map_id'])){
                $createdLayer = $this->savemap($layerRequest, true);
                $layerRequest->merge(['map_id' => json_encode($createdLayer['map_id'])]);
            }

            $this->saveConfigurations($layerRequest, true);

            if ($file) {
                $currentLayer = $createdLayer ?? Map::find($layer['map_id']);
                $this->importdata($layerRequest, $currentLayer);
            }
        }

        $mapData = [];
        try {
            foreach ($data['latlong_fields'] as $key => $value) {
                if (empty($value)) {
                    unset($data['latlong_fields'][$key]);
                }
            }
            $mapData['map_name'] = $data['map_name'];
            $mapData['latlong_fields'] = json_encode($data['latlong_fields']);
            $mapData['fields_on_map'] = H::prepareFieldsOnMap($data);

            if($isLayer){
                $mapData['map_name']    = $data['map_name'];
                $mapData['pin_color']   = $data['pin_color'];
                $mapData['pin_icon']    = $data['pin_icon'];
                $mapData['parent_id']   = $data['parent_id'];
                $tableId = $data['map_id'];
            } else {
                if (!isset($data['map_id']) || (int) $data['map_id'] !== (int) $mapInfo[0]->map_id) {
                    return redirect(route('map.configurations'))
                        ->withErrors('Map selection has changed, please reload and try again.');
                }
                $mapData['map_category'] = $data['map_category'];
                $mapData['map_type'] = $data['map_type'];
                $mapData['map_preferences'] = $data['default_view'];
                $mapData['map_data_preference'] = $data['map_data_preference'];
                $mapData['map_boolean_field'] = isset($data['map_boolean_field']) ? json_encode($data['map_boolean_field']) : '';
                $mapData['map_select_preference'] = isset($data['map_select_preference']) ? $data['map_select_preference'] : '';
                $mapData['map_search_radius'] = $data['map_search_radius'];
                $mapData['map_filters'] = H::prepareFieldsMapFilters($data);
                $mapData['fields_datatable'] = H::prepareFieldsDatatable($data);
                $mapData['map_default_filter'] = H::prepareDefaultFilters($data);
                $mapData['enable_address_filter'] = isset($data['enable_address_filter']) ? 1 : 0;
                $mapData['layer_ring_radius'] = $data['layer_ring_radius'];
                $mapData['show_rings_by_default'] = isset($data['show_rings_by_default']) ? 1 : 0;
                $mapData['ring_layer_id'] = $data['ring_layer_id'];
                if (LD::checkFeatureFlag('unique-identifier-map-config')) {
                    $mapData['unique_identifier_col'] = isset($data['unique_identifier_col']) ? json_encode($data['unique_identifier_col']) : '';
                }
                $tableId = $mapInfo[0]->map_id;
            }


            DB::table('map_configurations')->where('map_id', $tableId)->update($mapData);

            return redirect(route('map.configurations'))->withSuccess('Map configuration Updated Successfully');
        } catch (\Exception $e) {
            return redirect(route('map.configurations'))->withErrors('There is an error updating map configuration, please try again later. Error Description : ' . $e->getMessage());
        }
    }

    public function showMap(User $user)
    {
        try {
            $selectedMap = Map::query()
                ->where('map_id', Session::get('selectedmap'))
                ->first();

            return view('map.map', [
                'coverage_coordinators' => $user->getCoverageCoordinators(['name']),
                'uploaded_at' => getFormatedDateTime($selectedMap->last_uploaded_at)
            ]);
        } catch (\Exception $e) {
            return redirect(route('login'));
        }
    }

    public function resetMapFilters(Request $request)
    {
        $mapInfo = H::getSelectedMapInfo();
        $request->session()->forget($mapInfo[0]->map_tablename);
    }

    public function chooseMap(Request $request)
    {
        $requestD = $request->all();
        //H::ExcelLog(auth()->user()->name . ' in MapAbstractController.chooseMap: ' . print_r($requestD,true),'mapQuery.log');

        $mapInfo = H::getSelectedMapInfo();
        //H::ExcelLog(auth()->user()->name . ' in MapAbstractController.chooseMap mapInfo: ' . print_r($mapInfo,true),'mapQuery.log');

        if (!empty($mapInfo)) {
            H::ExcelLog(auth()->user()->name . ' in MapAbstractController.chooseMap: mapInfo not empty','mapQuery.log');

            $request->session()->forget($mapInfo[0]->map_tablename);
        }
        H::ExcelLog(auth()->user()->name . ' in MapAbstractController.chooseMap: put selectedmap = ' . $request['map-selector'],'mapQuery.log');

        $request->session()->put('selectedmap', $request['map-selector']);
        if (!empty($request['map-selector'])) {
            H::ExcelLog(auth()->user()->name . ' in MapAbstractController.chooseMap: map-selector not empty','mapQuery.log');

            $newMapInfo = H::getSelectedMapInfo();
            if (LD::checkFeatureFlag('map-selection-setting')) {
                if ($newMapInfo[0]->map_select_preference == '0') {
                    return redirect()->route('map.map');
                } else {
                    return redirect()->route('map.table');
                }
            } else {
                if ($newMapInfo[0]->map_preferences == '0') {
                    return redirect()->route('map.map');
                } else {
                    return redirect()->route('map.table');
                }
            }
        } else {
            return redirect()->route('map.mapIntro');
        }
    }

    public function showTable(MapTableRequest $request)
    {
        try {
            if ($request->ajax()) {
                $dRequest = $request->all();

                return H::getMapData($dRequest);
            }

            return view('map.table');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $e->getMessage();
        }
    }

    public function getDistinctValue(Request $request)
    {
        $arr = [];
        $field = $request->input('groupfield');
        foreach (H::getDistinctValue($field) as $key=> $va) {
            $arr[] = $va->$field;
        }

        return $arr;
    }

    public function getjson(Request $request)
    {
        $data['latlong_fields'][0] = 'Client_Address';
        $data['latlong_fields'][1] = 'Client_County';
        $data['latlong_fields'][2] = 'Client_City';
        $data['latlong_fields'][3] = 'Client_ZipCode';
        $data['latlong_fields'][4] = 'State';

        return json_encode($data);
    }

    public function setMapColors(Request $request)
    {
        $mapInfo = H::getSelectedMapInfo();
        Session::put($mapInfo[0]->map_tablename . '_colors', $request->all());
        $res = Session::get($mapInfo[0]->map_tablename . '_colors');

        return response()->json($res);
    }

    public function getLocationDataOfMap(Request $request)
    {
        H::ExcelLog(auth()->user()->name . ' in MapAbstractController.getLocationDataOfMap','mapQuery.log');
        $all = $request->all();
        $mapInfo = H::getSelectedMapInfo();

        $facilityId = $all['facilityID'];
        $layerId = $request->input('layer_id');

        if (!empty($layerId)) {
            $mapInfo = DB::table('map_configurations')->where('map_id', $layerId)->get();
        } else {
            $mapInfo = H::getSelectedMapInfo();
        }

        $map1Data = null;

        $map1Data = DB::table($mapInfo[0]->map_tablename)->where('id', $facilityId)->where('lat', '!=', '')->where('lng', '!=', '')->get();

        if ($mapInfo[0]->map_type == 'PHI') {
            $loggedInUser = H::getLoggedInUser();
            $phiData['user']['id'] = $loggedInUser['id'];
            $phiData['user']['name'] = $loggedInUser['name'];
            $phiData['user']['ip'] = request()->ip();
            $phiData['map_name'] = $mapInfo[0]->map_name;

            //foreach($modified as $key =>$value) {
            $phiData['results'][] = $facilityId;
            //}
            H::ExcelLog(json_encode($phiData), 'mapDetails.log');
        }

        return view('map.popup', ['mapData'=>$map1Data[0], 'mapInfo' => $mapInfo]);
    }

    public function getMainLocations(Request $request, $type = 'map')
    {
        H::ExcelLog(auth()->user()->name . ' in MapAbstractController.getMainLocations','mapQuery.log');
        error_reporting(0);

        $reqData = $request->all();
        if (isset($reqData['layer_id']) && !empty($reqData['layer_id'])) {
            $mapInfo = DB::table('map_configurations')->where('map_id', $reqData['layer_id'])->get();
        } else {
            $mapInfo = H::getSelectedMapInfo();
        }

        H::ExcelLog(auth()->user()->name,'mapQuery.log');
        //H::ExcelLog(print_r($reqData,true),'mapQuery.log');
//        $mapInfo = H::getSelectedMapInfo();
        // $colors = Session::get($mapInfo[0]->map_tablename.'_colors');
        if (isset($reqData['mode']) && $reqData == 'search') {
            Session::put(H::getSelectedMapInfo()[0]->map_tablename, []);
        }

        if ($mapInfo[0]->map_type == 'PHI') {
            $ssValue = Session::get($mapInfo[0]->map_tablename);
            if (isset($ssValue['lastmapsearch']['location']) && !empty($ssValue['lastmapsearch']['location'])) {
                $loggedInUser = H::getLoggedInUser();
                $phiData['user']['id'] = $loggedInUser['id'];
                $phiData['user']['name'] = $loggedInUser['name'];
                $phiData['user']['ip'] = request()->ip();
                $phiData['map_name'] = $mapInfo[0]->map_name;
                $phiData['search']['location'] = $ssValue['lastmapsearch']['location'];
                $phiData['search']['radius'] = $ssValue['lastmapsearch']['radius'];
                H::ExcelLog("Request : " . json_encode($phiData), 'mapSearch.log');
            }
        }

        //if location is not found in location table - get it from google
        $useGoogleApiMapArray = ['maps_nth','maps_clients','maps_coverageneeds','maps_therapy','maps_branch','maps_covclin'];
        if ($reqData['locationString'] == '' && $reqData['location'] != '') {

            //$locationData = $this->getLatLong($reqData['location'],2);
            $googleSearch = 0;
            $tomtomSearch = 1;
            $locationAddress = [];
            //if(in_array($mapInfo[0]->map_tablename,$useGoogleApiMapArray)) {
                H::ExcelLog(auth()->user()->name . ' in array','mapQuery.log');
                $locationData = Geocoding::getGeoGoogle($reqData['location']);
            //}else {
              //  H::ExcelLog(auth()->user()->name . ' get from tomtom','mapQuery.log');
            //    $locationData = Geocoding::getGeoTomTom($reqData['location']);
           // }
            /*if(!empty($locationData) && $googleSearch == 1){
                $addressComponent = $locationData['address_components'];
                $address1='';
                if(!empty($addressComponent)){
                    $locationAddress['state']  =$locationAddress['zipcode']  = $locationAddress['city']  = $locationAddress['address1'] ='';

                    foreach($addressComponent as $addressComponentData){

                        if($addressComponentData['types'][0] == 'street_number'){
                            $address1  = $address1.' '.$addressComponentData['long_name'];
                        }
                        if($addressComponentData['types'][0] == 'route'){
                            $address1  = $address1.' '.$addressComponentData['long_name'];
                        }
                        $locationAddress['address1'] = $address1;
                        if($addressComponentData['types'][0] == 'locality'){
                            $locationAddress['city']  =   $addressComponentData['long_name'];
                        }

                        if($addressComponentData['types'][0] == 'administrative_area_level_1'){
                            $locationAddress['state']  = $addressComponentData['long_name'];
                        }
                        if($addressComponentData['types'][0] == 'postal_code'){
                            $locationAddress['zipcode']  = $addressComponentData['long_name'];
                        }
                    }

                }

                $formatted_address = $locationData['formatted_address'];

                $addressComponents = explode(",",$formatted_address);
                $locationAddress['country'] = trim($addressComponents[count($addressComponents)-1]);
                $tempZipcode = $locationAddress["zipcode"];
                unset($locationAddress["zipcode"]);
                $locationAddress["zipcode"] = $tempZipcode;
                $geometry = $locationData['geometry'];

                //if address exist in location db
                $latlong = Location::getLatLngForAddress($locationAddress);
                if(empty($latlong)){
                    $locationAddress['lat'] = $geometry['location']['lat'];
                    $locationAddress['lng'] = $geometry['location']['lng'];
                    Location::saveAddressData($locationAddress);
                }else{
                    $locationAddress['lat'] = $latlong['lat'];
                    $locationAddress['lng'] = $latlong['lng'];
                }
                $reqData['formatedString'] = $locationAddress['address1'].', '.$locationAddress['city'].', '.$locationAddress['state'].', '.$locationAddress['country'].' '.$locationAddress['zipcode'];
                $reqData['locationString'] = $locationAddress['state'].'|'.$locationAddress['country'].'|'.$locationAddress['lat'].'|'.$locationAddress['lng'];

                unset($locationAddress['address1']);
                unset($locationAddress['city']);
                unset($locationAddress['zipcode']);
                $reqData = array_merge($reqData,$locationAddress);

            }*/
            //if($tomtomSearch == 1){
            if (!empty($locationData)) {
                $reqData['lat'] = $locationData['lat'];
                $reqData['lng'] = $locationData['lng'];
            } else {
                return response()->json(['success'=>'0', 'message'=>'Bad Address.']);
            }
            //}
            foreach ($reqData as $key => $value) {
                if (empty($value)) {
                    unset($reqData[$key]);
                }
            }
        } else {
            if (isset($reqData['locationString']) && $reqData['locationString'] != '') {
                $ex = explode('|', $reqData['locationString']);
                $reqData['state'] = $ex[0];
                $reqData['country'] = $ex[1];
                $reqData['lat'] = $ex[2];
                $reqData['lng'] = $ex[3];
            }

            foreach ($reqData as $key => $value) {
                if (empty($value)) {
                    unset($reqData[$key]);
                }
            }
        }

        $reqDataSession['lastmapsearch'] = $reqData;

        $where = '1=1 ';
        if (Session::get($mapInfo[0]->map_tablename) == '') {
            Session::put($mapInfo[0]->map_tablename, []);
        }
        if (count(Session::get($mapInfo[0]->map_tablename)) > 0 && count($reqDataSession['lastmapsearch']) > 0) {
            Session::put($mapInfo[0]->map_tablename, $reqDataSession);
            $search = $reqDataSession;
        } elseif (count(Session::get($mapInfo[0]->map_tablename)) <= 0 && count($reqDataSession['lastmapsearch']) > 0) {
            Session::put($mapInfo[0]->map_tablename, $reqDataSession);
            $search = $reqDataSession;
        } elseif (count(Session::get($mapInfo[0]->map_tablename)) > 0 && count($reqDataSession['lastmapsearch']) <= 0) {
            $search = Session::get($mapInfo[0]->map_tablename);
        } else {
            $search = [];
        }
        if (isset($search['lastmapsearch'])) {
            $search = $search['lastmapsearch'];
        }
        // var_dump($search);
        if (isset($reqData['ismappage']) && $reqData['ismappage'] == 1) {
            return '';
        }

        if (isset($reqData['isbtnclicked']) && $reqData['isbtnclicked'] == 1) {
            $newMapInfo = $mapInfo;
            if ($newMapInfo[0]->map_preferences == '1') {
                return '';
            }
        }

        $res = ['id'=>'Aochippa.map.data', 'type'=>null];
        $features = [];
        $searchData = [];
        $select = ' ';
        $having = ' 1 = 1';
        $orderBy = ' ';
        //var_dump($search);
        if (count($search)) {
            foreach ($search as $k =>$val) {
                if ($k != 'isbtnclicked' && $k != 'ismappage' && $k != 'search_text' && $k != 'colors' && $k != 'grouping-field' && $k != 'enable_grouping' && $k != 'radius' && $k != 'lat' && $k != 'lng' && $k != 'state' && $k != 'locationString' && $k != 'location' && $k != 'formatedString' && $k != 'country' && $k != 'mode' && $k != 'already_search') {
                    $fieldSearch = [];
                    $queryStr = '';
                    $fieldExtract = H::decryptFieldName($k);
                    $k = $fieldExtract[0];
                    $imp = $fieldExtract[1];
                    $dummyColumn = H::addDummyColumn($k, $val, $imp);
                    $select .= $dummyColumn;
                    //echo $k.'--'.$imp;
                    $specialString = H::isSpecialFilter($k, $val, $imp);

                    if (empty(trim($specialString))) {
                        //echo "here";
                        foreach ($val as $kv => $fieldVal) {
                            $fieldSearch[] = ' `' . $k . '` =  :'.$k.'_cusotm'.$cnt;
                            H::$sqlParameters[$k.'_cusotm'.$cnt] =  $fieldVal;
                            $cnt++;
                        }
                        if (is_array($fieldSearch) && !empty($fieldSearch)) {
                            $queryStr = implode(' OR ', $fieldSearch);
                            //$where.=$queryStr;
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

                if ($k == 'search_text' && !empty($val)) {
                    $where .= H::prepareGlobalSearchQuery($val);
                }
            }

            if (isset($search['enable_grouping']) && $search['enable_grouping']) {
                $groupingField = $search['grouping-field'];
                $searchData = $search['colors'][$groupingField];
                $firstArrayElement = array_key_first($searchData);
                if ($firstArrayElement == '-1') {
                    $searchData = [];
                    $distinctData = H::getDistinctValue($groupingField);
                    foreach ($distinctData as $key => $value) {
                        $searchData[] = $value->{$groupingField};
                    }
                }

                $groupArray = [];
                foreach ($searchData as $key => $v) {
                    $groupArray[] = ' (`' . $groupingField . '` = "' . $v . '") ';
                }
                if (!empty($groupArray)) {
                    $where .= ' AND ( ' . implode(' OR ', $groupArray) . ' )';
                }
            }

            if (isset($search['radius']) && $search['radius'] != '') {
                if ($search['lat'] != '' && $search['lng'] != '') {
                        $select .= sprintf(", (3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos( radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat)))) AS distance",$search['lat'],$search['lng'],$search['lat']);
                        $where .= 'AND lat <> "" AND lng <> "" ';
                        $having .= sprintf(' and distance <= ' . ('%f+0.5'),$search['radius']);
                        $orderBy = ' ORDER BY distance';
                } else {
                    $res = ['err'=>'Latitude and longitude not defined', 'status'=>false];
                }
            } elseif ($search['radius'] == '' && !empty($search['state'])) {

                $where .= ' AND lat <> "" AND lng <> "" AND `State` = :State';
                H::$sqlParameters["State"] = $search['state'];
            } else {
                $where .= ' AND lat <> "" AND lng <> ""';
            }
        } else {
            $where .= ' AND lat <> "" AND lng <> "" ';
        }

        if (isset($mapInfo[0]->map_default_filter)) {
            $defaultFilters = json_decode($mapInfo[0]->map_default_filter, true);

            if (count($defaultFilters)) {
                $where .= ' AND '.$defaultFilters[0]['field'].' '.$defaultFilters[0]['operator'].' :'.$defaultFilters[0]['field'].'_default';
                H::$sqlParameters[$defaultFilters[0]['field'].'_default'] = $defaultFilters[0]['value'];
            }
        }
            $where .= ' AND ifnull(is_ignored,0) != 1';

        //echo "select * ".$select." from `".$mapInfo[0]->map_tablename."` where ".$where. " having ".$having.$orderBy; exit;
        $map1Data = DB::select('select * ' . $select . ' from `' . $mapInfo[0]->map_tablename . '` where ' . $where . ' having ' . $having . $orderBy,H::$sqlParameters);
        H::ExcelLog("User: " . auth()->user()->name . " : " . 'select * ' . $select . ' from `' . $mapInfo[0]->map_tablename . '` where ' . $where . ' having ' . $having . $orderBy, 'mapQuery.log');
        if ($type == 'data') {
            return $map1Data;
        }
        //	var_dump($colors);
        foreach ($map1Data as $key => $value) {
            $index = -1;
            $cls = '';
            $iconUrl = '/resources/images/marker-icon.png';

            if (isset($search['enable_grouping']) && $search['enable_grouping']) {
                $groupingField = $search['grouping-field'];
                //$searchData = $search['colors'][$groupingField];

                if (in_array($value->{$groupingField}, $searchData)) {
                    $index = array_search($value->{$groupingField}, $searchData);
                    //$cls= 'pointer-color-'.$index;
                    $iconUrl = H::getResourceMarkerPath() . '/marker-icon-' . H::getMapMarkerImage($index) . '.png';
                }
            }
                $temp = [
                    'geometry'=>[
                        'type'=>'point',
                        'coordinates'=>[$value->lng, $value->lat],
                    ],
                    'id'=>$value->id,
                    'properties'=>[
                        'id'=>$value->id,
                        'pin_icon' => $mapInfo[0]->pin_icon ?? null,
                        'pin_color' => $mapInfo[0]->pin_color ?? null,
                        'icon'=>[
                            'iconUrl'=>$iconUrl,
                            'className'=> 'dot ' . $cls,
                            'iconSize'=>null,
                            'iconAnchor'=>[17, 25],
                            'popupAnchor'=>[0, -25],
                        ],
                    ],
                    'type'=>'Feature',
                ];
                array_push($features, $temp);
        }

        $ssValue = Session::get($mapInfo[0]->map_tablename);

        if (isset($ssValue['lastmapsearch']['lat']) && isset($ssValue['lastmapsearch']['lng'])) {
            $iconUrl = $iconUrl = H::getResourceMarkerPath() . '/marker-search.png';
            $temp2 = [
                'geometry'=>[
                   'type'=>'point',
                   'coordinates'=>[(string) $ssValue['lastmapsearch']['lng'], (string) $ssValue['lastmapsearch']['lat']],
                ],
                'id'=>-1,
                'properties'=>[
                   'id'=>-1,
                   'icon'=>[
                         'iconUrl'=>$iconUrl,
                         'className'=> 'dot ',
                         'iconSize'=>null,
                         'iconAnchor'=>[17, 25],
                         'popupAnchor'=>[0, -25],
                   ],
                ],
                'type'=>'Feature',
             ];
            //    echo "if";
            //  var_dump($features);
            array_push($features, $temp2);
        }
        // echo "out";
        //var_dump($ssValue);
            if(isset($ssValue['lastmapsearch']['enable_grouping'])){unset($ssValue['lastmapsearch']['enable_grouping']);}
            if(isset($ssValue['lastmapsearch']['grouping-field'])){unset($ssValue['lastmapsearch']['grouping-field']);}
            if(isset($ssValue['lastmapsearch']['colors'])){unset($ssValue['lastmapsearch']['colors']);}
            if(isset($ssValue['lastmapsearch']['search_text'])){unset($ssValue['lastmapsearch']['search_text']);}

        //  var_dump($ssValue);
        //Session::put($mapInfo[0]->map_tablename,[]);

        Session::put($mapInfo[0]->map_tablename, $ssValue);

        //var_dump($ssValue);

        if ($mapInfo[0]->map_type == 'PHI') {
            if (isset($ssValue['lastmapsearch']['location']) && !empty($ssValue['lastmapsearch']['location'])) {
                $phiData1 = [];
                foreach ($features as $key =>$value) {
                    $phiData1['results'][] = $value['id'];
                }
                H::ExcelLog("Result: " . json_encode($phiData1), 'mapSearch.log');
            }
        }
        // var_dump(Session::get($mapInfo[0]->map_tablename));
        $res['features'] = $features;
        //var_dump($res);
        // echo json_encode($res);exit;
        return response()->json($res);
    }

    public function dbFieldMapping($csvfield)
    {
        $csvfield = str_replace(' ', '_', $csvfield);
        //$csvfield = str_replace('#', '', $csvfield);

        return $csvfield;
    }

    public function dbFieldMappingForMap1($csvfield)
    {
        $data = ['Client'=>'Client', 'Schedule'=>'Schedule', 'ScheduleText'=>'ScheduleText', 'ScheduleFreeText'=>'ScheduleFreeText', 'Sum of hoursonNTH'=>'SumofhoursonNTH', 'Sum of TotalauthorizedPDNhours'=>'SumofTotalauthorizedPDNhours', 'Sum of TotalauthorizedRespitehours'=>'SumofTotalauthorizedRespitehours', 'Sum of TotalauthorizedCNAhours'=>'SumofTotalauthorizedCNAhours', 'Sum of Age'=>'SumofAge', 'Gender'=>'Gender', 'Weight'=>'Weight', 'Biography'=>'Biography', 'TheSale'=>'TheSale', 'AttendingSchool'=>'AttendingSchool', 'Language'=>'Language', 'NurseLanguage'=>'NurseLanguage', 'PetsinHome'=>'PetsinHome', 'PetsNurseCannotOwn'=>'PetsNurseCannotOwn', 'Liftingrequirements'=>'Liftingrequirements', 'SpecializedclientBIT'=>'SpecializedclientBIT', 'Skills'=>'Skills', 'Serviceneeded'=>'Serviceneeded', 'ClinicianGender'=>'ClinicianGender', 'Cliniciandiscipline'=>'Cliniciandiscipline', 'Payer'=>'Payer', 'RespitePayer'=>'RespitePayer', 'IntakeStatus'=>'IntakeStatus', 'IntakeDate'=>'IntakeDate', 'SOCDate'=>'SOCDate', 'Address'=>'Address', 'City'=>'City', 'Zipcode'=>'Zipcode', 'State'=>'State', 'Location'=>'Location', 'Phone'=>'Phone', 'CreatedOn'=>'CreatedOn', 'CreatedReason'=>'CreatedReason', 'MeetingNurses'=>'MeetingNurses', 'WorkForceDevProj'=>'WorkForceDevProj', 'NTHNotes'=>'NTHNotes', 'Covid19VaccineRequired'=>'Covid19VaccineRequired', 'MediaConsent'=>'MediaConsent', 'NonSmokingNurses'=>'NonSmokingNurses', 'SmokersLiveinHome'=>'SmokersLiveinHome',
    ];

        return isset($data[$csvfield]) ? $data[$csvfield] : '';
    }

    public function csvToArray($filename = '', $delimiter = ',')
    {
        $mapConfiguration = H::getSelectedMapInfo();
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, null, $delimiter)) !== false) {
                if (!$header) {
                    foreach ($row as $key => $value) {
                        if ($mapConfiguration[0]->map_tablename == 'map1') {
                            $header[] = $this->dbFieldMappingForMap1($value);
                        } else {
                            $header[] = $this->dbFieldMapping($value);
                        }
                    }
                } else {
                    $row = array_map('utf8_encode', $row);
                } //added
                $data[] = array_combine($header, $row);
            }

            fclose($handle);
        }

        return $data;
    }

    public function getLatLong($address, $usedfor = 1)
    {
        H::ExcelLog(auth()->user()->name . ' in MapAbstractController.getLatLong','mapQuery.log');
        //H::ExcelLog(auth()->user()->name . print_r($address,true),'mapQuery.log');
        $latLongArray = H::getCurl(env('GOOGLE_API_URL') . '?address=' . urlencode($address) . '&key=' . env('GOOGLE_API_KEY'));
        //H::ExcelLog(auth()->user()->name . ' results: ' . print_r($latLongArray,true),'mapQuery.log');
        if (isset($latLongArray['results'][0])) {

            //$userfor getMainLocations function to get full address component
            if ($usedfor == 2) {
                H::ExcelLog(auth()->user()->name . ' usedfor = 2','mapQuery.log');
                return $latLongArray['results'][0];
            }

            return $latLongArray['results'][0]['geometry']['location'];
        } else {
            return false;
        }
    }

    public function getGeoLatLong($address)
    {
        H::ExcelLog(auth()->user()->name . ' in MapAbstractController.getGeoLatLong','mapQuery.log');
        $latLongArray1 = 'https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address=' . urlencode($address) . '&benchmark=2020&format=json';
        $latLongArray = file_get_contents($latLongArray1);
        $latLongData = json_decode($latLongArray, true);
        $latLongFinalData = [];
        if (isset($latLongData['result']['addressMatches']) && !empty($latLongData['result']['addressMatches'])) {
            $latLongFinalData['lng'] = $latLongData['result']['addressMatches'][0]['coordinates']['x'];
            $latLongFinalData['lat'] = $latLongData['result']['addressMatches'][0]['coordinates']['y'];

            return $latLongFinalData;
        } else {
            return false;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importdata(Request $request, Map $map = null) : RedirectResponse
    {
        $uploadDir = public_path() . '/' . 'uploads';
        $isAppend = $request->input('isAppend') == 'on' ? true : false;
        $file = $request->file('map_csv');
        $destinationPath = $uploadDir;

        if (!$file) {
            return redirect()->back()->withErrors('CSV file is required.');
        }

        $file->move($destinationPath, $file->getClientOriginalName());

        $fileName = $uploadDir . '/' . $file->getClientOriginalName();
        $mapConfiguration = $map ? [$map] : H::getSelectedMapInfo();
        $mapDBFields = H::getTableFieldsForSelectedMap($mapConfiguration[0]->map_tablename);
        $mapTableName = $mapConfiguration[0]->map_tablename;
        $prepareDBFields = array_map(fn($val) => $val->Field, $mapDBFields);

        if (($handle = fopen($fileName, 'r')) !== false) {
            $headers = fgetcsv($handle);
            fclose($handle);
        } else {
            return redirect()->back()->withErrors('Unable to open CSV file.');
        }

        $normalized = array_map(fn($h) => H::getColumnName(trim($h)), $headers);

        $invalid = [];
        foreach ($normalized as $idx => $col) {
            if (!in_array($col, $prepareDBFields)) {
                $invalid[] = $headers[$idx];
            }
        }

        if (!empty($invalid)) {
            return redirect(route('map.import'))
                ->withErrors('CSV file fields are not matching with the database. Invalid: ' . implode(', ', $invalid));
        }

        $prepareDBFields = [];
        $fieldError = [];
        $ignoreArray = [];

        foreach ($mapDBFields as $key => $val) {
            $prepareDBFields[] = $val->Field;
        }

        if (!$isAppend) {
            DB::table($mapConfiguration[0]->map_tablename)->truncate();
           // Reset batch state for a new Coverage Needs upload.
            if ($mapTableName === self::RAW_TABLE) {
                H::getHelper('Sync')->update_last_updated(self::SYNC_TYPE, '', self::DONE_TILL_NEXT_UPLOAD);
            }
        }
            $ignoreValue = DB::table("map_ignore")->select("*")->where("map_id",$mapConfiguration[0]->map_id)->get()->toArray();
            foreach($ignoreValue as $v) {
                $ignoreArray[] = $v->unique_identifier;
            }
        $heading = [];
        $isFieldErr = false;

        $success = H::getChunkedCsvData($isFieldErr, $prepareDBFields, $heading, $mapConfiguration, $fileName, 5000, '',$ignoreArray, function ($chunk, &$handle, $iteration, &$queryValuePrefix, $tableName, &$heading, $prepareDBFields, &$isFieldErr,&$ignoreArray) {
            $chunk = $queryValuePrefix . $chunk;
            H::ExcelLog('here','zzMapImport.log');
            $lineArray = preg_split("/\r\n|\n|\r/", $chunk);
            $numberOfRecords = count($lineArray);
            H::ExcelLog('number of records: ' . $numberOfRecords,'zzMapImport.log');
            $dataArr = [];
            $latlongFields = json_decode($tableName[0]->latlong_fields);

            for ($i = 0; $i < $numberOfRecords - 1; $i++) {
                if (empty($heading)) {
                    $heading = array_map('trim', str_getcsv($lineArray[0]));
                    H::ExcelLog(print_r($heading,true),'zzMapImport.log');
                    $fieldError = [];
                    foreach ($heading as $key=>$value) {
                        $checkKey = H::getColumnName($value);
                        H::ExcelLog($checkKey,'zzMapImport.log');
                        if (!in_array($checkKey, $prepareDBFields)) {
                            H::ExcelLog('field error: ' . $key . ' -> ' . $value . ' : ' . $checkKey,'zzMapImport.log');
                            $fieldError[] = $value;
                        }
                    }

                    if (count($fieldError)) {
                        $isFieldErr = true;
                        if ($tableName[0]->map_tablename == 'map1') {
                            return redirect(route('map.import'))->withErrors('CSV file fields are not matching with the database. Please Check the CSV file.');
                        } else {
                            return redirect(route('map.import'))->withErrors('CSV file fields are not matching with the database. Please correct ' . count($fieldError) . '  fields (' . implode(',', $fieldError) . ')');
                        }
                    }
                    continue;
                }

                $colArray = str_getcsv($lineArray[$i]);
                H::ExcelLog(print_r($colArray,true),'zzMapImport.log');
                $temp = [];
                if (count($colArray) > 1) {
                    for ($j = 0; $j < count($colArray); $j++) {
                        $headingKey = H::getColumnName($heading[$j]);
                        $temp[$headingKey] = $colArray[$j];
                    }
                    H::ExcelLog(print_r($temp,true),'zzMapImport.log');
                        $temp["is_ignored"] = 0;
                        if(!empty($ignoreArray)) {
                            foreach($ignoreArray as $key=>$processData){
                                $encodedData = json_decode($processData,true);
                                $ignoreCount = 0;
                                foreach($encodedData as $fkey => $compareVal) {
                                    if(isset($temp[$fkey])) {
                                        if($compareVal==$temp[$fkey]){
                                            $ignoreCount++;
                                        }
                                    }
                                }
                                if(count($encodedData)==$ignoreCount){
                                    $temp["is_ignored"] = 1;
                                }
                            }
                        }
                    //check mobile to set is_ignored
                    if(LD::checkFeatureFlag("add_is_ignored_contacts_back_to_maps")) {
                        $selecgtedMapConfig = H::getSelectedMapInfo();
                        $mapMobileFieldArr =config('constant.map_mobile_field');
                        $mapMobileField =  $mapMobileFieldArr[$selecgtedMapConfig[0]->map_tablename]??null;
                        if(!empty($mapMobileField)) {
                            H::ExcelLog('Map Mobile field: '.$mapMobileField,'MapImportIsIgnored.log');
                            H::ExcelLog('Map '.$selecgtedMapConfig[0]->map_tablename,'MapImportIsIgnored.log');
                            foreach($temp as $tempkey=>$tempData){
                                if($tempkey == $mapMobileField) {
                                    $mobileNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($tempData);
                                    $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$mobileNumber)
                                    ->where('map_id',$selecgtedMapConfig[0]->map_id)->first();
                                    if(!empty($twilioErrorData)) {
                                        H::ExcelLog('Mobile number '.$mobileNumber.' found in error logs for map '.$selecgtedMapConfig[0]->map_tablename,
                                        'MapImportIsIgnored.log');
                                        $temp["is_ignored"] = 1;
                                    }
                                }
                            }
                        }
                    }
                    array_push($dataArr, $temp);
                }
            }
            H::ExcelLog('insert','zzMapImport.log');
            H::ExcelLog($tableName[0]->map_tablename . ' imported by user: ' . auth()->user()->name . ' and user-id: ' . auth()->user()->id, 'userimportmapdata.log');
            H::ExcelLog(print_r($dataArr,true),'zzMapImport.log');
            $lynnresult = DB::table($tableName[0]->map_tablename)->insert($dataArr);
            H::ExcelLog(print_r($lynnresult,true),'zzMapImport.log');
            $queryValuePrefix = $lineArray[$i];
        });

        event(new MapUpdatedEvent($mapTableName, Auth::user()->id));

        if ($success === -1) {
            return redirect(route('map.import'));
        } else {
            if (!$success) {
               return redirect(route('map.import'))->withErrors('There is an error processing your request, please try after some time');
            } else {
                return redirect(route('map.import'))->withSuccess('CSV File Imported to database successfully');
            }
        }
    }

    public function searchLocationData(Request $request)
    {
        //
        $latLongDetails = [];
        $latLongDetails = Location::searchLocationData($request)->toArray();
        //echo "11<pre>";print_r($latLongDetails);exit;
        if (count($latLongDetails) > 0) {
            $cnt = 0;
            foreach ($latLongDetails as $latLongData) {
                $displayStr = $latLongData['address1'];
                if ($latLongData['address2'] != '') {
                    $displayStr = ($displayStr != '') ? $displayStr . ', ' : $displayStr;
                    $displayStr = $displayStr . ' ' . $latLongData['address2'];
                }

                if ($latLongData['address3'] != '') {
                    $displayStr = ($displayStr != '') ? $displayStr . ', ' : $displayStr;
                    $displayStr = $displayStr . ' ' . $latLongData['address3'];
                }

                if ($latLongData['city'] != '') {
                    $displayStr = ($displayStr != '') ? $displayStr . ', ' : $displayStr;
                    $displayStr = $displayStr . ' ' . $latLongData['city'];
                }

                $displayStr = ($displayStr != '') ? $displayStr . ', ' : $displayStr;
                $displayStr = $displayStr . ' ' . $latLongData['state'];
                $displayStr = $displayStr . ', ' . $latLongData['country'];
                if ($latLongData['zipcode'] != '' && $latLongData['zipcode'] != 0) {
                    $displayStr = $displayStr . ' ' . $latLongData['zipcode'];
                }

                $latLongDetails[$cnt]['display_address'] = $displayStr;

                $latLongDetails[$cnt]['use_val'] = $latLongData['state'] . '|' . $latLongData['country'] . '|' . $latLongData['lat'] . '|' . $latLongData['lng'];
                $cnt++;
            }
        }
        $latLongDetailsJson = json_encode($latLongDetails);

        return $latLongDetailsJson;
    }

    public function listmap()
    {
        return view('map.grid');
    }

    public function getMapList(Request $request)
    {
      /*  $columns = [
            0 => 'map_name',
            1 => 'map_status',

        ]; */

        $columns = $request->input("columns");


        $mapList = Map::query()->with('lastUploadedByUser:id,name');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $mapList = $mapList->whereRaw("(`map_name` like concat('%',?,'%'))",[$request['search']['value']]);
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $mapList = $mapList->orderby($columns[intval($request['order'][0]['column'])]['data'], $request['order'][0]['dir']);
        }
        $total_records = $mapList->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $mapList = $mapList->offset($request['start'])->limit($request['length']);
        }

        $mapList = $mapList->get();

        $cnt = 0;
        foreach ($mapList as $mapData) {
            $isprotected = $mapData->is_protected;

            $btn = '';
            if($isprotected == 0){
                if (H::canPermissions('map.import')) {
                    $btn = '<a href="' . url('/map/import') . '?id=' . $mapData['map_id'] . '" class="edit"><i class="fa fa-upload" title="Import Map Data"></i></a>&nbsp;';
                }


                if (H::canPermissions('managemap.editmap')) {
                    $btn .= '<a href="' . url('/managemap/editmap/id', $mapData['map_id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
                }
                if (H::canPermissions('managemap.status')) {
                    if ($mapData['map_status'] == 1) {
                        $btn = $btn . '&nbsp;<a href="javascript:void(0)" class="disable-it"  data-href="' . url('/managemap/status/0/id', $mapData['map_id']) . '"  class="disable btn btn-info btn-sm"><i class="fa fa-ban" title="Disable"></i></a>';
                    } else {
                        $btn = $btn . '&nbsp;<a href="javascript:void(0)" class="enable-it"  data-href="' . url('/managemap/status/1/id', $mapData['map_id']) . '"  class="btn btn-info btn-sm"><i class="fa fa-circle-o" title="Enable"></i></a>';
                    }
                }
            }
            if (H::canPermissions(['managemap.editmap', 'managemap.status', 'map.import'])) {
                $mapList[$cnt]['action'] = $btn;
            }
            $mapList[$cnt]['geocode'] = H::getMapGeoCode($mapData['map_tablename']);
            $mapList[$cnt]['address_error'] = H::getMapAddressError($mapData['map_tablename']);
            $mapList[$cnt]['map_status'] = ($mapData['map_status'] == 1) ? 'Enabled' : 'Disabled';

            $mapList[$cnt]['last_uploaded_date'] = getFormatedDateTime($mapData->last_uploaded_at);
            $mapList[$cnt]['last_uploaded_by'] = $mapData->lastUploadedByUser?->name;
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $mapList,
        ];

        return $data;
    }

    public function add()
    {
        $mapData = H::getAllMaps();

        return view('map.form', ['mapData'=>$mapData]);
    }

    public function status($status, $id)
    {
        $mapConfiguration = H::getSelectedMapInfo();
        $isprotected = Map::where('map_id', $id)->value('is_protected');
        if($isprotected == 1){
            return redirect()->back();
        }
        if (!empty($mapConfiguration)) {
            if ($mapConfiguration[0]->map_id == $id) {
                Session::forget('selectedmap');
                Session::forget($mapConfiguration[0]->map_tablename);
            }
        }
        Map::where('map_id', $id)->update(['map_status' => $status]);
        if ($status == 1) {
            return redirect('/managemap')->with('success', 'Map enabled!');
        } else {
            return redirect('/managemap')->with('error', 'Map disabled!');
        }
    }

    public function savemap(Request $request, bool $returnMap = false) : RedirectResponse|Map
    {
        $isExistMap = $request->input('isExistMap') == 'on' ? true : false;
        if ($isExistMap) {
            $validator = Validator::make($request->all(), [
                'map_name' => 'required',
                'mapList' => 'required|not_in:0',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'map_name' => 'required',
                'map_csv' => 'required|file|mimes:csv,txt',
            ]);
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        if ($isExistMap) {
            $formData = $request->all();
            $selectMapId = isset($formData['mapList']) ? $formData['mapList'] : '';
            if ($selectMapId != '') {
                $mapData = H::objToArr(H::getMapById($selectMapId));
                unset($mapData['map_id']);
                $mapData['map_name'] = $formData['map_name'];
                $mapData['map_status'] = $formData['map_status'];
                $createdMap = Map::create($mapData);
            } else {
                return redirect(route('/managemap'))->withErrors('Selected map is not available');
            }
        } else {
            $mapModel = Map::orderBy('map_id', 'desc');

            //if (!$mapModel)
            $existingMap = $mapModel->first();
            if (!empty($existingMap)) {
                $lastMapId = $existingMap->map_id;
            } else {
                $lastMapId = 0;
            }

            $mapTableName = 'map' . ($lastMapId + 1);
            $path = $request->file('map_csv')->getRealPath();
            $data = array_map('str_getcsv', file($path));
            $columns = H::removeHiddenChars($data[0]);

            Schema::create($mapTableName, function (Blueprint $table) use ($columns) {
                $table->increments('id');
                foreach ($columns as $column) {
                    $table->text(str_replace(' ', '_', $column));
                }
                $table->string('lat');
                $table->string('lng');
                $table->tinyInteger('needs_geocoding')->default(1);
                $table->boolean('is_ignored')->default(0);
                $table->timestamps();
            });
            $request->request->add(['map_tablename' => $mapTableName]);

            $fdata['table_heading'][] = 'Distance (in Miles)';
            $fdata['datatable'][] = 'distance';

            $importantFields = [];

            foreach ($columns as $dataTable) {
                if (!empty($dataTable)) {
                    $fdata['table_heading'][] = $dataTable;
                    $fdata['datatable'][] = str_replace(' ', '_', $dataTable);

                    $name = str_replace(' ', '_', $dataTable);
                    $title = $dataTable;
                    $temp = [];
                    $temp = ['title'=>$title, 'name'=>$name];
                    $importantFields['fields'][] = $temp;
                }
            }

            $request->request->add(['enable_address_filter' => '1']);
            $request->request->add(['fields_on_map' => json_encode($importantFields)]);
            $request->request->add(['fields_datatable' => json_encode($fdata)]);

            $createdMap = Map::create($request->except(['_token', 'map_csv', 'mapList', 'card_fields', 'latlong_fields', 'isAppend']));
        }

        return $returnMap ? $createdMap :redirect('/managemap')->with('success', 'Map added!');
    }

    public function editmap(Request $request, $id)
    {
        $requestData = $request->input('action');
        $isprotected = Map::where('map_id', $id)->value('is_protected');
        if($isprotected == 1){
            return redirect()->back();
        }
        $request->session()->put('selectedmap', $id);
        if ($requestData == 'import') {
            return redirect(route('map.import'));
        }

        return redirect('/map/configurations');
    }

    public function mapIntro(Request $request)
    {
        return view('map.mapIntro');
    }

    public function removeTerritory(Request $request)
    {
        try {
            $id = $request->input('id');
            list($clicianData,$ccId)=H::getHelper("ConstantHelper")->autoAssignCoverageCoordinatorMap($id);

            DB::table('mssql_providers')->whereIn('cc_id', [$ccId])->update(['cc_id' => null]);
            DB::table('polygons')->where('id', $id)->delete();
            Session::flash('success', 'Territory has been removed successfuly.');

            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'mapPage.log');
            Session::flash('errors', 'Try again. ' . $e->getMessage());

            return response()->json(['status'=>false]);
        }
    }

    public function getRouteDistance(Request $request)
    {
        try {
            $selectedMap = H::getSelectedMap();
            $selectedMapInfo = H::getSelectedMapInfo();

            if ($selectedMap) {
                $searchDataSession = Session::get($selectedMapInfo[0]->map_tablename);
                if (isset($searchDataSession['lastmapsearch']['lat']) && isset($searchDataSession['lastmapsearch']['lng'])) {
                    $from = $searchDataSession['lastmapsearch']['lat'] . ',' . $searchDataSession['lastmapsearch']['lng'];
                    $to = $request->input('to');
                    $res = Geocoding::getRouteDistance($from, $to);
                    if ($res) {
                        return response()->json(['status'=>true, 'data'=>$res]);
                    } else {
                        return response()->json(['status'=>false]);
                    }
                } else {
                    return response()->json(['status'=>false]);
                }
            } else {
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'mapPage.log');
            Session::flash('errors', 'Try again. ' . $e->getMessage());

            return response()->json(['status'=>false]);
        }
    }

    public function removePNM(Request $request)
    {
        try {
            $id = $request->input('id');
            DB::table('maps_potential_nurse')->where('id', $id)->delete();
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            return response()->json(['status'=>false]);
        }
    }
}
