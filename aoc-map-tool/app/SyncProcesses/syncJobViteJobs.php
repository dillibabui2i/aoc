<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Models\JobCategories;
use Illuminate\Support\Facades\DB;
use App\MssqlFacilities as MssqlFacilities;
use App\Helpers\Notifications;

class syncJobViteJobs
{
    /**
     * Sync JobVite Jobs.
     */
    public function syncJobs()
    {
        try {

        
        echo "Sync Jobs from JobVite \n";
        $syncType = 'jobs';

        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            //exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $limit = $batch_info['batch_size'];

        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        //get list of categories to sync

            $categoryList = '';

            $jobCategories = JobCategories::query()
                ->where('is_enabled', true)
                ->get(['category', 'State', 'Type'])
                ->mapWithKeys(function ($item) {
                    return [
                        $item->category => [
                            'state' => $item->State,
                            'label' => $item->Type,
                        ],
                    ];
                })
                ->toArray();

            foreach (array_keys($jobCategories) as $cat) {
                $categoryList .= '&category=' . urlencode($cat);
            }

        //set all records to is_updated = 0
        $updated = DB::table('jobs')->update(['is_updated' => 0]);
        $isData = 1;
        $page = $last_updated_id + 1;
        $page = 1;
        echo 'page: ' . $page . "\n";
        $count = 1;
        while ($isData) {
            $start = (($page - 1) * $limit) + 1;
            $apicall = env('JOBVITE_JOBS_API_URL') . '?start=' . $start . '&count=' . $limit . $categoryList;
            echo $apicall . "\n";
            //exit;
            H::ExcelLog($apicall,'dbJobSync.log');
            $data = $this->getCurl($apicall);
           
            // avove exit added by yagnesh
            if (count($data['requisitions']) > 0) {
                foreach ($data['requisitions'] as $job) {
                    
                    //var_dump($c);
                    //echo "\n";
                    if(isset($job['filledDate'])) {
                        unset($job['filledDate']);
                    }
                    foreach ($job as $key => &$value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                    }
                    //get branch from custom field
                    $branch = 'none';
                    if(isset($job['customField'])){
                        $customFields = json_decode($job['customField'], true);
                        foreach($customFields as $custom){
                            if($custom['fieldCode']=='branch'){
                                $branch = $custom['value'];
                            }
                        }
                    }
                    echo "branch: " . htmlspecialchars($branch, ENT_QUOTES, 'UTF-8') . "\n";
                    $job['branch'] = $branch;
                    //now get state from category
                    $category = $job['category'];
                    $state = 'none';
                    if(isset($jobCategories[$category])) {
                        $state = $jobCategories[$category]['state'];
                    }
                    echo "state: " . htmlspecialchars($state, ENT_QUOTES, 'UTF-8') . "\n";
                    $job['state'] = $state;

                    $uniqueID = $job['eId'];
                    $l = date('c', ($job['lastUpdatedDate'] / 1000));
                    //convert from 2016-09-08T15:46:35-0800 to 09-08-2016T15:46:35-0800
                    $last_updated_date = substr($l, 5, 2) . '-' . substr($l, 8, 2) . '-' . substr($l, 0, 4) . substr($l, 10, 12) . substr($l, 23, 2);
                    $last_updated_id = $uniqueID;
                    if(isset($job['lastUpdatedDate'])) {
                        $job['lastUpdatedDate'] = H::getDateTimeFromTimestamp($job['lastUpdatedDate']);
                    }
                    if(isset($job['closeDate'])) {
                        $job['closeDate'] = H::getDateTimeFromTimestamp($job['closeDate']);
                    }
                    if(isset($job['sentDate'])) {
                        $job['sentDate'] = H::getDateTimeFromTimestamp($job['sentDate']);
                    }
                    H::ExcelLog($uniqueID . ' opened: ' . $job['sentDate'] . ' - last update: ' . $job['lastUpdatedDate'],'dbJobSync.log');
                    $job['is_updated'] = '1';
                    echo 'looking for: ' . htmlspecialchars($uniqueID, ENT_QUOTES, 'UTF-8') . "\n";
                    H::ExcelLog("select * from jobs where binary `eId` = '" . $uniqueID . "'",'dbJobSync.log');
                    $dbRecord = DB::select("select * from jobs where binary `eId` = ?",[$uniqueID]);

                    
                   
                    foreach($job as $jobKey => $jobValue) {
                        
                        if(!in_array($jobKey,$this->availableFieldsInDatabase())) {
                            unset($job[$jobKey]);
                            echo "Removed :" . htmlspecialchars($jobKey, ENT_QUOTES, 'UTF-8');
                        }
                    }

                    if (!empty($dbRecord)) {
                        H::ExcelLog("found - updating",'dbJobSync.log');
                        echo 'Update Record' . "\n";
                        $job['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                        DB::table('jobs')->whereRaw('binary `eId`= ?', [$uniqueID])->update($job);
                    } else {
                        H::ExcelLog("not found - inserting",'dbJobSync.log');
                        echo 'Insert Record' . "\n";
                        $job['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                        $job['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                        DB::table('jobs')->insert($job);
                    }
                    $last_updated_id = $page;
                    H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
                    echo 'Last Updated Date: ' . htmlspecialchars($last_updated_date, ENT_QUOTES, 'UTF-8') . "\n";
                    echo 'Last Page: ' . $page . "\n";
                    
                }
            } else {
                $isData = false;
                echo 'finished with sync because no data is returned' . "\n";
                //in a different process,  we will check all records where is_updated = 0 and either mark deleted or with updated status
                //for now we are going to assume any that didn't get updated are closed . . . until JV helps me figure out how to get the close requisitions
                $update = DB::table('jobs')->where('is_updated', 0)->update(['jobState' => 'Closed']);
            }
            $page++;
            $count++;
            //break;  //unless we need to sync mass data,  we don't need to do multiple pages in one count
            if ($count > 30) {
                echo 'something is wrong - we shouldnt reach 20 counts' . "\n";
                H::ExcelLog('something is wrong - we reached 20 batches','jobSync.log');
                break;
            }
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        } catch (\Exception $e ) {
            H::ExcelLog($e->getMessage(),'jobSyncError.log');
        }
    }

    public function availableFieldsInDatabase(){
        return $data = [
            "agencyAccessEmails",
            "applyFormId",
            "applyLink",
            "bonus",
            "briefDescription",
            "category",
            "branch",
            "state",
            "closeDate",
            "company",
            "companyId",
            "createdBy",
            "createdByJson",
            "description",
            "detailLink",
            "distribution",
            "eId",
            "eeoCategory",
            "evaluationFormName",
            "hiringManagers",
            "hiringManagersJson",
            "internalOnly",
            "jobSource",
            "jobState",
            "lastUpdatedDate",
            "location",
            "locationCity",
            "locationCountry",
            "locationEId",
            "locationPostalCode",
            "locationState",
            "openings",
            "jobLocations",
            "otherLocations",
            "postingType",
            "primaryHiringManager",
            "primaryHiringManagerJson",
            "primaryHiringManagerEmail",
            "primaryRecruiter",
            "primaryRecruiterJson",
            "primaryRecruiterEmail",
            "private",
            "recruiters",
            "requisitionId",
            "sentDate",
            "title",
            "workflow",
            "jobType",
            "region",
            "customField",
            "department",
            "preInterviewFormName",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
            "is_updated",
            "is_deleted",
            "emailLanguage",
            "no_active_nth",
            "no_active_nth_resolved_by",
            "no_active_nth_resolved_on",
            "templateCode"
        ];
    }

    /**
     * get record for specific candidate.
     */
    public function getCandidate($id)
    {
        $apicall = env('JOBVITE_API_URL') . '?candidateId=' . $id;
        echo $apicall . "\n";
        $data = $this->getCurl($apicall,"candidate");

        //var_dump($data);
        if (count($data['candidates']) > 0) {
            foreach ($data['candidates'] as $c) {
                var_dump($c);
                echo "\n";
            }
        }
    }

    public function getCurl($url = 'https://api.jobvite.com/api/v2/job',$type='job')
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, H::getHelper("Jobvite")->getJobviteAuthenticationHeader($type));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function ProcessOpenJobs()
    {
        echo "Check for jobvite jobs that don't have an NTH record  \n";
        $syncType = 'nthJobs';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        H::ExcelLog("starting process to Check for jobvite jobs that don't have an NTH record" . H::getHelper('Sync')->getSessionId(), 'nthJobs.log');
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'nthJobs.log');
          //  exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";
        $limit = $batch_info['batch_size'];
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $rawData = DB::table('jobs')->select('id','title','category','locationCity','requisitionId')->where('jobState', 'Open')->get();
        $batchCount = count($rawData);
        $titles = ['RN / LPN Pediatric Home Health Nurse', 'RN / LVN Pediatric Home Health Nurse','CNA Pediatric Home Health'];
        $titleArray = ['RN / LPN Pediatric Home Health Nurse' => ['PDN','PDN/Respite','Respite','Skilled Nursing'],
                        'RN / LVN Pediatric Home Health Nurse' => ['PDN','PDN/Respite','Respite','Skilled Nursing'],
                        'CNA Pediatric Home Health' => ['CNA','IHSS']];
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);

        DB::table("jobs")->update(["no_active_nth"=>0]);

        foreach ($rawData as $row) {
            $jobId = $row['id'];

            $title = $row['title'];
            $category = $row['category'];
            $state = substr($category,0,2);
            $hub = $row['locationCity'];
            echo $title . ' | ' . $state . ' | ' . $category . ' | ' . $hub . "\n";
            H::ExcelLog($title . ' | ' . $category . ' | ' . $state . ' | ' . $hub, 'nthJobs.log');
            if(in_array($title,$titles)){
                //the job is for a title we are tracking
                $servicesArray = $titleArray[$title];
                H::ExcelLog('searching for: hub = ' . $hub, 'nthJobs.log');
                H::ExcelLog('  services in ' . print_r($servicesArray,true), 'nthJobs.log');

                var_dump($servicesArray);
                echo "\n";
                //build the query to check for a NTH account
                $nthAccount = DB::table('NeedToHire_Accounts')
                        ->join('mssql_patients','mssql_patients.id','NeedToHire_Accounts.aoc_patient_id')
                        ->join('NeedToHire_Hub', function ($join) {
                            $join->on('NeedToHire_Hub.City', '=', 'mssql_patients.Patient_City')
                                 ->on('NeedToHire_Hub.ShortState', '=', 'mssql_patients.Patient_State');
                        })
                        ->where('NeedToHire_Hub.Hub',$hub)
                        ->whereIn('NeedToHire_Accounts.ServicesNeeded', $servicesArray)
                        ->first();
                if(empty($nthAccount)){
                    echo 'No job found for this hub city.  Notifying Admin' . "\n";
                    H::ExcelLog('no job found - notify', 'nthJobs.log');

                    DB::table("jobs")->where("id",$jobId)->update(["no_active_nth"=>1]);

                    //send Natalie a notification and record as note
                    $notificationText = 'Requisition ' . $row['requisitionId'] . ' (' . $title . ') '. ' for ' . $hub . ', ' . $state . ' does not have any active NTH Accounts.';
                    $notification = DB::table('notifications')->select('*')->where('title',$notificationText)->where('type','jobs')
                            ->where('related_item',$jobId)
                            ->where('created_at','>', date('Y-m-d'))->first();
                    if(empty($notification)){
                        Notifications::addNotification($data = ['user_id' => config('constant')['jobNotificationUserId'], 'title' => $notificationText, 'type' => 'jobs', 'related_item' => $jobId]);
                        Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'jobs', 'related_item' => $jobId]);
                        echo $notificationText . ' notification sent to admin' . "\n";
                        H::ExcelLog($notificationText, 'nthJobs.log');
                    }else{
                        echo 'Skipping Notification' . "\n";
                    }
                }else{
                    echo 'NTH Accounts found for : ' . $hub . "\n\n";
                    H::ExcelLog('nth accounts found for ' . $hub, 'nthJobs.log');
                }
            }else{
                echo "job title doesn't match one we are processing" . "\n\n";
                H::ExcelLog('job title not matching', 'nthJobs.log');
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'nthJobs.log');
    }
}
