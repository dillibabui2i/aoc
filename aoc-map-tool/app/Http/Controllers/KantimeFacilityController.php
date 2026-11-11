<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\MssqlFacilities as MssqlFacilities;
use Illuminate\Support\Facades\Auth;

class KantimeFacilityController extends Controller
{
    //

    /**
     * Show the kantime facility list.
     */
    public function getKantimeFacility(Request $request)
    {
        $columns = [
            ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'FacilityName', 'label' => 'Kantime Facility', 'width' => '10%'],
            ['name' => 'State', 'label' => 'State', 'width' => '15%'],
            ['name' => 'region_name', 'label' => 'Region', 'width' => '15%'],
            ['name' => 'branch', 'label' => 'Associated Branch', 'width' => '10%'],
        ];

       
        if ($request->ajax()) {
            list($data, $totalData, $totalFiltered) = $this->getKantimeFacilityData($request, $columns, 'list');
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }
        return view('kantime.grid', ['columns' => $columns]);
    }


    private function getKantimeFacilityData($request, $columns, $requestFrom) {
   
        $filters = [];
        $filtersInput = $request->input('filters');
        if ($filtersInput) {
            foreach ($filtersInput as $key => $value) {
                if ($value['value'] != '') {
                    $filters[$value['name']][] = $value['value'];
                }
            }
        }
        $search = $request->input('search.value');
        $limit = $start = $order = $dir = '';
        if ($requestFrom == 'list') {
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');
        }

        $q = MssqlFacilities::select('mssql_facilities.*', 'regions.region_name', 'branch_locations.branch')
            ->leftJoin('regions', 'mssql_facilities.region', '=', 'regions.id')
            ->leftJoin('branch_locations', 'mssql_facilities.branchid', '=', 'branch_locations.id');

       
        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function ($q) use ($search) {
                    $q->where('mssql_facilities.Facilityname', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_facilities.State', 'LIKE', "%{$search}%")
                    ->orWhere('regions.region_name', 'LIKE', "%{$search}%")
                    ->orWhere('branch_locations.branch', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }

        if ($requestFrom == 'list') {
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        } else {
            $data = $q->get()->toArray();
        }

        return [$data, $totalData, $totalFiltered];
    }


    private function DataProcess($data)
    {
        
        return $data;
    }

    public function ajaxKantimeFacility(Request $request){

    }

    public function addKantimeFacility(Request $request){
        $facilityDetail = [];
        if(isset($request['facility_id'])) {
            $facilityDetail = MssqlFacilities::select("*")->where("id",$request['facility_id'])->first();
        } 
        $facilities =  MssqlFacilities::select("*")->get();
        $states = [];
        foreach($facilities as $facility) {
            if(!in_array($facility->State,$states)) {
                $states[] = $facility->State;
            }
        }

        $regions =  DB::table("regions")->select("*")->get();

        $branchLocations =  DB::table("branch_locations")->select("*")->get();

        return view('kantime.form',['facilityDetail'=>$facilityDetail,'facilities' => $facilities,'states'=>$states,'regions'=>$regions,'branchLocations'=>$branchLocations]);
    }

    public function saveKantimeFacility(Request $request){
        $req = $request->all();
        if(isset($req['id']) && !empty($req['id'])) {
            $data['region'] = $req['region'];
            $data['branchid'] = $req['branch'];
            $data['branch_coverage_booked_goal'] = $req['branch_coverage_booked_goal'];
            MssqlFacilities::where("id",$req['id'])->update($data);
            return redirect(route('view.kantimefacility'))->with('success', 'Kantime Facility Updated Successfully !!!');
        }   
        return redirect(route('view.kantimefacility'))->with('success', 'Kantime Facility not Updated');
    }
}
