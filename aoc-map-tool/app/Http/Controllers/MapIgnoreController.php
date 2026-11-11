<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\BranchLocations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapIgnoreController extends Controller
{
    public function performGlobalSearch(Request $request){
        $req = $request->all();
        $data = [];
        $whereinit = [];
        $whereGlobal = [];
        $whereSpecific = [];
        $keySearch = [];
        $sqlParameters = [];
        $tableName = DB::table("map_configurations")->select("map_tablename")->where("map_id",$req["map_id"])->first();
        if(!empty($req['unique'])){
            foreach($req["unique"] as $key => $value) {
                if(!empty($value)) {
                    $keySearch[] = $key;
                    $whereSpecific[]="`$key` like :".$key."_unique ";
                    $sqlParameters[$key."_unique"] ='%'.$value.'%';
                }
            }
            if(!empty($whereSpecific)) {
                $whereinit[] = '('.implode(" and ",$whereSpecific).')';
            }
        }

        if(!empty($req['map-search'])) {
            $fieldsForTable = H::getTableFieldsForSelectedMap($tableName->map_tablename);
            foreach($fieldsForTable as $key=>$field) {
                if(!in_array($field->Field,$keySearch)) {
                    $whereGlobal[]="`$field->Field` like :".$field->Field."_map ";
                    $sqlParameters[$field->Field."_map"] ='%'.$req['map-search'].'%';
                }
            }
            $whereinit[] = '('.implode(" or ",$whereGlobal).')';
        }
     
        $formattedData = [];
        if (count($whereinit) > 0) {
            $data = DB::table($tableName->map_tablename)->select("*")->whereRaw(implode(" AND ", $whereinit),$sqlParameters)->limit(10)->get()->toArray();
        } else {
            $data = [];
        }
        $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
        
        foreach($data as $key=>$values) {
            foreach($values as $k=>$v){
                if(!in_array($k,$excluded)) {
                    $formattedData[$key][$k] = $v;
                }
            }
            
        }

        return json_encode($formattedData);
    }

    public function getIgnoreGrid()
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('map_ignore');
        return view('map.mapignoregrid', ['dataTable'=>$dataTable]);
    }

    public function ajaxIgnoreData(Request $request)
    {
        return $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'map_ignore', 0);
    }

    public function getIgnoreForm(Request $request)
    {
        try {
            
            $id = $request->input("id");
            if($id) {
                $data=  DB::table("map_ignore")->select("*")->where("id",$id)->first();
                return view('map.mapignoreform', ['data'=>$data]);
            } else {
                return view('map.mapignoreform');
            }
        } catch (\Exception $e) {
            return redirect(route('map.ignoregrid'));
        }
    }

   
    public function saveIgnore(Request $request)
    {
        $requestData = $request->all();
        $data = [];
        $sqlParameters = [];
        $id = isset($requestData['id']) ? $requestData['id'] : "";

        $data["map_id"] = isset($requestData['map_id']) ? $requestData['map_id'] : '';
        $data["unique_identifier"] = isset($requestData['unique']) ? json_encode($requestData['unique']): '';
        
        $tableName = DB::table("map_configurations")->select("map_tablename")->where("map_id",$requestData["map_id"])->first();
        $where = [];
        foreach($requestData['unique'] as $key => $val) {
            if(!empty($val)) {
                $where[] = "`".$key."` = :$key";
                $sqlParameters[$key]=$val;
            }
        }
        
        if (count($where) > 0) {
            $dataRecord = DB::table($tableName->map_tablename)->select("*")->whereRaw(implode(" AND ", $where),$sqlParameters)->get()->toArray();
            $saveRecords = [];
            foreach($dataRecord as $key => $rec) {
                DB::table($tableName->map_tablename)->where("id",$rec->id)->update(["is_ignored"=>1]);
                foreach($rec as $field => $dataV) {
                    $saveRecords[$key][$field] = $dataV;
                }
            }
            $data["value"] = !empty($saveRecords) ? json_encode($saveRecords) : ''; 
            if(!empty($id)) {
                DB::table("map_ignore")->where('id',$id)->update($data);
            } else {
                DB::table("map_ignore")->insert($data);
            }
        }
        return redirect(route('map.ignoregrid'));
    }

    public function deleteIgnore(Request $request)
    {
        try {
            $requestData = $request->all();
            $id = $requestData['id'];
            DB::table("map_ignore")->where('id',$id)->delete();
        } catch (\Exception $e) {
            H::ExcelLog("Error : " . $e->getMessage(),'deleteIgnore.log');
            exit;
        }
        return redirect(route('map.ignoregrid'));
    }
}
