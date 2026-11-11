<?php

namespace App\Http\Controllers;

use App\Candidates;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\Notes;
use App\Helpers\Notifications;
use App\RecruitingSchedule;
use App\Rules\IsValidPassword;
use App\Schedule;
use App\ScheduleType;
use DataTables;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrientationCoordinatorController extends Controller
{
    public function index()
    {
        //return view('account.dashboard');
    }

    public function getOrientationForm(Request $request) {
		$schedule_type = '';
		$defaultOutcome = '';
		$data = $request->all();
		$ipea_id = $request->tracker_id;
        H::ExcelLog(print_r($data,true),'ocNextStep.log');
		$bucket = $request->bucket;
		$branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $branches = H::arrGroupBy($branches, 'state');
        $ipeaDetails = RecruitingSchedule::where(['id' => $ipea_id])->first();
        if(isset($ipeaDetails->tracker_id)) {
            $tracker_id = $ipeaDetails->tracker_id;
		    $recruiterDetails = MssqlRecruitertracker::find($tracker_id);
        }else{
            return '';  //not sure what to do here . . .  $ipeaDetails should always have a tracker_id but I guess we should allow for exception?
        }
        H::ExcelLog($recruiterDetails->aoc_candidate_id,'ocNextStep.log');
        H::ExcelLog($recruiterDetails->aoc_patient_id,'ocNextStep.log');

		if(!isset($request->bucket)) {
			$tmpStatus = $recruiterDetails->Candidate_Current_Status;
			if(isset(config('constant')['bucketPerStatus'][$tmpStatus])){
				$bucket = config('constant')['bucketPerStatus'][$tmpStatus];
			}
		}
		$providerDetails = [];
		if ($recruiterDetails->aoc_provider_id != '') {
			$providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
		}
		$patientsDetails = [];
		if ($recruiterDetails->aoc_patient_id != '') {
    		$patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
		}
		$branchDetails = [];
		if ($recruiterDetails->aoc_facility_id != '') {
			$branchDetails = MssqlFacilities::find($recruiterDetails->aoc_facility_id);
		}
		$candidateDetails = [];
		if ($recruiterDetails->aoc_candidate_id != '') {
			$candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
		}
		$schedules = [];
		if ($recruiterDetails->id != '') {
			$schedules = Schedule::where('tracker_id', '=', $recruiterDetails->id)->orderBy('created_at', 'desc')->get();
			$latest = [];
			$schedulesNew = [];
			foreach ($schedules as $key => $value) {
				if (!in_array($value->type_id, $latest)) {
					$schedulesNew[] = $value;
					$latest[] = $value->type_id;
				}
			}
			$schedules = $schedulesNew;
		}
		$scheduleType = ScheduleType::all()->toArray();
		usort($scheduleType, fn ($a, $b) => $a['sort_order'] - $b['sort_order']);

		return view('orientation.nextstep', ['tracker_id'=>$tracker_id, 'ipea_id'=>$ipea_id, 'bucket'=>$bucket, 'schedule_type'=>$schedule_type, 'branches'=>$branches, 'recruiterDetails'=>$recruiterDetails, 'providerDetails'=>$providerDetails, 'patientsDetails'=>$patientsDetails, 'scheduleType'=>$scheduleType, 'schedules'=>$schedules, 'branchDetails'=>$branchDetails,'candidateDetails'=>$candidateDetails,'defaultOutcome'=>$defaultOutcome]);
	}

	public function saveOrientationNext(Request $request) {
		$trackerId = $request->tracker_id;
        $ipeaId = $request->ipea_id;
        $candidate_id = 0;
        H::ExcelLog('here','ocOutcomes.log');
        H::ExcelLog(print_r($request->all(),true),'ocOutcomes.log');
        $appointment_date = '';
        $appointment_time = '';
        $originalNote = '';
        if (!empty($request->appointment_date)) {
            $appointment_date = date('Y-m-d', strtotime($request->appointment_date));
        }
        if (!empty($request->appointment_time)) {
            $appointment_time = date('H:i', strtotime($request->appointment_time));
        }
        $gp = floatval(str_replace('%', '', $request->gp)) / 100;
        $scheduleTypes = DB::table('schedule_types')->where('type', $request->schedule_type)->first();
        $scheduleTypes_id = 0;
        if (!empty($scheduleTypes)) {
            $scheduleTypes_id = $scheduleTypes->id;
        }
        $skillSet = '';
        $userId = '';
        $recruiterName = '';
		$activityNote = $request->note;
		$recruitertracker = MssqlRecruitertracker::where('id', $trackerId)->first();
        $originalSchedule = RecruitingSchedule::where(['id' => $ipeaId])->first();
		$userId = $recruitertracker->user_id;
		$recruiterName = $recruitertracker->Recruiter;
		$schedTypes = 1;   //IPEA
		$updateRecruiter = [];
        if ($request->outcome=='Confirmed') {
            //set lunch choice and live_ipea_status
            $scheduleData = [];
            $scheduleData['IPEA_Confirmed'] = 'Yes';
            if(isset($request->lunch_choice)) {
				$scheduleData['IPEA_lunch_preference'] = $request->lunch_choice;
			}
            $scheduleData['updated_at'] = date('Y-m-d H:i:s');
            $scheduleData['updated_by'] = Auth::user()->id;
            RecruitingSchedule::where('id', $ipeaId)->update($scheduleData);
        } elseif (in_array($request->outcome, ['NC/NS', 'Pending Nurse', 'Pending Parent'])) {
    		if ($request->attampted_to_contact == 1) {
				$scheduleData['attempt_count'] = ($originalSchedule->attempt_count + 1);
			}
			$scheduleData['outcome'] = $request->outcome;
			$scheduleData['updated_at'] = date('Y-m-d H:i:s');
			$scheduleData['updated_by'] = Auth::user()->id;
			H::ExcelLog('updating sched: ' . $ipeaId . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
            RecruitingSchedule::where('id', $ipeaId)->update($scheduleData);
		} elseif ($request->outcome == 'Cancelled') {
			$scheduleData['outcome'] = $request->outcome;
			$scheduleData['updated_at'] = date('Y-m-d H:i:s');
			$scheduleData['updated_by'] = Auth::user()->id;
			H::ExcelLog('updating sched: ' . $ipeaId . ' - ' . print_r($scheduleData,true),'updateSchedules.log');
            RecruitingSchedule::where('id', $ipeaId)->update($scheduleData);
			//now update RT table
    		$updateRecruiter['inactive_reason'] = 'Nurse decided not to work for AOC';
			$updateRecruiter['Candidate_Current_Status'] = 'Inactive';
			$updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
			$activityNote = 'Candidate set to Inactive - Nurse decided not to work for AOC';
		} elseif ($request->outcome == 'Schedule Next Step') {
			H::ExcelLog('Schedule Next Step: ' . $request->patient_id,'updateTracker.log');
		} elseif ($request->outcome == 'Rescheduled') {
            $scheduleData = [];
            $scheduleData['appointment_date'] = $appointment_date;
            $scheduleData['appointment_time'] = $appointment_time;
            $scheduleData['ipea_location_id'] = $request->ipea_location_id;
            $scheduleData['updated_at'] = date('Y-m-d H:i:s');
            $scheduleData['updated_by'] = Auth::user()->id;
            RecruitingSchedule::where('id', $ipeaId)->update($scheduleData);
            $updateRecruiter['IPEA_Date'] = $appointment_date;
			$updateRecruiter['IPEA_Time'] = $appointment_time;
            //now insert a note
            $myNote = $request->schedule_type . ' was rescheduled from ' . $originalSchedule->appointment_date . ' at ' . $originalSchedule->appointment_time . ' to ' . $appointment_date . ' at ' . $appointment_time . ' by ' . Auth::user()->name;
            H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker_activity', 'entity_id' => $trackerId, 'tracker_id' => $trackerId, 'notes' => $myNote]);
		}
		//update common fields
		$updateRecruiter['updated_at'] = date('Y-m-d H:i:s');
		$updateRecruiter['updated_by'] = Auth::user()->id;
		//if ($request->note != '' && $request->note != $originalNote) {
		//	$updateRecruiter['Candidate_Notes'] = $request->note;
		//}
		//if the outcome is set to schedule something,  but nothing is scheduled, set status to Need to be scheduled by RA
		if($request->scheduling=='1' && $request->schedule_type==''){
			$updateRecruiter['Candidate_Current_Status'] = 'Need to be Scheduled by RA';
			$updateRecruiter['Status_Changed_Date'] = date('Y-m-d H:i:s');
		}
		H::ExcelLog('updating tracker from oc dashboard: ' . $trackerId . ' - ' . print_r($updateRecruiter,true),'updateTracker.log');
		MssqlRecruitertracker::where('id', $trackerId)->update($updateRecruiter);
        return response()->json(['success'=>1, 'message'=>'Infomation saved successfully', 'linkedIPEA' => $ipeaId, 'candidate_id' => $candidate_id]);
	}

    public function updateProviderBadge(Request $request){
        try {
            $id = $request->input('id');
            if(isset($id)){
                MssqlProviders::find($id)->update(['has_badge'=>1]);
                return response()->json(['status'=>true]);
            }else{
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'ocUpdateBadge.log');
            return response()->json(['status'=>false,'message'=>$e->getMessage()]);
        }
    }
}
