<?php
namespace App\Helpers;
use App\MssqlFacilities;
use App\MssqlPatients;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\BranchLocations as BranchLocations;
use App\Candidates as Candidates;
use App\IPEAAttachment as IPEAAttachment;
use App\MssqlRecruitertracker;
use Mail;

class IpeaEmail
{
    public function __construct(
        protected ?CrossStateHelper $crossStateHelper = null,
        protected ?HtmlReplaceHelper $htmlReplaceHelper = null
    ) {
        $this->crossStateHelper = $crossStateHelper ?? app(CrossStateHelper::class);
        $this->htmlReplaceHelper = $htmlReplaceHelper ?? app(HtmlReplaceHelper::class);
    }

    public function processEmail($request,$trackerId,$isReschedule=false) {


        $locationID = $request->input("ipea_location_id");
        $user = H::getLoggedInUser();

        //get tracker record for use throughout
        if(!empty($trackerId)) {
            $recruiterTracker = MssqlRecruitertracker::find($trackerId);
        }else{
            $recruiterTracker = '';
        }

        $branch = BranchLocations::find($locationID);

        // detect cross state for IPEA and client location
        if (LD::checkFeatureFlag('cross-state-dynamic-email')) {
            $patientsDetails = MssqlPatients::find($request->patient_id);
            $ipeaState = $branch->state ?? '';
            $clientState = config('constant.states.' . $patientsDetails['Patient_State'] ?? '', '');

            if ($this->crossStateHelper->isCrossState($ipeaState, $clientState)) {
                // replace IPEA branch with client branch text
                $clientBranchId = MssqlFacilities::where('FacilityName', $request->branch_location)->value('branchid') ?? '';
                $clientBranch = BranchLocations::find($clientBranchId);
                $ipeaEmailBody = $branch->email_body;
                $branch = $clientBranch;
                $branch->email_body = $this->htmlReplaceHelper->replaceTable($ipeaEmailBody, $branch->email_body);
            }
        }

        $data = [];
        $body = '';
        $subject= '';
        $branchBCC = '';
        $bcc = [];

        if (LD::checkFeatureFlag('send-full-ipeaemail-everytime')) {
            if(!empty($branch->email_body)) {
                $body = $branch->email_body;
                $subject = $branch->email_subject;
            } else {
                $body = view('emailtemplate.ipea');
                $subject = 'New IPEA Created';
            }
        } else {
            if($isReschedule) {

                $branchAddress = '';
                if(isset($branch->address) && !empty($branch->address)) {
                    $branchAddress = $branch->address;
                }
                if(isset($branch->city)  && !empty($branch->city)) {
                    $branchAddress.=', '.$branch->city;
                }

                if(isset($branch->state) && !empty($branch->state)) {
                    $branchAddress.=', '.$branch->state;
                }

                if(isset($branch->zipcode) && !empty($branch->zipcode)) {
                    $branchAddress.=' '.$branch->zipcode;
                }


                $subject = 'Angels of Care Interview and Orientation Details';
                $body = '<div class="main-container">
                <div class="logo">
                    <div class="angel-logo" style="text-align:center;">
                        <img width="50%" src="'.asset('images/blue-logo.png').'" />
                    </div>
                </div>';

                $body.='<div class="content">';
                $body.='<div class="title">[candidates_first_name]</div>';
                $body.= "<div class='title'><p>Your interview with Angels of Care has been rescheduled to [ipea_time] on <strong>[ipea_long_date]</strong> at <strong>".$branchAddress."</strong>.</p><p>If you have not completed the onboarding paperwork please reference the initial email for the link.</p><p>We look forward to seeing you at your interview!</p></div>";
                $body.='<div class="title"><p>[recruiter_signature]</p></div>';
                $body.='</div>';
            } else {
                if(!empty($branch->email_body)) {
                    $body = $branch->email_body;
                    $subject = $branch->email_subject;
                } else {
                    $body = view('emailtemplate.ipea');
                    $subject = 'New IPEA Created';
                }
            }
        }

        H::ExcelLog(print_r($request->all(),true),'testemail.log');
        $candidate_id = $request->input("candidate_id");
        $provider_id = $request->input("provider_id");
        $toEmail = '';
        $recruiterId='';
        $signature = '';
        $raId = '';
        $candidateEmail = '';
        if(!empty($candidate_id)) {
            $candidate = Candidates::find($candidate_id);
            if(isset($candidate->firstName)) {
                $data["candidates_first_name"] = ucwords(strtolower($candidate->firstName));
            } else {
                $data["candidates_first_name"] = "";
            }
            if(isset($candidate->email)){
                $candidateEmail = $candidate->email;
            }
        }elseif(!empty($provider_id)) {
            $tmp = DB::table('mssql_providers')->where('id', $provider_id)->first();
            if (!empty($tmp)) {
                $data["candidates_first_name"] = ucwords(strtolower($tmp->FirstName));
                $candidateEmail = $tmp->External_Email;

            } else {
                $data["candidates_first_name"] = "";
            }
        } else {
            if($recruiterTracker!='') {
                if(!empty($recruiterTracker->aoc_candidate_id)) {
                    $candidate = Candidates::find($recruiterTracker->aoc_candidate_id);
                    if($candidate) {
                        $data["candidates_first_name"] = ucwords(strtolower($candidate->firstName));
                        $candidateEmail = $candidate->email;
                    } else {
                        $data["candidates_first_name"] = '';
                    }
                } else {
                    $data["candidates_first_name"] = '';
                }
            } else {
                $data["candidates_first_name"] = '';
            }
        }

        H::ExcelLog('candidate: ' . $data['candidates_first_name'] . ' - ' . $candidateEmail,'testemail.log');
        //never delete this feature flag!  this should be turned off on staging and all developer environments

            $toEmail = $candidateEmail;
            //get recruiter email for bcc from tracker
            $recruiterId = $recruiterTracker->user_id;
            if($recruiterId!=''){
                $recruiterEmail = H::getHelper("ConstantHelper")->getUserEmailById($recruiterId);
                $signature = H::getHelper("ConstantHelper")->getUserNameById($recruiterId,'signature');
                if($recruiterEmail!='') {
                    $bcc[] = $recruiterEmail;
                }
            }
            //get RA email for bcc from recruiter id
            $raId = H::getRecruiterAdmin($recruiterId,true);
            if($raId!=''){
                $raEmail = H::getHelper("ConstantHelper")->getUserEmailById($raId);
                if($raEmail!='') {
                    $bcc[] = $raEmail;
                }
            }
            $bcc[] = 'rmeeks@angelsofcare.com';
            if (LD::checkFeatureFlag('dallas-add-as-bcc')) {
                if($branch) {
                    if($branch->branch=='Dallas') {
                        $bcc[] = 'mpeiffer@angelsofcare.com';
                    }
                }
            }

            H::ExcelLog('sending to: ' . $toEmail,'testemail.log');
            H::ExcelLog(print_r($bcc,true),'testemail.log');

        if($toEmail==''){
            H::ExcelLog('no email','testemail.log');
        }else{
            if($isReschedule) {
                $data['ipea_long_date'] = $this->getIpeaLongDate(!empty($request->input("appointment_date"))?$request->input("appointment_date"):$request->input("IPEA_Date"));
                if(!empty($request->input('IPEA_Time'))) {
                    $data['ipea_time'] = substr_replace(strtoupper($request->input('IPEA_Time')), ' ' . substr(strtoupper($request->input('IPEA_Time')), -2), -2);
                } else {
                    $data['ipea_time'] = substr_replace(strtoupper($request->input('appointment_time')), ' ' . substr(strtoupper($request->input('appointment_time')), -2), -2);
                }
                $data['recruiter_signature'] = !empty($signature)?$signature:'';
            } else {
                $data['ipea_date'] = !empty($request->input("appointment_date"))?$request->input("appointment_date"):'';
                $data['recruiter_signature'] = !empty($signature)?$signature:'';
                $data['ipea_long_date'] = $this->getIpeaLongDate($request->input("appointment_date"));
                $data['ipea_time'] = substr_replace(strtoupper($request->input('appointment_time')), ' ' . substr(strtoupper($request->input('appointment_time')), -2), -2);
            }


            $skill = 'none';
            if(!empty($trackerId)) {
                $recruiterTracker = MssqlRecruitertracker::find($trackerId);
                if(!empty($recruiterTracker->Skillset)) {
                    $skill = $recruiterTracker->Skillset;
                }
            }
            $data['classmarker_link'] = '<a href="https://www.classmarker.com/online-test/start/user-info/?quiz=fdm627547f55b2b6" target="_blank" rel="noopener">HERE</a>';
			$data['application_link'] = '<a href="https://www.paycomonline.net/v4/ats/web.php/jobs/ViewJobDetails?job=65839&clientkey=354FE5DAF7A486D359BA8000E8268A0C" target="_blank" rel="noopener">HERE</a>';
            if($skill=='CNA') {
                $data['classmarker_link'] = '<a href="https://www.classmarker.com/online-test/start/?quiz=jnr627545ba270de" target="_blank" rel="noopener">HERE</a>';
            }

            if($skill=='CNA' && ($branch->state=='North Carolina' || $branch->state=='South Carolina')) {
                $data['classmarker_link'] = '<a href="https://www.classmarker.com/online-test/start/user-info/?quiz=jnr627545ba270de" target="_blank" rel="noopener">HERE</a>';
            }

            $body = $this->processBody($body,$data);

            $attachments = IPEAAttachment::where("skills","like",'%'.$skill.'%')->whereRaw("FIND_IN_SET(?, facilities)", [$locationID])->get();

            Mail::send([], [], function($message) use($body,$subject,$attachments,$bcc,$toEmail,$isReschedule){
                $message->to($toEmail);
                $message->bcc($bcc);
                $message->subject($subject);
                $message->html($body);
                if(!$isReschedule) {
                    foreach($attachments as $attachment ) {
                        $destinationPath = storage_path() . '/ipeaattachments/'.$attachment->file_path;
                        if(is_file($destinationPath)) {
                            $message->attach($destinationPath, array(
                                'as' => $attachment->file_path)
                            );
                        }
                    }
                }
            });
            $attach = [];
            if(!$isReschedule) {
                foreach($attachments as $attachment ) {
                    $attach[] = $attachment->file_path;
                }
            }
            $notesData = ["to"=>$toEmail,"bcc"=>$bcc,"subject"=>$subject, "body"=>$body, "attachments"=>$attach];
            $notes = json_encode($notesData);
            H::getHelper("ActivityNotes")->setActivityNotes(["entity"=>"email","entity_id"=>$trackerId,"tracker_id"=>$trackerId,"notes"=>$notes]);
        }
    }


   public function processBody($body, $data){

        foreach($data as $key=> $val) {
           $body = str_replace("[".$key."]",$val,$body);
        }
        return $body;
   }

   public function bodyVariables(){
        return ["candidates_first_name","ipea_date","recruiter_signature","ipea_long_date","ipea_time"];
   }

   public function getCandidateFirstName($candidateId){
        $candidate = Candidates::find($candidateId);
        if(isset($candidate->firstName)) {
            return $candidate->firstName;
        } else {
            return "";
        }

   }

   public function getIpeaLongDate($date){
        $date = strtotime($date);
        return date("F d, Y",$date);

   }
}
