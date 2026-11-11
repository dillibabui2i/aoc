<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Candidates as Candidates;
use Carbon\Carbon;
use GuzzleHttp\HandlerStack;
use \GuzzleHttp\Client;

class syncCandidateToJobvite
{
    private bool $mapTouched = false;
    /**
     * Sync Providers.
     */
    public function syncCandidate(){
            $candidates = Candidates::where("syncToJobVite",1)->get();
            if($candidates->count()>0) {
            
                foreach($candidates as $candidate) {
                    //var_dump($candidate);
                    //H::ExcelLog(print_r($candidate,true),'syncToJobvite.log');
                    $eId = $candidate->eId;
                    echo 'id: ' . $eId . "\n";
                    $jobviteCandidate = H::getHelper('Jobvite')->getCandidateFromJobvite($eId);
                    var_dump($jobviteCandidate);
                    echo "\n";
                    if(isset($jobviteCandidate['eId'])){
                        $req = [];
                        $req2 = [];

                        echo $jobviteCandidate['firstName'] . ' - ' . $candidate->firstName . "\n";
                        echo $jobviteCandidate['application']['workflowState'] . ' - ' . $candidate->appWorkflowState . "\n";
                        H::ExcelLog($candidate->firstName . ' ' . $candidate->lastName ,'syncToJobvite.log');
                        if($jobviteCandidate['firstName']!=$candidate->firstName){
                            $req['candidate']['firstName'] = $candidate->firstName;
                            H::ExcelLog('update firstName from: ' . $jobviteCandidate['firstName'] . ' to: ' . $candidate->firstName,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['lastName']!=$candidate->lastName){
                            $req['candidate']['lastName'] = $candidate->lastName;
                            H::ExcelLog('update lastName from: ' . $jobviteCandidate['lastName'] . ' to: ' . $candidate->lastName,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['title']!=$candidate->title){
                            $req['candidate']['title'] = $candidate->title;
                            H::ExcelLog('update title from: ' . $jobviteCandidate['title'] . ' to: ' . $candidate->title,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['homePhone']!=$candidate->homePhone){
                            $req['candidate']['homePhone'] = $candidate->homePhone;
                            H::ExcelLog('update homePhone from: ' . $jobviteCandidate['homePhone'] . ' to: ' . $candidate->homePhone,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['mobile']!=$candidate->mobile){
                            $req['candidate']['mobile'] = $candidate->mobile;
                            H::ExcelLog('update mobile from: ' . $jobviteCandidate['mobile'] . ' to: ' . $candidate->mobile,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['address']!=$candidate->address){
                            $req['candidate']['address'] = $candidate->address;
                            H::ExcelLog('update address from: ' . $jobviteCandidate['address'] . ' to: ' . $candidate->address,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['address2']!=$candidate->address2){
                            $req['candidate']['address2'] = $candidate->address2;
                            H::ExcelLog('update address2 from: ' . $jobviteCandidate['address2'] . ' to: ' . $candidate->address2,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['city']!=$candidate->city){
                            $req['candidate']['city'] = $candidate->city;
                            H::ExcelLog('update city from: ' . $jobviteCandidate['city'] . ' to: ' . $candidate->city,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['state']!=$candidate->state){
                            $req['candidate']['state'] = $candidate->state;
                            H::ExcelLog('update state from: ' . $jobviteCandidate['state'] . ' to: ' . $candidate->state,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['postalCode']!=$candidate->postalCode){
                            $req['candidate']['postalCode'] = $candidate->postalCode;
                            H::ExcelLog('update postalCode from: ' . $jobviteCandidate['postalCode'] . ' to: ' . $candidate->postalCode,'syncToJobvite.log');
                        }
                        if($jobviteCandidate['application']['workflowState']!=$candidate->appWorkflowState){
                            $req['candidate']['application']['workflowState'] = $candidate->appWorkflowState;
                            $disposition = '';
                            if(strtolower($candidate->appWorkflowState)=='rejected by agency') {
                                $disposition = 'Resume Reviewed- Attempted to Contact';
                            }
                            if($candidate->appWorkflowState=='Offer Rejected') {
                                $disposition = 'Candidate Withdrew';
                            }
                            if($candidate->appWorkflowState=='IPEA Scheduled') {
                                $disposition = 'IPEA Pending';
                            }
                            if($candidate->appWorkflowState=='Recruiter Phone Screen') {
                                $disposition = 'Resume Reviewed- Attempted to Contact';
                            }
                            if($disposition!='') {
                                $req['candidate']['application']['disposition'] = $disposition;
                            }

                            $req['candidate']['application']['eId'] = $jobviteCandidate['application']['eId'];
                            echo 'update workflowState from: "' . $jobviteCandidate['application']['workflowState'] . '" to: "' . $candidate->appWorkflowState . '" - ' . $disposition . "\n";
                            H::ExcelLog('update workflowState from: "' . $jobviteCandidate['application']['workflowState'] . '" to: "' . $candidate->appWorkflowState . '" - ' . $disposition,'syncToJobvite.log');
                        }
                        $candidateUpdate = Candidates::find($candidate->id);
                        if($req != $req2){
                            //only update to jobvite if something actually changed.  But we still set syncToJobVite back to 0 below
                            //add email because that's what jobvite uses to match for updates
                            $req['candidate']['email'] = $candidate->jobvite_email;
                            if($candidate->jobvite_email!=''){
                                var_dump($req);
                                H::ExcelLog('send to jv: ' . print_r($req,true),'syncToJobvite.log');
                                $result = $this->putCandidateToJobvite($req);
                                H::ExcelLog(print_r($result,true),'syncToJobvite.log');
                                var_dump($result);
                                /*
                                                                  echo 'Message: ' .$e->getMessage();
                                  $data = [];
                                  $data['user_id'] = 12;
                                  $data['title'] = 'Error updating Jobvite for ' . $candidate->firstName . ' ' . $candidate->lastName . ': ' . $e->getMessage();
                                  $data['type'] = 'candidate';
                                  $data['related_item'] = $candidate->id;
                                  $data['notes'] = '';
                                  Notifications::addNotification($data);
                                  $data['user_id'] = 1;
                                  Notifications::addNotification($data);                                  

                                  */
                                $candidateUpdate->updatedToJobVite=Carbon::now();
                            }else{
                                H::ExcelLog('skip because email is empty: ' . print_r($req,true),'syncToJobvite.log');
                            }
                        }
                        $candidateUpdate->syncToJobVite=0;
                        $candidateUpdate->save();
                        $this->mapTouched = true;
                    }else{
                        H::ExcelLog('ERROR - not found in jobvite!!!!!!','syncToJobvite.log');
                    }
                }
            }

        if($this->mapTouched) {
            event(new MapUpdatedEvent('candidates'));
        }
    }

    public function putCandidateToJobvite($params)
    {
        /*try{
            $apicall = env('JOBVITE_API_URL') . '/candidate?api=' . env('JOBVITE_API_KEY') . '&sc=' . env('JOBVITE_SECRET_KEY');
            $response = $this->jobviteApi()->request("PUT",$apicall,["json"=> $params]); 
            return $response;
        }catch(GuzzleHttp\Exception\ClientException $e){
            echo 'ERROR';
            return 'error';
        }*/
        try{
            $url = env('JOBVITE_API_URL') . '/candidate';
            $data_json = json_encode($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
           // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(array('Content-Type: application/json','Content-Length: ' . strlen($data_json)),H::getHelper("Jobvite")->getJobviteAuthenticationHeader("candidate")));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            curl_close($ch);
            var_dump($response);
            H::ExcelLog(print_r($response,true),'syncToJobvite.log');
            if (!$response) 
            {
                return false;
            }else{
                return $response;
            }
        }catch(\Exception $e){
            H::ExcelLog('ERROR: ' . $e->getMessage(),'syncToJobvite.log');
            return false;
        }
    }

    public function jobviteApi(){
		$client = new Client([
            'headers' => [ 'Content-Type' => 'application/json' ]
		]);
		return $client;
	}

}
