<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\Renewables\RenewablesRefreshByProviderIdRequest;
use App\Jobs\Renewable\RefreshByProviderIdJob;
use App\Transformer\Dashboard\Buckets\Nursys\NursysMismatchTransformer;
use App\SyncProcesses\DaasRenewables;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Renewables as RenewalbesTable;
use DB;

class RenewablesController extends Controller
{

    public function getNursysExceptionForm(Request $request){
        return view('orientation.approvenursys', ["data" => $request->all()]);
    }

    public function saveNursysExceptionForm(Request $request){
        $req = $request->all();
        try {
            if(isset($req['renewable_id'])  ) {
                $renewableData = DB::table("renewables")->where("id",$req['renewable_id'])->first();
                $user = H::getLoggedInUser();
                $userId = $user['id'];

                $data['nursys_outcome']='Uploaded to KanTime';
                $data['nursys_updated_by']=$userId;
                $data['nursys_updated_at']=date("Y-m-d H:i:s");
                $data['last_nursys_sync']='2000-01-01';
                DB::table("renewables")->where("id",$req['renewable_id'])->update($data);


                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $renewableData->aoc_provider_id;
                $noteData['notes'] =$user['name'].'  marked the '.$renewableData->license_no.' Professional License as updated in KanTime.';
                H::getHelper('ActivityNotes')->setActivityNotes($noteData);


                return response()->json(["status" => true]);
            }
        } catch (\Exception $e) {
            return response()->json(["status" => false]);
        }

    }

    public function getNursysMismatchData(Request $request, NursysMismatchTransformer $transformer) {


        $data = H::getHelper("NursysMismatchException")->getNursysMismatchData("dashboard",$request->input("type"), $request->input("filters"));

        return $transformer->transform($data);

    }

    public function reloadNursysMismatchData(Request $request) {
        return json_encode(["counter"=>H::getHelper("NursysMismatchException")->getNursysMismatchData("dashboard",$request->input("type"), $request->input("filters"),1),"type"=>$request->input("type")]);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function getExpiringData(Request $request): mixed
    {
        $expcount = 0;
        $data = H::getHelper('Renewables')
            ->getRenewablesData('dashboard', $request->input('type'), $request->input('filters'));

        if ($request->input("type") == 'thirtyzeroexpiring') {
            $expQury = $data;
            $data = $data->get();
            $expcount = $expQury->having('ItemStatus', '=', 'Expired')->count();
        } elseif ($request->input("type") == 'cccfifteenzeroexpiring') {
            if (LD::checkFeatureFlag('change-ccc-renewable-bucket-name')) {
                $expQury = $data;
                $data = $data->get();
                $expcount = $expQury->having('ItemStatus', '=', 'Expired')->count();
            }
        }

        return Datatables::of($data)
            ->with('expcount', function () use ($data, $expcount) {
                return '(' . $data->count() . ' | ' . $expcount . ' Expired)';
            })
            ->editColumn('ClientName', function ($data) {
                $patientData = explode("#", $data->PatientData);
                return (isset($patientData[0]) && !empty($patientData[0])) ? $patientData[0] : '-';
            })->editColumn('PatientAccount', function ($data) {
                $patientData = explode("#", $data->PatientData);
                return isset($patientData[1]) ? $patientData[1] : '-';
            })->editColumn('expiration_date_format', function ($row) {
                if (isset($row->expiration_date_format)) {
                    return [
                        'display' => e(H::getFormatedDate($row->expiration_date_format)),
                        'timestamp' => strtotime($row->expiration_date_format)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })
            ->editColumn('recent_date', function ($row) {
                if (isset($row->recent_date) && $row->recent_date != '00/00/0000') {
                    return [
                        'display' => $row->recent_date,
                        'timestamp' => strtotime($row->recent_date)
                    ];
                } else {
                    return [
                        'display' => '',
                        'timestamp' => ''
                    ];
                }
            })->addIndexColumn()->escapeColumns('Action')->make(true);
    }
    public function exportNursys(Request $request, NursysMismatchTransformer $transformer){
        try{
            $dataTableFields = H::getHelper('NursysMismatchException')->getDataTableHtml('export');
            $data = H::getHelper("NursysMismatchException")->getNursysMismatchData("export",'',$request->input("filters") ,false,$request);

            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=nursysmismatch.csv",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $callback = function () use ($data, $dataTableFields, $transformer) {
                $file = fopen('php://output', 'w');

                fputcsv($file, array_values($dataTableFields));

                foreach ($data as $tableData) {
                    $temp = [];
                    foreach ($dataTableFields as $key => $value) {
                        if ($key === 'nursys_mismatch_created_at') {
                            $temp[] = $transformer->formatCreatedDate($tableData[$key]);
                            continue;
                        }

                        $temp[] = $tableData[$key];
                    }
                    fputcsv($file, $temp);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            H::ExcelLog('RenewablesController::exportnursys:'.$e->getMessage(),'nursysmismatch.log');
        }
    }
    public function nursysShowAll(Request $request, NursysMismatchTransformer $transformer){
        try {
            $columns = H::getHelper('NursysMismatchException')->getDataTableHtml('');

            if ($request->ajax()) {
                $data = H::getHelper("NursysMismatchException")->getNursysMismatchData("showall",'',$request->input("filters") ,false,$request);

                return response()->json([
                    'draw'            => intval($request->input('draw')),
                    'recordsTotal'    => intval($data['totalData']),
                    'recordsFiltered' => intval($data['totalFiltered']),
                    'data'            => $transformer->transformShowAllRows($data['data']),
                ]);
            }

            return view('renewable.nursysgrid',['columns' => $columns]);
        } catch (\Exception $e) {
            H::ExcelLog('RenewablesController::showAll:'.$e->getMessage(),'nursysmismatch.log');
        }
    }

    public function showAll(Request $request){
        try {
            $columns = H::getHelper('Renewables')->getDataTableHtml('');
            if ($request->ajax()) {
                $data = H::getHelper("Renewables")->getRenewablesData("showall",'',$request->input("filters") ,false,$request);

                return response()->json([
                    'draw'            => intval($request->input('draw')),
                    'recordsTotal'    => intval($data['totalData']),
                    'recordsFiltered' => intval($data['totalFiltered']),
                    'data'            => $data['data'],
                ]);
            }

            return view('renewable.grid',['columns' => $columns]);
        } catch (\Exception $e) {
            H::ExcelLog('RenewablesController::showAll:'.$e->getMessage(),'renewables.log');
        }
    }
    public function reloadExpiringData(Request $request) {
        return json_encode(["counter"=>H::getHelper("Renewables")->getRenewablesData("dashboard",$request->input("type"), $request->input("filters"),1),"type"=>$request->input("type")]);
    }
    public function exportRenewables(Request $request){
        try{
            $dataTableFields = H::getHelper('Renewables')->getDataTableHtml('export');
            $data = H::getHelper("Renewables")->getRenewablesData("export",'',$request->input("filters") ,false,$request);

            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=renewable.csv",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $callback = function () use ($data, $dataTableFields) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($dataTableFields));

                foreach ($data as $tableData) {
                    $temp = [];
                    foreach ($dataTableFields as $key => $value) {
                        $temp[] = $tableData[$key];
                    }
                    fputcsv($file, $temp);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            H::ExcelLog('RenewablesController::exportRenewables:'.$e->getMessage(),'renewables.log');
        }
    }


    public function getOutcomeForm(Request $request) {
       $data = $request->all();
        $renewableData = [];
        $providerData = [];



        $renewableData = RenewalbesTable::select('renewables.*','renewables_types.name',
        DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"),)
        ->leftJoin('mssql_providers','renewables.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin("mssql_facilities","mssql_facilities.FacilityID","=","mssql_providers.FacilityID")
        ->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.renewable_type_id')->where("renewables.id",$data['renewableid'])
        ->where('mssql_providers.is_deleted',0)->first();
        if(!empty($renewableData->id)) {
            $providerData=DB::table("mssql_providers")->select("*")->where("id",$renewableData->aoc_provider_id)->where('mssql_providers.is_deleted',0)->first();
        }
        return view('renewable.renewableoutcomepopup', ["renewableData" => $renewableData, "clinicianDetails"=>$providerData,"type" => $request->input("buckettype")]);

    }

    public function saveOutcomeForm(Request $request) {
        $req = $request->all();

        $data = [];
        $renewableData = DB::table('renewables')->select('renewables.*','renewables_types.name') ->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.renewable_type_id')->where("renewables.id",$req['renewable_id'])->first();

        if($req['outcome']=='Attempt 1' || $req['outcome']=='Attempt 2' || $req['outcome']=='Attempt 3' ) {
            $attemptCountField = $req['type'].'_attempt_count';
            $data[$attemptCountField] = $renewableData->$attemptCountField+1;
        }
        if(!empty($req['outcome'])) {
            $user = H::getLoggedInUser();
            $userId = $user['id'];
            $data['outcome'] = $req['outcome'];
            $data['last_followup_by'] = $userId;
            $data['last_follow_up'] = date("Y-m-d H:i:s");
            RenewalbesTable::where("id",$req['renewable_id'])->update($data);

            $isAttempt = in_array($req['outcome'], ['Attempt 1', 'Attempt 2', 'Attempt 3']);
            $noteText = sprintf('%s %s documented outcome %s on renewable item %s.', date('m/d/Y H:i'), $user['name'], $req['outcome'], $renewableData->name);
            if ($isAttempt && !empty($req['results'])) {
                $results = is_array($req['results']) ? implode(', ', $req['results']) : $req['results'];
                $noteText .= sprintf(' Result: %s.', $results);
            }
            $noteText .= sprintf(' Comments: %s', $req['comment']);

            $noteData['notes'] = $noteText;
            $noteData['entity'] = 'clinician';
            $noteData['entity_id'] = $renewableData->aoc_provider_id;
            H::getHelper('ActivityNotes')->setActivityNotes($noteData);

            $noteData['entity'] = 'renewables';
            $noteData['entity_id'] = $req['renewable_id'];
            H::getHelper('ActivityNotes')->setActivityNotes($noteData);

            return response()->json(["status" => true]);

        }
        return response()->json(["status" => false]);

    }

    public function getCompleteRenewableForm(Request $request) {
        $req = $request->all();

        return view('renewable.completerenewable', ["renewable_id"=>$req['renewable_id'],"bucket_type" => $request->input("bucket_type")]);
    }

    public function saveCompleteRenewableForm(Request $request) {
            try {
                $req = $request->all();
                $user = H::getLoggedInUser();
                $userId = $user['id'];

                $renewableData = DB::table('renewables')
                    ->select('renewables.aoc_provider_id', 'renewables_types.name')
                    ->leftJoin('renewables_types', 'renewables.renewables_type', '=', 'renewables_types.renewable_type_id')
                    ->where('renewables.id', $req['renewable_id'])
                    ->first();

                $data['outcome'] = "Uploaded to KanTime";
                $data['last_followup_by'] = $userId;
                $data['last_follow_up'] = date("Y-m-d H:i:s");
                RenewalbesTable::where("id",$req['renewable_id'])->update($data);

                $noteText = sprintf('%s %s marked the renewable %s as completed in Kantime.', date('m/d/Y H:i'), $user['name'], $renewableData->name);

                $noteData['notes'] = $noteText;
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $renewableData->aoc_provider_id;
                H::getHelper('ActivityNotes')->setActivityNotes($noteData);

                $noteData['entity'] = 'renewables';
                $noteData['entity_id'] = $req['renewable_id'];
                H::getHelper('ActivityNotes')->setActivityNotes($noteData);

                return response()->json(["status" => true]);
            } catch(\Exception $e) {
                return response()->json(["status" => false]);
            }
    }


    public function getRenewableNotesForm(Request $request) {
        $req = $request->all();
        return view('renewable.renewablenotes', ["renewable_id"=>$req['renewable_id'],"bucket_type" => $request->input("bucket_type")]);
    }

    public function saveRenewablenotesForm(Request $request) {

        try {
            $req = $request->all();
            $renewableData = RenewalbesTable::select('renewables.aoc_provider_id')->where('renewables.id', $req['renewable_id'])->first();

            $noteData['notes'] = $req['notes'];
            $noteData['entity'] = 'renewables';
            $noteData['entity_id'] = $req['renewable_id'];
            H::getHelper('ActivityNotes')->setActivityNotes($noteData);

            $noteData['entity'] = 'clinician';
            $noteData['entity_id'] = $renewableData->aoc_provider_id;
            H::getHelper('ActivityNotes')->setActivityNotes($noteData);

            return response()->json(["status" => true]);
        } catch(\Exception $e) {
            return response()->json(["status" => false]);
        }
    }

    /**
     * @param RenewablesRefreshByProviderIdRequest $request
     * @return JsonResponse
     */
    public function refreshByProviderId(RenewablesRefreshByProviderIdRequest $request): JsonResponse
    {
        RefreshByProviderIdJob::dispatch((int) $request->input('id'));

        return response()->json(['message' => 'The sync process has started. Please reopen the modal window.']);
    }
}
