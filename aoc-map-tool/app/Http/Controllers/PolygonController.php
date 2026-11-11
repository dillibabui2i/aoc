<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PolygonController extends Controller
{
    public function save(Request $request)
    {
        $reqData = $request->all();
        $mapInfo = H::getSelectedMapInfo();

        if (isset($reqData['name']) && $reqData['name'] != '' && isset($reqData['coordinates']) && $reqData['coordinates'] != '') {
            $reqData['map_id'] = $mapInfo[0]->map_id;
            unset($reqData['_token']);
            if(LD::checkFeatureFlag('map-polygon-usertype-client-care-coordinator')) {
                $insertID = DB::table('polygons')->insertGetId($reqData);

                if($reqData['user_type']=='client_care_coordinator' && $mapInfo[0]->map_tablename=='maps_covclin') {
                    list($clicianData,$ccId)=H::getHelper("ConstantHelper")->autoAssignCoverageCoordinatorMap($insertID);
                    DB::table("mssql_providers")->whereIn("id",$clicianData)->update(["cc_id"=>$ccId]);
                }

            } else {
                DB::table('polygons')->insert($reqData);
            }




        } else {
            Session::flash('success', 'Your territory Name or cordinates not founds!');

            return response()->json(['status'=>false]);
        }
        Session::flash('success', 'Your territory has been saved!');

        return response()->json(['status'=>true]);
    }

    public function editTerritoryName(Request $request)
    {
        $reqData = $request->all();
        $mapInfo = H::getSelectedMapInfo();
        if (isset($reqData['name']) && $reqData['name'] != '' && isset($reqData['id']) && $reqData['id'] != '') {
            $reqData['map_id'] = $mapInfo[0]->map_id;
            unset($reqData['_token']);
            DB::table('polygons')->where('id', $reqData['id'])->where('map_id', $reqData['map_id'])->update(['name' => $reqData['name']]);

            if($reqData['user_type'] === 'client_care_coordinator' && $mapInfo[0]->map_tablename === 'maps_covclin') {
                list($clicianData,$ccId)=H::getHelper('ConstantHelper')->autoAssignCoverageCoordinatorMap($reqData['id']);
                DB::table('mssql_providers')->whereIn('id', $clicianData)->update(['cc_id' => $ccId]);
            }
        } else {
            Session::flash('success', 'Your territory Name or Id not founds!');

            return response()->json(['status'=>false]);
        }
        Session::flash('success', 'Your territory has been edited!');

        return response()->json(['status'=>true]);
    }

    public function checkNameExist(Request $request)
    {
        $reqData = $request->all();
        $mapInfo = H::getSelectedMapInfo();

        if (isset($reqData['name']) && $reqData['name'] != '') {
            $query = DB::table('polygons')
                ->where('name', '=', $reqData['name'])
                ->where('map_id', '=', $mapInfo[0]->map_id);

            if (!empty($reqData['id'])) {
                $query->where('id', '!=', $reqData['id']);
            }
            $data = $query->get()->toArray();
            if (empty($data)) {
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        } else {
            return response()->json(['status'=>false, 'msg'=>'Polygon name is required']);
        }
    }
}
