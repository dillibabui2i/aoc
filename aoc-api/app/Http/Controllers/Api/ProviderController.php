<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Helper;
use DB;
use App\Models\MssqlProviders;
use App\Models\MssqlPatients;
use App\Models\ExternalUsers;
use Exception;
use Illuminate\Http\Response;

class ProviderController extends Controller
{
    //
    public function getKanTimeSchedule(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        $scheduleStatus = config('constants.schedulestatus');
        $sheduledata = [];
        $currentDate = Carbon::now();
        $futureDate = $currentDate->addMonths(6)->format('Y-m-d');
        try {
            if ($aoc_provider_id) {
                $schedule = DB::table('mssql_schedulemaster')->select('mssql_schedulemaster.*', DB::raw("concat(mssql_patients.Patient_FirstName,' ',
                mssql_patients.Patient_MiddleName,' ',
                mssql_patients.Patient_LastName) as patientFullName"))->join('mssql_patients', 'mssql_patients.PatientID', '=', 'mssql_schedulemaster.PatientID')->leftjoin('mssql_providers', 'mssql_providers.ProviderID', 'mssql_schedulemaster.ProviderID')->where('mssql_providers.id', $aoc_provider_id)->where('mssql_schedulemaster.PlannedStart', '<', $futureDate)->get();
                if ($schedule) {
                    foreach ($schedule as $key => $data) {
                        $sheduledata[$key]['PatientID'] = $data->PatientID;
                        $sheduledata[$key]['patientFullName'] = $data->patientFullName;
                        $sheduledata[$key]['PlannedStart'] = Helper::getDateAndReadableTime($data->PlannedStart);
                        $sheduledata[$key]['PlannedEnd'] = Helper::getDateAndReadableTime($data->PlannedEnd);
                        $sheduledata[$key]['PlannedHours'] = number_format($data->PlannedHours, 2);
                        $sheduledata[$key]['ScheduleStatus'] = $scheduleStatus[$data->ScheduleStatusID];
                    }
                    return Helper::response(config('constants.response_code.OK'), $sheduledata);
                } else {
                    return Helper::response(config('constants.response_code.NO_SCHEDULE'), [], __('messages.no_schedule_available'));
                }
            } else {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
            }
        } catch (\Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getUpcomingAppointments(Request $request)
    {
        $aoc_patient_id = $request->aoc_patient_id;
        $upcoimrArray = [];

        try {

            $upcomingAppointments = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=', 'recruiting_schedule.tracker_id')
                ->select(
                    'schedule_types.type as type',
                    'recruiting_schedule.*',
                    'mssql_recruitertracker.Candidate',
                    'mssql_recruitertracker.Skillset',
                    'mssql_recruitertracker.Schedule')
                ->where('recruiting_schedule.aoc_patient_id', $aoc_patient_id)
                ->whereDate('appointment_date', '>=', date('Y-m-d'))
                ->orderBy('appointment_date', 'asc')
                ->get();

            if (!($upcomingAppointments->isEmpty())) {
                foreach ($upcomingAppointments as $key => $data) {
                    $upcoimrArray[$key]['type'] = $data->type;
                    $upcoimrArray[$key]['date'] = Helper::getFormatedDate($data->appointment_date);
                    $upcoimrArray[$key]['time'] = Helper::getFormatedTime($data->appointment_time);
                    $upcoimrArray[$key]['clinician'] = $data->Candidate;
                    $upcoimrArray[$key]['skillset'] = $data->Skillset;
                    $upcoimrArray[$key]['Schedule'] = $data->Schedule;
                }
                return Helper::response(config('constants.response_code.OK'), ['upcomingappointments' => $upcoimrArray]);
            } else {
                return Helper::response(config('constants.response_code.OK'), [], __('messages.patient_no_upcoming_appointments'));
            }
        } catch (\Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function submitFormRequest(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        try {
            $providerDetails = MssqlProviders::getProviderDetails($aoc_provider_id, ['mssql_providers.id', 'mssql_providers.ccm_id', DB::raw("CONCAT(mssql_providers.FirstName,' ',IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),mssql_providers.LastName) as fullName")]);
            if ($providerDetails == NULL) {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.invalid_details'));
            }
            $selectedPatientName = $providerDetails->fullName;
            $userDetail = ExternalUsers::find($request->id);
            $requestData['aoc_provider_id'] = $aoc_provider_id;
            $noteEntity = 'NoteByApi';
            $miscellaneous = $notificationNotes = $notificationTitle ='';
            if ($request->request_type == 'reportHospitalization') {
                $patientDetails = MssqlPatients::getPatientDetails($request->patient_id, ['mssql_patients.id', 'mssql_patients.ccc_id', DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),mssql_patients.Patient_LastName) as patientFullName")]);
                $selectedPatientName = $patientDetails->patientFullName;
                //Note
                $newNoteDetailStr = '';
                $newNoteDetailStr = $newNoteDetailStr . 'From Date:' . $request->from_date . "<br>";
                $newNoteDetailStr = $newNoteDetailStr . 'To Date:' . $request->to_date;
                if ($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr . "<br>";
                    $newNoteDetailStr = $newNoteDetailStr . 'Comment:' . $request->comment;
                }
                $notes = $notificationTitle =  $userDetail->name . ' has reported hospitalization for patient ' . $selectedPatientName . ' for following dates';
                $notes = $notes . "<br> " . $newNoteDetailStr;
                $noteEntity = 'Hospitalization';
                $responseMessage  = __('messages.hospitalization_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif ($request->request_type == 'callRequest') {
                //Note
                $newNoteDetailStr = '';
                $newNoteDetailStr = $newNoteDetailStr . 'between ' . Helper::getDateAndReadableTime($request->from_time) . " - " . Helper::getDateAndReadableTime($request->to_time) . "<br>";
                $newNoteDetailStr = $newNoteDetailStr . 'Days:' . $request->days . "<br>";
                $newNoteDetailStr = $newNoteDetailStr . 'Call Type:' . $request->call_type;
                if ($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr . "<br>";
                    $newNoteDetailStr = $newNoteDetailStr . 'Comment:' . $request->comment;
                }
                $notes =  $notificationTitle = $userDetail->name . ' has requested a call';
                $notes = $notes . " " . $newNoteDetailStr;
                $noteEntity = 'callRequest';
                $responseMessage  = __('messages.call_request_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif ($request->request_type == 'submitHalos') {
                $notes = $request->shoutout;
                $noteEntity = 'halos';
                $responseMessage  = __('messages.halos_submitted');
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $type = $image->getClientOriginalExtension();
                    $imageContents = file_get_contents($image->getRealPath());
                    $miscellaneous = 'data:image/' . $type . ';base64,' . base64_encode($imageContents);
                }
            } elseif ($request->request_type == 'submitDemographic') {
                //Note
                $newNoteDetailStr = '';
                $notes = $notificationTitle = $userDetail->name . ' wants to change following demographic';
                $notes = $notes . "<br>" . $request->details;
                $noteEntity = 'submitDemographic';
                $responseMessage  = __('messages.demographic_change_submitted');
                $notificationNotes = $newNoteDetailStr;
            } elseif ($request->request_type == 'referFriend') {
                //Note
                $newNoteDetailStr = '';
                $newNoteDetailStr = $newNoteDetailStr . 'Name ' . $request->name;
                if ($request->email != '') {
                    $newNoteDetailStr = $newNoteDetailStr . "<br>";
                    $newNoteDetailStr = $newNoteDetailStr . 'Email:' . $request->email;
                }
                if ($request->phone != '') {
                    $newNoteDetailStr = $newNoteDetailStr . "<br>";
                    $newNoteDetailStr = $newNoteDetailStr . 'Phone:' . $request->phone;
                }
                if ($request->comment != '') {
                    $newNoteDetailStr = $newNoteDetailStr . "<br>";
                    $newNoteDetailStr = $newNoteDetailStr . 'Comment:' . $request->comment;
                }
                $notes =  $notificationTitle =  $userDetail->name . ' referred a friend with following details';
                $notes = $notes . "<br>" . $newNoteDetailStr;
                $noteEntity = 'referFriend';
                $responseMessage  = __('messages.friends_details_sent');
                $notificationNotes = $newNoteDetailStr;
            } elseif ($request->request_type == 'supplyRequest') {
                $patientDetails = MssqlPatients::getPatientDetails($request->patient_id, ['mssql_patients.id', 'mssql_patients.ccc_id', DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),mssql_patients.Patient_LastName) as patientFullName")]);
                $selectedPatientName = $patientDetails->patientFullName;
                //Note
                $newNoteDetailStr = '';
                $cnt = 0;
                foreach ($request->items as $v) {
                    $newNoteDetailStr .= 'Item: ' . $v['item'] . '<br>';
                    $newNoteDetailStr .= 'Description: ' . $v['description'] . '<br>';
                    $newNoteDetailStr .= 'Qty: ' . $v['qty'];
                    $cnt++;
                    if ($cnt != count($request->items)) {
                        $newNoteDetailStr .= '<br><br>--<br>';
                    }
                }
                $notes = $notificationTitle =  $userDetail->name . ' has requested for following supplies for patient ' . $selectedPatientName;
                $notes = $notes . "<br>" . $newNoteDetailStr;
                $noteEntity = 'supplyRequest';
                $responseMessage  = __('messages.supply_request_sent');
                $miscellaneous = json_encode($request->items);
                $notificationNotes = $newNoteDetailStr;
            } elseif ($request->request_type == 'submitPRNDate') {
                $selectedPatientName = '';
                if($request->patient_id > 0) {
                    $patientDetails = MssqlPatients::getPatientDetails($request->patient_id, ['mssql_patients.id', 'mssql_patients.ccc_id', DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),mssql_patients.Patient_LastName) as patientFullName")]);
                    $selectedPatientName = $patientDetails->patientFullName;
                }
                //Note
                $newNoteDetailStr = '';
                $cnt = 0;
                foreach ($request->dates as $v) {
                    if($cnt == 0) {
                        $newNoteDetailStr .= 'PRN Dates';
                    }
                    $newNoteDetailStr .= Helper::getFormatedDate($v) . '<br>';
                    $cnt++;
                }
                if($selectedPatientName != '') {
                    $notes = $notificationTitle = $userDetail->name . ' has submitted for following PRN dates for patient ' . $selectedPatientName;
                } else {
                    $notes = $notificationTitle = $userDetail->name . ' has submitted for following PRN dates';
                }
                $notes = $notes . "<br>" . $newNoteDetailStr;
                $noteEntity = 'submitPRNDate';
                $responseMessage  = __('messages.prn_dates_request_sent');
                $miscellaneous = json_encode($request->items);
                $notificationNotes = $newNoteDetailStr;
            }
            //if ccm id is null in provider - use 15 as ccm_id - discussed with Lynn on 11 Oct 2023 - dev support hours
            $ccm_id = ($providerDetails->ccm_id != NULL)?$providerDetails->ccm_id:15;
            $notesData = ['entity' => $noteEntity, 'entity_id' => $requestData['aoc_provider_id'], 'notes' => $notes, 'completed' => 0, 'created_by' => $requestData['aoc_provider_id'], 'assigned_to' => $ccm_id, 'misc4' => $miscellaneous, 'misc1' => 'mobileapp'];
            Helper::setActivityNotes($notesData);

            if($notificationNotes !='' && $notificationTitle !='') {
                $notificationData = ['type' => 'candidate', 'related_item' => $requestData['aoc_provider_id'], 'notes' =>$notificationNotes,'title' => $notificationTitle,'created_by' => $requestData['aoc_provider_id'],'user_id' => $ccm_id];
                Helper::addNotification($notificationData);
            }
            return Helper::response(config('constants.response_code.OK'), [], $responseMessage);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clinicianDetails(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        try {
            $providerDetails = MssqlProviders::find($aoc_provider_id);
            $email = $providerDetails->AOC_Email ?? '';
            $phone = $providerDetails->Phone ?? '';
            $mobile = $providerDetails->Mobile ?? '';
            $address = implode(', ', array_filter([
                $providerDetails->Address1,
                $providerDetails->Address2,
                $providerDetails->City,
                $providerDetails->State,
                $providerDetails->Zip
            ]));
            $data = [
                'Address' => $address,
                'Email' => $email,
                'Phone' => $phone,
                'Mobile' => $mobile
            ];
            return Helper::response(config('constants.response_code.OK'), ['cliniciandetail' => $data]);
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getSupplyRequest(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        try {
            $patientList = DB::table('mssql_patients')->select(DB::raw('DISTINCT(mssql_patients.id)'))->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID',
                        '=', 'mssql_patients.Patient_CurrentStatusID')->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                        ->leftJoin('lob', 'lob.LOBID', '=', 'mssql_patients.LOBID')->leftJoin('mssql_schedulemaster', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
                        ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')->where('mssql_schedulemaster.ScheduleDate','>=',date('Y-m-d'))->where("mssql_providers.id",'=',$aoc_provider_id)->get()->toArray();
            $patientIdList = array_column($patientList, 'id');
            $query = DB::table('supply_requests')
                ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'supply_requests.aoc_patient_id')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->leftJoin('users as userForCompletedBy', 'userForCompletedBy.id', '=', 'supply_requests.completed_by')
                ->select('supply_requests.items as items', DB::raw("DATE_FORMAT(supply_requests.created_at, '" . config('constants')['mysqlsearchdatetimeformat'] . "') as created_at"), DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS client"));
            $query->whereIn("mssql_patients.id",$patientIdList)->orderBy('created_at','DESC');
            Helper::ExcelLog('List: ' . $query->toSql(), 'SuppliesRequest.log');
            $data = $query->get();
        return Helper::response(config('constants.response_code.OK'), ['supplyrequest' => $data]);
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function getPrnDays(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        $response = [];
        try {
            $query = DB::table('mssql_recruitertracker')->select('prn_first_schedule','aoc_patient_id')->where("mssql_recruitertracker.aoc_provider_id",$aoc_provider_id)->where("mssql_recruitertracker.aoc_patient_id",'>',0);
            Helper::ExcelLog('List: ' . $query->toSql(), 'SuppliesRequest.log');
            $data = $query->get();
            $patientList = array_unique(array_column( json_decode(json_encode($data)), 'aoc_patient_id'));
            $patientListWithName = [];
            foreach($patientList as $patientId) {
                $patientDetails = MssqlPatients::getPatientDetails($patientId, ['mssql_patients.id', 'mssql_patients.ccc_id', DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),mssql_patients.Patient_LastName) as patientFullName")]);
                $patientListWithName[$patientId] = $patientDetails->patientFullName;
            }
            $dateList = $patientDateList = [];
            foreach($data as $dataTemp) {
                $dateList[$dataTemp->aoc_patient_id] = $dataTemp;
            }
            foreach($dateList as $key =>$dateListTemp) {
                if($dateListTemp->prn_first_schedule != '') {
                    $dateArray = json_decode($dateListTemp->prn_first_schedule);
                    foreach($dateArray  as $dateVal) {
                        $patientDateList[$patientListWithName[$key]][] = Helper::getFormatedDate($dateVal);
                    }
                }
            }
            if(count($patientDateList) > 0) {
                return Helper::response(config('constants.response_code.OK'), [$patientDateList]);
            } else {
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_record_available'));
            }
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }


    public function getExpiringRenewables(Request $request)
    {
        $aoc_provider_id = $request->aoc_provider_id;
        $data = [];
        try {
            $query = DB::table('renewables')->select('renewables.id','renewables_types.name',DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%Y') as expiration_date"))->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.renewable_type_id');
            $query->whereRaw('renewables.expiration_date >= CURDATE() AND renewables.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 120 DAY)')->where("aoc_provider_id",$aoc_provider_id)->orderBy('renewables.created_at','DESC');
            Helper::ExcelLog('List: ' . $query->toSql(), 'SuppliesRequest.log');
            $data = $query->get();
            if(count($data) > 0) {
                return Helper::response(config('constants.response_code.OK'), $data);
            } else {
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_record_available'));
            }
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
    //Used for HH
    public function providerList(Request $request)
    {
        try {
            $data = [
            [
                'id' => 1,
                'physician_name' => "Physician Name",
                'npi' => "NPI",
                'email' => "Email",
                'city' => "City",
                'state' => "State",
                'zip' => "Zip",
                'cell' => "Cell",
                'phone' => "Phone",
                'fax' => "Fax",
                'pecos' => "PECOS",
                'order_delivery_method' => "Order Delivery Method",
            ],[
                'id' => 2,
                'physician_name' => "Physician Name",
                'npi' => "NPI 2",
                'email' => "Email 2",
                'city' => "City 2",
                'state' => "State 2",
                'zip' => "Zip 2",
                'cell' => "Cell 2",
                'phone' => "Phone 2",
                'fax' => "Fax 2",
                'pecos' => "PECOS 2",
                'order_delivery_method' => "Order Delivery Method 2",
            ]
            ];
            return Helper::response(config('constants.response_code.OK'), ['provider' => $data]);
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    //Used for HH
    public function providerDetails(Request $request)
    {
        try {
            $data = [
                'id' => 1,
                'physician_name' => "Physician Name",
                'npi' => "NPI",
                'email' => "Email",
                'city' => "City",
                'state' => "State",
                'zip' => "Zip",
                'cell' => "Cell",
                'phone' => "Phone",
                'fax' => "Fax",
                'pecos' => "PECOS",
                'order_delivery_method' => "Order Delivery Method",
            ];
            return Helper::response(config('constants.response_code.OK'), ['providerdetail' => $data]);
        } catch (Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
}
