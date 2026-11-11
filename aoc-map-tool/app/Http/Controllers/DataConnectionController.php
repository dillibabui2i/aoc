<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Helpers\H;
use DataTables;
class DataConnectionController extends Controller
{
    
    /**
     * Show the Class list.
     */
    public function index()
    {
        return view('dataconnection.grid');
    }

    /**
     * Show the Class ajax list.
     */
    public function getDataConnectionList(Request $request)
    {
        $columns = [
            0 => 'process',
            1 => 'source',
            2 => 'last_updated_date',
            3 => 'last_updated_id'
        ];
        $dataConnectionList = DB::table('process_settings')->select('process_settings.*', DB::raw("DATE_FORMAT(process_settings.last_updated_date,'%m/%d/%Y %h:%i %p') AS display_last_updated_date"));
        $dataConnectionList = $dataConnectionList->whereNotNull('source');
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $dataConnectionList = $dataConnectionList->where(function ($query) use ($request) {
                $searchValue = '%' . $request['search']['value'] . '%';
                $query->where('process_settings.process', 'like', $searchValue)
                      ->orWhere('process_settings.purpose', 'like', $searchValue)
                      ->orWhere('process_settings.frequency', 'like', $searchValue)
                      ->orWhere('process_settings.source', 'like', $searchValue)
                      ->orWhere('process_settings.info', 'like', $searchValue);
            });
        }
        $total_records = $dataConnectionList->get();
        $last_updated_date = ''; $source = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $key => $value){
                if($value['name']=='source') {
                    $source[] = $value['value'];
                }
                if($value['name']=='last_updated_date') {
                    $last_updated_date = $value['value'];
                }
            }
        }
        if(!empty($source)) {
            $dataConnectionList = $dataConnectionList->whereIn('source',$source);
        }
        if(!empty($last_updated_date)) {
             $last_updated_date = date("Y-m-d",strtotime($last_updated_date));
            $dataConnectionList = $dataConnectionList->whereDate('last_updated_date',$last_updated_date);
        }
        $totalFiltered = $dataConnectionList->count();
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $dataConnectionList = $dataConnectionList->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        if (isset($request['start']) && $request['length'] != '-1') {
            $dataConnectionList = $dataConnectionList->offset($request['start'])->limit($request['length']);
        }
        $dataConnectionList = $dataConnectionList->get()->toArray();
        
        foreach ($dataConnectionList as $dataConnectionData) {
            $dataConnectionData->action ='<a href="' . url('/syncdataconnection/edit', $dataConnectionData->id) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
        }
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $dataConnectionList,
        ];
    }
    public function edit($id)
    {
        $dataConnectionDetail = DB::table('process_settings')->where('id',$id)->first();
        return view('dataconnection.form', compact('dataConnectionDetail'));
    }

    public function updateDataConnection(Request $request)
    {
        $updateArr = [];
        try {
            $data = $request->all();
            $updateArr['purpose'] =  $data['purpose'];
            $updateArr['frequency'] =  $data['frequency'];
            $updateArr['info'] =  $data['info'];
            DB::table('process_settings')->where('id', $request->id)->update($updateArr);
            return redirect('/managesyncdataconnections')->with('success', 'Your changes have been saved!');
        } catch (\Exception $e) {
            H::ExcelLog('==updateDataConnection-Exception-' . $e->getMessage(), 'updateDataConnection.log');
            return redirect('/syncdataconnection/edit/' . $request->id)->with('error', 'Something went wrong. Please try again.');
        }
    }
}
