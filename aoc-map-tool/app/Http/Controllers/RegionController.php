<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Region;
use Illuminate\Support\Facades\Validator;
use App\Helpers\H;

class RegionController extends Controller
{
    public function index(){
        return view('region.grid');
    }

    public function getReginList(Request $request) {
        $columns = [
            0 => 'region_name',
            1 => 'team_leader',
        ];

        $regionLists = Region::select('id', 'region_name', 'team_leader');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $regionLists = $regionLists->where(function ($query) use ($request) {
                $searchValue = '%' .$request['search']['value']. '%';
                $query->where('region_name', 'like', $searchValue)
                    ->orWhere('team_leader', 'like', $searchValue);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $regionLists = $regionLists->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $regionLists->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $regionLists = $regionLists->offset($request['start'])->limit($request['length']);
        }

        $regionLists = $regionLists->get();

        $cnt = 0;
        foreach ($regionLists as $regionData) {
            $regionLists[$cnt]['region_name'] = $regionData['region_name'];
            $regionLists[$cnt]['team_leader'] = $regionData['team_leader'] ?? '';
            if (H::canPermissions('region.edit')) {
                $btn = '';
                $btn .= '<a href="' . url('/manageregion/edit', $regionData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
                $btn = $btn . '&nbsp|&nbsp <a href="javascript:void(0)" class="disable-it"  data-href="' . url('/manageregion/delete', $regionData['id']) . '"  class="disable btn btn-info btn-sm"><i class="fa fa-trash-o" title="Delete"></i></a>';
                $regionLists[$cnt]['action'] = $btn;
            }
            $cnt++;
        }

        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $regionLists,
        ];

        return $data;
    }

    public function add()
    {
        return view('region.form');
    }

    public function saveRegion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'region_name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        Region::create($request->except(['_token']));

        return redirect('/region')->with('success', 'Region added!');
    }

    public function edit($id) {
        $regionDetail = Region::find($id);
        return view('region.form', compact('regionDetail'));
    }

    public function updateRegion(Request $request) {
        $validator = Validator::make($request->all(), [
            'region_name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            $updateArr['region_name'] = $request->region_name;
            $updateArr['team_leader'] = $request->team_leader?? '';
            Region::where('id', $request->id)->update($updateArr);

            return redirect('/region')->with('success', 'Region updated!');
        } catch (\Exception $e) {
            return redirect('/region/edit/' . $request->id)->with('error', 'Something went wrong.');
        }
    }

    public function destroy($id)
    {
        $region = Region::find($id);
        $region->delete();

        return redirect('/region')->with('success', 'Region deleted!');
    }
}
