<?php

namespace App\Http\Controllers;

use App\Repositories\MssqlFacilities\MssqlFacilitiesRepository;
use DB;
use Illuminate\Http\Request;
use Throwable;

class RenewablesRuleController extends Controller
{

    public function __construct(private MssqlFacilitiesRepository $facilitiesRepository) {}

    public function index()
    {
        return view('renewablesrule.grid');
    }

    public function getRenewablesRule(Request $request)
    {
        $columns = [
            0 => 'name',
            1 => 'states',
            2 => 'isClinician',
            4 => 'isOfficeStaff',
            5 => 'discipline',
            6 => 'email_to_nurse',
            7 => 'has_client_over21',
        ];
        $renewablesrules = DB::table('renewable_rules')->select('renewable_rules.*', 'renewables_types.name')->leftJoin('renewables_types', 'renewables_types.id', 'renewable_rules.renewable_type_id');
        $totalrecord = $renewablesrules->get();
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $renewablesrules = $renewablesrules->where(function ($query) use ($request) {
                $searchValue = '%' .$request['search']['value']. '%';
                $query->where('renewables_types.name', 'like', $searchValue)
                    ->orWhere('renewable_rules.states', 'like', $searchValue)
                    ->orWhere('renewable_rules.discipline', 'like', $searchValue);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $orderColumnIndex = intval($request['order'][0]['column']);
            $orderDir = $request['order'][0]['dir'];
            if (isset($columns[$orderColumnIndex])) {
                $orderColumn = $columns[$orderColumnIndex];
                $renewablesrules = $renewablesrules->orderBy($orderColumn, $orderDir);
            }
        }
        if (isset($request['start']) && $request['length'] != '-1') {
            $renewablesrules = $renewablesrules->offset($request['start'])->limit($request['length']);
        }
        $renewablesrules = $renewablesrules->get();
        $cnt = 0;
        $renewablesrulesData = [];
        foreach ($renewablesrules as $renewablesrule) {
            $btn = '<a href="' . url('/renewablesrule/edit', $renewablesrule->id) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            $btn = $btn . '&nbsp|&nbsp <a href="javascript:void(0)" class="disable-it"  data-href="' . url('/renewablesrule/delete', $renewablesrule->id) . '"  class="disable btn btn-info btn-sm"><i class="fa fa-trash" title="Delete"></i></a>';
            $renewablesrulesData[$cnt]['action'] = $btn;
            $renewablesrulesData[$cnt]['type'] = $renewablesrule->name;
            $renewablesrulesData[$cnt]['states'] = $renewablesrule->states;
            $renewablesrulesData[$cnt]['is_clinician'] = ($renewablesrule->isClinician == 1) ? 'Yes' : 'No';
            $renewablesrulesData[$cnt]['is_Office_staff'] = ($renewablesrule->isOfficeStaff == 1) ? 'Yes' : 'No';
            $renewablesrulesData[$cnt]['discipline'] = $renewablesrule->discipline;
            $renewablesrulesData[$cnt]['email_to_nurse'] = ($renewablesrule->email_to_nurse == 1) ? 'Yes' : 'No';
            $renewablesrulesData[$cnt]['missing'] = ($renewablesrule->Missing == 1) ? 'Yes' : 'No';
            $renewablesrulesData[$cnt]['expiring'] = ($renewablesrule->Expiring == 1) ? 'Yes' : 'No';
            $renewablesrulesData[$cnt]['has_client_over21'] = ($renewablesrule->has_client_over21 == 1) ? 'Yes' : 'No';
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($totalrecord)),
            'recordsFiltered' => intval(count($totalrecord)),
            'data'            => $renewablesrulesData,
        ];

        return $data;
    }

    /**
     * @throws Throwable
     */
    public function add()
    {
        try {
            $renewablesname = DB::table('renewables_types')
              ->select('renewables_types.name', 'renewables_types.id')
              ->get();

            $states = $this->facilitiesRepository->getUniqueStates();

            return view('renewablesrule.form', [
                'renewablesname' => $renewablesname,
                'states' => $states,
              ]
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function checkRenewableRule(Request $request){
        $id = $request->id;
        $chekRule = DB::table('renewable_rules')->where('renewable_rules.renewable_type_id', $id)->first();
        if(!empty($chekRule)){
            return response()->json([
                'success' => 0,
                'message' => 'Rules exsits'
            ]);
        }else{
            return response()->json([
                'success' => 1,
                'message' => 'No Rules exsits'
            ]);
        }
    }

    public function saveRenewablesRule(Request $request){
        $isClinician = 0;
        $IsOfficeStaff = 0;
        $Expiring = 0;
        $Missing = 0;
        if(!empty($request->roles)){
            $isClinician = in_array("isClinician", $request->roles) ? 1 : 0;
            $IsOfficeStaff = in_array("IsOfficeStaff", $request->roles) ? 1 : 0;
        }
        if(!empty($request->buckets)){
            $Expiring = in_array("Expiring", $request->buckets) ? 1 : 0;
            $Missing = in_array("Missing", $request->buckets) ? 1 : 0;
        }
        $data = [
            "renewable_type_id" => $request->name,
            "states" => implode(',', $request->state),
            "isClinician" => $isClinician,
            "isOfficeStaff" => $IsOfficeStaff,
            "discipline" => implode(',', $request->discipline),
            "Expiring" => $Expiring,
            "Missing" => $Missing,
            "email_to_nurse" => (isset($request->email_to_nurse) && $request->email_to_nurse == '1') ? 1 : 0,
            "has_client_over21" => (isset($request->has_client_over21) && $request->has_client_over21 == '1') ? '1' : '0',
            "created_by" => auth()->user()->id,
        ];
        $addrule = DB::table('renewable_rules')->insert($data);
        if($addrule == true){
            return redirect()->route('renewablesrule')->with('success', 'Renewables Rule added successfully.');
        }else{
            return redirect()->back()->with('error', 'Something went worng');
        }
    }

    /**
     * @throws Throwable
     */
    public function edit($id)
    {
        try {
            $ruleDetail = DB::table('renewable_rules')
              ->select('renewable_rules.*', 'renewables_types.name')
              ->leftJoin('renewables_types', 'renewables_types.id', 'renewable_rules.renewable_type_id')
              ->where('renewable_rules.id', $id)
              ->first();

            $states = $this->facilitiesRepository->getUniqueStates();

            return view('renewablesrule.form', [
              'ruleDetail' => $ruleDetail,
              'states' => $states,
            ]);
        } catch (Throwable $t) {
            throw $t;
        }
    }

    public function updateRenewablesRule(Request $request){
        try{
            $isClinician = 0;
            $IsOfficeStaff = 0;
            $Expiring = 0;
            $Missing = 0;
            if(!empty($request->roles)){
                $isClinician = in_array("isClinician", $request->roles) ? 1 : 0;
                $IsOfficeStaff = in_array("IsOfficeStaff", $request->roles) ? 1 : 0;
            }
            if(!empty($request->buckets)){
                $Expiring = in_array("Expiring", $request->buckets) ? 1 : 0;
                $Missing = in_array("Missing", $request->buckets) ? 1 : 0;
            }
            $data = [
                "renewable_type_id" => $request->renewable_type_id,
                "states" => implode(',', $request->state),
                "isClinician" => $isClinician,
                "isOfficeStaff" => $IsOfficeStaff,
                "discipline" => implode(',', $request->discipline),
                "Expiring" => $Expiring,
                "Missing" => $Missing,
                "email_to_nurse" => (isset($request->email_to_nurse) && $request->email_to_nurse == '1') ? 1 : 0,
                "has_client_over21" => (isset($request->has_client_over21) && $request->has_client_over21 == '1') ? '1' : '0',
                "updated_by" => auth()->user()->id,
            ];
            DB::table('renewable_rules')->where('id', $request->id)->update($data);
            return redirect()->route('renewablesrule')->with('success', 'Renewables Rule updated successfully.');
        }catch(\Exception $e){
            return redirect()->back()->with('error', 'Something went worng');
        }
    }

    public function destroy($id){
        $deleterecord = DB::table('renewable_rules')->where('id', $id)->delete();
        if($deleterecord == true){
            return redirect()->route('renewablesrule')->with('success', 'Renewables Rule deleted successfully.');
        }else{
            return redirect()->back()->with('error', 'Something went worng');
        }
    }
}
