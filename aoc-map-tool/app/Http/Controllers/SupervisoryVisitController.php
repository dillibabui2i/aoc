<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\MssqlProviders;
use App\MssqlPatients;
use DataTables;
use App\User;
use App\Helpers\Notifications;
use App\Helpers\LD;

class SupervisoryVisitController extends Controller
{
    //
    // Show all page for Status change requests
    public function showAll(Request $request){
        try {
            $columns = H::getHelper('SupervisoryVisits')->getDataTableHtml('showall');
            if ($request->ajax()) {
                $data = H::getHelper("SupervisoryVisits")->
                getSupervisoryVisits("showall",$request->input("filters") ,false,$request);

                return response()->json([
                    'draw'            => intval($request->input('draw')),
                    'recordsTotal'    => intval($data['totalData']),
                    'recordsFiltered' => intval($data['totalFiltered']),
                    'data'            => $data['data'],
                ]);
            }
            return view('supervisoryvisits.grid',['columns' => $columns]);
        } catch (\Exception $e) {
            H::ExcelLog('StatusChangeRequestController::showAll:'.$e->getMessage(),'getSupervisoryVisits.log');
        }
    }

    public function openForm(Request $request)
    {
        $clinicianDetails = MssqlProviders::find($request->input('provider_id'));
        if ($clinicianDetails->fullName == '') {
            $clinicianDetails->fullName = $clinicianDetails->FirstName . ' ' . $clinicianDetails->LastName;
        }
        $patientsDetails = MssqlPatients::find($request->input('patient_id'));
        $supervisoryVisitData = DB::table('supervisory_visits')
            ->where('id', $request->input('id'))
            ->active()
            ->first();

        return view('supervisoryvisits.form', [
            'id' => $request->input('id'),
            'clinicianDetails' => $clinicianDetails,
            'patientsDetails' => $patientsDetails,
            'supervisoryVisitData' => $supervisoryVisitData,
            'mode' => $request->input('mode'),
        ]);
    }

    public function saveForm(Request $request)
    {
        $user = H::getLoggedInUser();
        try {
            $data = $request->all();
            unset($data['_token']);
            unset($data['id']);
            unset($data['provider_id']);
            unset($data['patient_id']);

            $data['completed_at'] = date('Y-m-d H:i:s');
            $data['completed_by'] = $user['id'];
            $data['visit_not_matched'] =0;
            $supervisorVisitOutcome = config('constant')['supervisorVisitOutcome'];

            $clinicianDetails = MssqlProviders::find($request->input('provider_id'));
            $clinicianName = $clinicianDetails->FirstName . ' ' . $clinicianDetails->LastName;
            $patientsDetails = MssqlPatients::find($request->input('patient_id'));
            $patientName = $patientsDetails->Patient_FirstName . ' ' . $patientsDetails->Patient_LastName;
            $clinicianMsg = 'A supervisory visit has been completed in KanTime for
             ' . $clinicianName . ' with ' . $patientName . ' and evaluation responses recorded.<br/>';
            $clinicianMsg .= 'Functions proficiently within the job description for his/her position: ' .
            (!empty($data['item1_score']) ? $supervisorVisitOutcome[$data['item1_score']] : '') . '<br/><br/>';
            $clinicianMsg .= 'Follows Plan Of Care, Makes appropriate action always mindful of safety standards/precautions: ' .
            (!empty($data['item2_score']) ? $supervisorVisitOutcome[$data['item2_score']] : '') . '<br/><br/>';
            $clinicianMsg .= 'Reports for duty promptly for scheduled shifts, Timely submission of notes.
             Responds promptly to communication from office staff: ' .
            (!empty($data['item3_score']) ? $supervisorVisitOutcome[$data['item3_score']] : '') . '<br/><br/>';
            $clinicianMsg .= 'Handles situations in a professional manner with few to no complaints,
             Maintains good communication with branch regarding shifts and client information: ' .
            (!empty($data['item4_score']) ? $supervisorVisitOutcome[$data['item4_score']] : '') . '<br/><br/>';
            $clinicianMsg .= 'Sup Visit has been completed and uploaded in KanTime : ' .
            (!empty($data['sv_uploaded_to_kantime']) ? 'Yes' : 'No') . '<br/>';

            $clinicianNoteData = [];
            $clinicianNoteData['entity'] = 'clinician';
            $clinicianNoteData['entity_id'] = $request->input('provider_id');
            $clinicianNoteData['notes'] = $clinicianMsg;

            H::getHelper("ActivityNotes")->setActivityNotes($clinicianNoteData);

            $clientMsg = 'A supervisory visit has been completed in KanTime for
            ' . $clinicianName . ' with ' . $patientName . '.';

            $clientNoteData = [];
            $clientNoteData['entity'] = 'client';
            $clientNoteData['entity_id'] = $request->input('patient_id');
            $clientNoteData['notes'] = $clientMsg;

            H::getHelper('ActivityNotes')->setActivityNotes($clientNoteData);
            if (LD::checkFeatureFlag('add-sup-visit-date-and-visit-not-matched-column')) {
                $supVisitData = DB::table('supervisory_visits')
                    ->select(DB::raw('(SELECT form_date FROM supervisory_form_master sm
                WHERE sm.client_id = mssql_patients.BillingInternalPatientID
                and sm.supervisee_cgid = mssql_providers.BillingProviderID order by form_date desc limit 1)
                AS last_sup_visit'))
                    ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id')
                    ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id')
                    ->where('supervisory_visits.id', $request->input('id'))
                    ->active('supervisory_visits')
                    ->first();
                if(strtotime($supVisitData->last_sup_visit) != strtotime($data['form_sup_visit_date'])) {
                        $data['visit_not_matched'] =1;
                }
                $data['form_sup_visit_date'] = date('Y-m-d',strtotime($data['form_sup_visit_date']));
            }
            DB::table('supervisory_visits')
                ->where('id', $request->input('id'))
                ->active()
                ->update($data);
            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }

    public function reloadSupervisoryVisit(Request $request){
        return json_encode(["counter"=>H::getHelper("SupervisoryVisits")->getSupervisoryVisits("dashboard"
        ,$request->input("filters"),1,$request),"type"=>$request->input("type")]);
    }

    public function supervisoryVisitBucket(Request $request){
        if(LD::checkFeatureFlag('add-code-change-to-make-supervisoryvisits-fast')){
            $data = H::getHelper("SupervisoryVisits")->getSupervisoryVisitsForBucket($request->input("filters"),0,$request);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($data['totalData']),
                'recordsFiltered' => intval($data['totalFiltered']),
                'data'            => $data['data'],
            ]);
        }else{
            $data = H::getHelper("SupervisoryVisits")->getSupervisoryVisits("dashboard",
            $request->input("filters"),0,$request);
            return Datatables::of($data)
            ->setRowAttr([
                'class' => function($row) {
                    $date = $row->visit_date_due;
                    if(strtotime($date) < strtotime(now())) {
                        return 'dashboard-older-record-cl-ra';
                    } else {
                        return '';
                    }
                },
            ])
            ->addColumn('Action', function ($row) {
                $btn = '';
                if (!empty($row->completed_at) && !empty(!empty($row->completed_by))) {
                    $btn .= '<a href="javascript:void(0)"\
                    onclick="openSupervisoryVisitForm('.$row->id.',
                    '.$row->aoc_provider_id.','.$row->aoc_patient_id.',\'view\');" >
                            <i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
                $btn .= '<a href="javascript:void(0)"\
                onclick="openSupervisoryVisitForm('.$row->id.',
                '.$row->aoc_provider_id.','.$row->aoc_patient_id.',\'edit\');" >
                <i class="fa fa-check-square-o" aria-hidden="true" title = "Complete Task"></i></a>';
                $btn .= '<a href="javascript:void(0)"
                onclick="getClinicianProfile('.$row->aoc_provider_id.')" >
                <i class="fa fa-user-md" aria-hidden="true" title = "View Clinician Profile"></i></a>';
                $btn .= '<a href="javascript:void(0);"
                onclick="getClientDetails('.$row->aoc_patient_id.')">
                <i class="fa fa-user" title = "View Client Profile"></i></a>';
                if(LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up')){
                    if(H::canPermissions('supervisoryVisits.delete')){
                        $btn .= '<a href="javascript:void(0);"
                        onclick="supervisoryvisitDeletePopUp('.$row->id.', \'bucket\')">
                        <i class="fa fa-trash-o" title = "Delete"></i></a>';
                    }
                }else{
                    if(H::canPermissions('supervisoryVisits.delete')){
                        $btn .= '<a href="javascript:void(0);"
                        onclick="supervisoryvisitDeletePopUp('.$row->id.', \'bucket\')">
                        <i class="fa fa-trash-o" title = "Delete"></i></a>';
                    }
                }
                return $btn;
            })
                ->addColumn('select', function ($row) use ($request) {
                    return '<input type="checkbox" name="checkbox-' . $request->input('type') . '[]"
                class="form-input td select-all-' . str_replace(' ', '_', $request->input('type')) . '"
                value="' . $row->id . '" />';
                })
            ->editColumn('last_sup_visit', function ($row) {
                if(!empty($row->last_sup_visit)) {
                    return [
                        'display' => e(H::getFormatedDate($row->last_sup_visit)),
                        'timestamp' => strtotime($row->last_sup_visit)
                    ];
                } else {
                    return [
                        'display' => '-',
                        'timestamp' => ''
                    ];
                }
            })
            ->editColumn('visit_date_due', function ($row) {
                if(!empty($row->visit_date_due)) {
                    return [
                        'display' => e(H::getFormatedDate($row->visit_date_due)),
                        'timestamp' => strtotime($row->visit_date_due)
                    ];
                } else {
                    return [
                        'display' => '-',
                        'timestamp' => ''
                    ];
                }
            })
            ->editColumn('visit_not_matched', function ($row) {
                if($row->visit_not_matched == 1) {
                    return 'Sup visit not matched';
                } else {
                    return '';
                }
            })
            ->addIndexColumn()
            ->escapeColumns('Action')
            ->make(true);
        }
    }

    public function assignedSuperCcm(Request $request){
        $reqData = $request->all();
        $noteData = [];
        if (!empty($reqData['ccr_id']) && !empty($reqData['ids'])) {
            $selectedIds = explode(",",$reqData['ids']);
            DB::table('supervisory_visits')
                ->whereIn('id', $selectedIds)
                ->active()
                ->update(['assigned_to' => $reqData['ccr_id']]);
            foreach($selectedIds as $id) {
                $bucket = DB::table('supervisory_visits')
                    ->select(
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                            mssql_patients.Patient_LastName) AS patient_name"),
                        DB::raw("CONCAT(mssql_providers.FirstName,' ',
                            IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
                                mssql_providers.LastName) as clinicianName"), 'supervisory_visits.aoc_provider_id'
                    )
                    ->leftJoin('mssql_patients', function ($query) {
                        $query->on('mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id');
                    })->leftJoin('mssql_providers', function ($query) {
                        $query->on('mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id');
                    })
                    ->where('supervisory_visits.id', $id)
                    ->active('supervisory_visits');
                $patientName = $bucket->first();

                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $patientName->aoc_provider_id;
                $noteData['notes'] = ' Supervisory visit reassigned to ' . User::find($reqData['ccr_id'])->name;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                $title = 'Supervisory visit for '.$patientName->patient_name.' has been assigned to you.';
                Notifications::addNotification(['user_id'=> $reqData['ccr_id'],
                'title'=>$title,'type'=>'clinician','related_item'=>$patientName->aoc_provider_id,'notes'=>$title]);
            }

            return response()->json(['success' => 1, 'message' => 'Infomation saved successfully']);
        } else {
            return response()->json(['success' => 0, 'message' => 'Something went wrong please try after some time.']);
        }
    }

    public function exportSupervisoryVisitsData(Request $request){
        $dataTableFields = [
            'clinicianName' => 'Clinician Name',
            'DisciplineName' => 'Clinician Discipline',
            'clientName' => 'Client Name',
            'LOBName' => 'LOB',
            'FacilityName' => 'Branch',
            'ccmName' => 'CCM',
            'last_sup_visit' => 'Last Sup Visit',
            'visit_date_due' => 'Next Sup Visit',
            'current_status' => 'Status',
            'completed_by_name' => 'Completed By',
            'completed_date' => 'Date Completed',
            'item1_score' => 'Job Knowledge Score',
            'item2_score' => 'Work Quality Score',
            'item3_score' => 'Dependability Score',
            'item4_score' => 'Professionalism Score',
        ];
        $scoreFieldDisplayValue = [
            4 => '4 - Exceeds Expectations',
            3 => '3 - Meets Expectations',
            2 => '2 - Partially Meets Expectations',
            1 => '1 - Does Not Meet Expectations',
        ];
        try {
            $data = H::getHelper("SupervisoryVisits")->
            getSupervisoryVisits("export",$request->input("filters") ,false,$request);
            $data = json_decode(json_encode($data->toArray()), true);
            $headers = [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=SupervisoryVisitsData.csv",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];
            $callback = function () use ($data, $dataTableFields,$scoreFieldDisplayValue) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_values($dataTableFields));
                foreach ($data as $tableData) {
                    $csvData = [];
                    foreach ($dataTableFields as $key => $val) {
                        if($key == 'item1_score' || $key == 'item2_score'
                        || $key == 'item3_score' || $key == 'item4_score') {
                            if($tableData[$key] != '') {
                                $csvData[] = $scoreFieldDisplayValue[$tableData[$key]];
                            }
                        } else {
                            $csvData[] = $tableData[$key];
                        }
                    }
                    fputcsv($file, $csvData);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            H::ExcelLog('StatusChangeRequestController::exportSupervisoryVisitsData:'.$e->getMessage(),
            'exportSupervisoryVisitsData.log');
        }
    }

    public function completeSupervisoryVisit(Request $request)
    {
        try {
            $data['completed_at'] = date('Y-m-d');
            DB::table('supervisory_visits')
                ->where('id', $request->input('id'))
                ->active()
                ->update($data);
            return response()->json(["status" => true]);
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $id = $request->id;
            if (LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up')) {
                $data = DB::table('supervisory_visits')
                    ->select('mssql_providers.fullName as clinician',
                        DB::raw("concat(mssql_patients.Patient_LastName, ', ', mssql_patients.Patient_FirstName) as client"),
                        'supervisory_visits.visit_date_due', 'supervisory_visits.last_visit',
                        'mssql_providers.id as clinician_id')
                    ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id')
                    ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id')
                    ->where('supervisory_visits.id', '=', $id)
                    ->active('supervisory_visits')
                    ->first();
                $user_name = auth()->user()->name;
                $message = '';
                $message .= '<li>Supervisory visit for ' . $data->clinician . ' with ' . $data->client . ' was deleted by ' . $user_name . '.</li>';
                $message .= '<li>Last Sup Visit: ' . H::getFormatedDate($data->last_visit) . '</li>';
                $message .= '<li>Sup Visit Due: ' . H::getFormatedDate($data->visit_date_due) . '</li>';
                $message .= '<li>Reason: ' . $request->reason . '</li>'; // Corrected typo 'reson' to 'reason'
                $clinicianNoteData = [];
                $clinicianNoteData['entity'] = 'clinician';
                $clinicianNoteData['entity_id'] = $data->clinician_id;
                $clinicianNoteData['notes'] = $message;

                H::getHelper("ActivityNotes")->setActivityNotes($clinicianNoteData);
                DB::table('supervisory_visits')
                    ->where('id', $id)
                    ->active()
                    ->softDelete();
            } else {
                DB::table('supervisory_visits')
                    ->where('id', $id)
                    ->active()
                    ->softDelete();
            }
            return response()->json(['success' => 1]);
        } catch (\Exception $e) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong']);
        }
    }
}
