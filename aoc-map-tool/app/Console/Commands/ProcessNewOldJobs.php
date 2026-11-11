<?php

namespace App\Console\Commands;

use App\Models\JobCategories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\Notifications;

class ProcessNewOldJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:oldjobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process New / Old Jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $apicall = env('JOBVITE_JOBS_API_URL') . '?start=1&jobStatus=Open';
         
        echo $apicall . "\n";
        H::ExcelLog($apicall,'jobOLDNew.log');
        $data = $this->getCurl($apicall);
       
        if(isset($data['requisitions'])) {
            foreach($data['requisitions']  as $job) {
                echo "\n\n";

                $sentDate = strtotime(H::getDateTimeFromTimestamp($job['sentDate']));
                $deadlineDate = strtotime('-30 days');

                $currentJob  = [];
                $updateData = [];
                if($job['category']=='TX: Attendant Care' || $job['category']=='AZ: Therapy' ||
                    $job['category']=='CO: Therapy' || $job['category']=='TX: Therapy' ||
                    $job['category']=='Administrative'){

                        H::ExcelLog('Processing Job :'.$job['requisitionId'] . ' because the category is ' . $job['category'],'jobOLDNewData.log');
                }else{
                    //  "{"status":{"code":201,"messages":[]},"eId":"omoVmfwL"}"
                    if($sentDate < $deadlineDate) {
                        var_dump($job);
                        H::ExcelLog('Processing Job :'.$job['requisitionId'],'jobOLDNewData.log');


                        $newRequisitionId =  $this->getNextAvailableRequisitionId();
                        echo 'New Req ID: ' . htmlspecialchars($newRequisitionId, ENT_QUOTES, 'UTF-8') . "\n";
                        
                        H::ExcelLog('New Available Requisition ID Retrieved with :'.$newRequisitionId,'jobOLDNewData.log');
                        if(!empty($newRequisitionId)) {
                            echo 'Createing a new job' . "\n";
                            H::ExcelLog("Original Job",'jobOLDNewData.log');
                            H::ExcelLog(print_r($job,true),'jobOLDNewData.log');
                            $currentJob  = $job;

                            $currentJob['requisitionId'] = $newRequisitionId;
                            $currentJob['jobState'] = "Open";
                            $currentJob['creatorEmail'] = $currentJob["createdBy"]['email'];

                            unset($currentJob['sentDate']);
                            unset($currentJob['eId']);
                            unset($currentJob['lastUpdatedDate']);
                            unset($currentJob['createdBy']);
                            H::ExcelLog("New Job Request",'jobOLDNewData.log');
                            H::ExcelLog(print_r($currentJob,true),'jobOLDNewData.log');

                            $newJob = json_decode($this->putJobToJobvite($currentJob,"POST"),true);
                            H::ExcelLog("New Job Response",'jobOLDNewData.log');
                            H::ExcelLog(print_r($newJob,true),'jobOLDNewData.log');

                            
                            if(isset($newJob['eId']) && !empty($newJob['eId'])) {
                                //echo "procession close";
                                $updateData['jobState'] = "Closed";
                                $updateData['requisitionId'] = $job['requisitionId'];

                                H::ExcelLog('Old Job : '.$job['requisitionId'].' is Closed and New Job is created with :'.$newRequisitionId,'jobOLDNewData.log');
                                $this->putJobToJobvite($updateData,"PUT");
                                $branch  = '';
                                if(isset($job['customField'])) {
                                    foreach($job['customField'] as $customField) {
                                        if($customField['key']=='Branch' || $customField['fieldCode']=='branch') {
                                            $branch  = $customField['value'];
                                        }
                                    }
                                }
                                $notificationData = [];
                                $notificationData['user_id'] = 15;
                                $notificationData['title'] = 'Job "'.$job['title'].'" for '.$job['locationCity'].' ('.$branch.') has been closed because it is older than 30 days';
                                $notificationData['type'] = 'jobvite';
                                $notificationData['related_item'] = '';
                                $notificationData['notes'] = '';
                                Notifications::addNotificationFromConsole($notificationData);
                                //$this->putJobToJobvite($updateData,"PUT");
                            } else {
                                H::ExcelLog('There is some error creating new job from : '.$job['requisitionId'].' to '.$newRequisitionId,'jobOLDNewData.log');
                                $notificationData = [];
                                $notificationData['user_id'] = 15;
                                $notificationData['title'] = 'There is some error creating new job from : '.$job['requisitionId'].' to '.$newRequisitionId . '. Please notify Excel Software Services.';
                                $notificationData['type'] = 'jobvite';
                                $notificationData['related_item'] = '';
                                $notificationData['notes'] = '';
                                Notifications::addNotificationFromConsole($notificationData);
                            }

                        } else {
                            H::ExcelLog('Process terminated for this job because not able to retrieve new Requisition ID','jobOLDNewData.log');
                            echo 'Process terminated for this job because not able to retrieve new Requisition ID' . "\n";
                        }
                        
                    }
                }
            }
            
        }
       


       return 0;
    }

    public function getCurl($url = 'https://api.jobvite.com/api/v2/job')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, H::getHelper("Jobvite")->getJobviteAuthenticationHeader());
        $result = curl_exec($ch);
        curl_close($ch);
       // var_dump($result);
        return json_decode($result, true);
    }


    public function putJobToJobvite($params,$method)
    {
       
        try{
            $url = env('JOBVITE_JOBS_API_URL');
            $data_json = json_encode($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(array('Content-Type: application/json','Content-Length: ' . strlen($data_json)),H::getHelper("Jobvite")->getJobviteAuthenticationHeader()));
            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
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

    public function getNextAvailableRequisitionId() {

        $cntr = 0;
        while(1) {
            $requisitionId = $this->prepareJobivteRequisitionId($this->getNextRequisitionId());
            if($this->isValidRequisitionId($requisitionId)) {
                return $requisitionId;
            } else {
                H::ExcelLog('Not Available Requisition ID :'.$requisitionId,'jobOLDNewData.log');
                H::ExcelLog('Checking For New Requisition ID','jobOLDNewData.log');
                
            }
            
        }
        /*var_dump($requisitionId);
        if($this->isValidRequisitionId($requisitionId)) {
            echo "if";
            var_dump($requisitionId);
            return $requisitionId;
        } else {
            echo "else";
            H::ExcelLog('Not Available Requisition ID :'.$requisitionId,'jobOLDNewData.log');
            H::ExcelLog('Checking For New Requisition ID','jobOLDNewData.log');
            $this->getNextAvailableRequisitionId();
        }*/
        
    }

    public function isValidRequisitionId($requisitionId) {
        $apicall = env('JOBVITE_JOBS_API_URL') . '?start=1&jobStatus=Open&jobStatus=Closed&jobStatus=Filled&jobStatus=On%20Hold&jobStatus=Awaiting%20Approval&jobStatus=Approved&jobStatus=Rejected&jobStatus=Retracted&jobStatus=Draft&requisitionId='.$requisitionId; 
        $data = $this->getCurl($apicall);
        
        if(isset($data["total"]) &&  $data['total']>0) {
            return 0;
        } else {
            return 1;
        }
    }
    

    public function getNextRequisitionId(){
        $maxValue = DB::table("core_setting")->select("*")->where("opt","jobvite_requisition_counter")->first();
        
        if(empty($maxValue)) {
            DB::table("core_setting")->insert(["opt"=>"jobvite_requisition_counter","val"=>"1"]);
            return 1;
        } else {
             $nextNumber = $maxValue->val+1;
            DB::table("core_setting")->where("opt","jobvite_requisition_counter")->update(["val"=>$nextNumber]);
            return $nextNumber;
        }
    }

    public function prepareJobivteRequisitionId($requisitionId) {
        return $num_str = sprintf("aoc%06d", $requisitionId);

    }

}
