<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\HubCities;
use Carbon\Carbon;
use Auth;

class HubCitiesController extends Controller
{
    public function index(Request $request) {
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'State', 'label' => 'State', 'width' => '20%'],
            ['name' => 'City', 'label' => 'City', 'width' => '20%'],
            ['name' => 'HUB', 'label' => 'HUB', 'width' => '20%'],
            ['name' => 'Branch', 'label' => 'Branch', 'width' => '20%'],
        ];
        if ($request->ajax()) {
            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = HubCities::where('is_deleted', '0');

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where('State', 'LIKE', "%{$search}%")
                    ->orWhere('City', 'LIKE', "%{$search}%")
                    ->orWhere('HUB', 'LIKE', "%{$search}%")
                    ->orWhere('Branch', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }
        return view('hubcities.grid', ['columns' => $columns]);
    }

    public function add() {
        return view('hubcities.form', ['mode' => 'add']);
    }

    public function edit(Request $request) {
        $params = $request->all();
        if (isset($params['id'])) {
            $data = HubCities::find($params['id'])->toArray();
            return view('hubcities.form', ['mode' => 'edit', 'data' => $data]);
        } else {
            return redirect('/hubcities');
        }
    }

    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'State' => 'required',
            'City' => 'required',
            'HUB' => 'required',
            'Branch' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $request->request->add(['Notes' => $request->input('Notes')??'']);
        if ($request->input('id')) {
            $request->request->add(['updated_at' => Carbon::now()]);
            $request->request->add(['updated_by' => Auth::id()]);
            HubCities::where('id', $request->id)->update($request->except(['_token']));
        } else {
            $request->request->add(['created_at' => Carbon::now()]);
            $request->request->add(['updated_at' => Carbon::now()]);
            $request->request->add(['created_by' => Auth::id()]);
            $request->request->add(['updated_by' => Auth::id()]);
            HubCities::create($request->except(['_token']));
        }
        return redirect('/hubcities')->with('success', 'Hub city information saved successfully!');
    }

    public function delete(Request $request)
    {
        $params = $request->all();
        if (isset($params['id'])) {
            HubCities::where('id', $params['id'])->update(['is_deleted' => 1]);
            return redirect('/hubcities')->with('success', 'Hub city removed successfully!');
        } else {
            return redirect('/hubcities');
        }
    }
}
