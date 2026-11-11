<?php

namespace App\SyncProcesses;

use App\Enums\JobviteTherapyOutcomeEnum;
use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use App\Models\JobCategories;
use App\MssqlFacilities as MssqlFacilities;
use Illuminate\Support\Facades\DB;
use App\Helpers\Notifications;

class syncJobViteCandidates
{
    private bool $mapTouched = false;

    /**
     * Sync JobVite Candidates.
     */
    public function syncCandidates()
    {
        echo "Sync Candidates from JobVite \n";
        $syncType = 'candidates';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
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
        //get job categories to sync

        $categoryList = JobCategories::query()
            ->where('is_enabled', true)
            ->pluck('category')
            ->map(fn($c) => preg_replace('/\s+/', ' ', trim($c)))
            ->toArray();

        if(empty($categoryList)){
            echo 'Skipping because there are no job categories sety';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }

            $apicall = env('JOBVITE_API_URL') . '/candidate?datestart=' . $last_updated_date . '&count=' . $limit;
            echo $apicall . "\n";

            $apicall = str_replace('+', '%2b', $apicall);
            //$apicall = env("JOBVITE_API_URL").'/candidate?api='.env("JOBVITE_API_KEY").'&sc='.env("JOBVITE_SECRET_KEY").'&start='.$start.'&count='.$limit;
            echo $apicall . "\n";
            $data = $this->getCurl($apicall);

            //var_dump($data);
            if (count($data['candidates']) > 0) {
                foreach ($data['candidates'] as $c) {
                    var_dump($c);
                    echo "\n";
                    $row = [];
                    $app = $c['application'];
                    $l = date('c', ($app['lastUpdatedDate'] / 1000));
                    $last_updated_date = substr($l, 5, 2) . '-' . substr($l, 8, 2) . '-' . substr($l, 0, 4) . substr($l, 10, 12) . substr($l, 23, 2);
                    //If there is no job,  skip processing
                    if (isset($app['job'])) {
                        $job = $app['job'];
                    }

                    if (isset($job['eId'])) {
                        $categoryNameNormalized = preg_replace('/\s+/', ' ', trim($job['categoryName']));

                        if (isset($job['categoryName']) && in_array($categoryNameNormalized, $categoryList)) {
                            H::ExcelLog($c['firstName'] . ' ' . $c['lastName'] . ' - ' . $last_updated_date,'candidateSync.log');
                            H::ExcelLog(print_r($job,true),'candidateSync.log');

                            if (isset($c['address'])){
                                $row['address'] = $c['address'];
                            }else{
                                $row['address'] = '';
                            }
                            if (isset($c['address2'])) {
                                $row['address2'] = $c['address2'];
                            }
                            if (isset($c['stateName'])){
                                $row['stateName'] = $c['stateName'];
                            }else{
                                $row['stateName'] = '';
                            }
                            if (isset($c['city'])){
                                $row['city'] = $c['city'];
                            }else{
                                $row['city'] = '';
                            }

                            if (isset($c['companyName'])) {
                                $row['companyName'] = $c['companyName'];
                            }
                            if (isset($c['country'])) {
                                $row['country'] = $c['country'];
                            }
                            if (isset($c['countryCode'])) {
                                $row['countryCode'] = $c['countryCode'];
                            }
                            if (isset($c['countryname'])) {
                                $row['countryName'] = $c['countryName'];
                            }

                            $row['eId'] = $c['eId'];
                            if (isset($c['email'])) {
                                $row['jobvite_email'] = $c['email'];
                            }
                            if (isset($c['firstName'])) {
                                $row['firstName'] = $c['firstName'];
                            }
                            if (isset($c['homePhone'])) {
                                $row['homePhone'] = $c['homePhone'];
                            }
                            if (isset($c['lastName'])) {
                                $row['lastName'] = $c['lastName'];
                            }

                            if (isset($c['location'])) {
                                $row['location'] = $c['location'];
                            }
                            if (isset($c['mobile'])) {
                                $row['mobile'] = $c['mobile'];
                            }
                            if (isset($c['postalCode'])) {
                                $row['postalCode'] = $c['postalCode'];
                            }
                            if (isset($c['state'])) {
                                $row['state'] = $c['state'];
                            }
                            if (isset($c['stateCode'])) {
                                $row['stateCode'] = $c['stateCode'];
                            }
                            if (isset($c['title'])) {
                                $row['title'] = $c['title'];
                            }
                            if (isset($c['workPhone'])) {
                                $row['workPhone'] = $c['workPhone'];
                            }
                            if (isset($c['workStatus'])) {
                                $row['workStatus'] = $c['workStatus'];
                            }

                            //get job
                            $jobEid = 'none';
                            $job_id = 0;
                            echo '****************************************' . "\n";
                            echo htmlspecialchars($job['eId'], ENT_QUOTES, 'UTF-8') . "\n";
                            if(isset($job['eId'])){
                                $jobEid = $job['eId'];
                                H::ExcelLog("select * from jobs where binary `eId` = '"
                                    . $jobEid . "'",'candidateJobErrors.log');
                                $jobRecord = DB::select("select * from jobs where binary `eId` = ?", [$jobEid]);
                                if (!empty($jobRecord) && isset($jobRecord[0]->id)) {
                                    $job_id = $jobRecord[0]->id;
                                }
                            }
                            //if no address on candidate application,  use city and state from the job they applied to
                            if($row['city']=='' && $row['address']=='' && $row['stateName']==''){
                                H::ExcelLog("city, address, and stateName are all blank - try to set address from jobvite",'candidateSync.log');
                                if(isset($jobRecord[0]->locationCity) && isset($jobRecord[0]->locationState)){
                                    H::ExcelLog("job locationCity and locationState exist, so set them",'candidateSync.log');
                                    $row['city'] = $jobRecord[0]->locationCity;
                                    $row['stateName'] = $jobRecord[0]->locationState;
                                }
                            }
                            //get branch from job
                            $branch = '';
                            $customFields = $job['customField'];
                            var_dump($customFields);
                            foreach($customFields as $custom){
                                if($custom['fieldCode']=='branch'){
                                    $branch = $custom['value'];
                                }
                            }
                            echo "branch: " . htmlspecialchars($branch, ENT_QUOTES, 'UTF-8') . "\n";
                            if (isset($job['categoryName'])) {
                                $row['jobCategoryName'] = $job['categoryName'];
                            }
                            if (isset($job['department'])) {
                                $row['jobDepartment'] = $job['department'];
                            }
                            if (isset($job['eId'])) {
                                $row['jobId'] = $job['eId'];
                            }
                            //get recruiter from application
                            if (isset($job['primaryRecruiter']["userName"])) {
                                echo htmlspecialchars($job['primaryRecruiter']["userName"], ENT_QUOTES, 'UTF-8') . "-----------------------------------------------------------------------------------------------\n";
                                H::ExcelLog('primaryRecruiter: ' . $job['primaryRecruiter']["userName"],'candidateSync.log');
                                $userId = H::getHelper('ConstantHelper')->getUserByEmail($job['primaryRecruiter']["userName"]);
                                H::ExcelLog('primaryRecruiter UserID: ' . $userId,'candidateSync.log');
                                echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') . "\n";
                                $row['user_id'] = $userId;
                            }
                            if (isset($job['title'])) {
                                $row['jobTitle'] = $job['title'];
                            }
                            if (isset($app['eId'])) {
                                $row['appId'] = $app['eId'];
                                $applicationID = $row['appId'];
                            }else{
                                H::ExcelLog('No Application eId','candidateSync.log');
                                $applicationID = '';
                            }
                            //if (isset($app['jobviteChannel'])) $row['appJobViteChannel'] = $app['jobviteChannel'];
                            //if (isset($app['lastUpdatedDate'])) $row['appLastUpdatedDate'] = H::getDateTimeFromTimestamp($app['lastUpdatedDate']);
                            //if (isset($app['personalDataProcessingStatus'])) $row['appPersonalDataProcessingStatus'] = $app['personalDataProcessingStatus'];
                            //if (isset($app['race'])) $row['appRace'] = $app['race'];
                            if (isset($app['sentDate'])){
                                $row['appSentDate'] = H::getDateTimeFromTimestamp($app['sentDate']);
                                $row['application_date'] = substr($row['appSentDate'],0,10);
                            }
                            //if (isset($app['source'])) $row['appSource'] = $app['source'];
                            //if (isset($app['sourceType'])) $row['appSourceType'] = $app['sourceType'];
                            //if (isset($app['startDate'])) $row['appStartDate'] = $app['startDate'];
                            //if (isset($app['veteranStatus'])) $row['appVeteranStatus'] = $app['veteranStatus'];
                            $appWorkflowState = '';
                            if (isset($app['workflowState'])){
                                $row['appWorkflowState'] = $app['workflowState'];
                                $appWorkflowState = $app['workflowState'];
                            }
                            //convert from 2016-09-08T15:46:35-0800 to 09-08-2016T15:46:35-0800
                            $l = date('c', ($app['lastUpdatedDate'] / 1000));
                            $uniqueID = $c['eId'];
                            $row['fullName'] = $c['firstName'] . ' ' . $c['lastName'];
                            echo htmlspecialchars($c['firstName'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($c['lastName'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($l, ENT_QUOTES, 'UTF-8') . "\n";
                            $last_updated_date = substr($l, 5, 2) . '-' . substr($l, 8, 2) . '-' . substr($l, 0, 4) . substr($l, 10, 12) . substr($l, 23, 2);
                            $last_updated_id = $uniqueID;

                            echo 'looking for candidate: ' . htmlspecialchars($uniqueID, ENT_QUOTES, 'UTF-8') . "\n";
                            $dbRecord = DB::select("select * from candidates where binary `eId` = ?",[$uniqueID]);
                            $candidate_id = '';
                            //Set is_ignore value
                            $is_ignored = 0;
                            $formatPhoneNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($row['mobile']);
                            $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$formatPhoneNumber)->first();
                            if(!empty($twilioErrorData)) {
                                $is_ignored = 1;
                                H::ExcelLog("Number - ".$formatPhoneNumber.' found in Twilio error log Candidate Id = ' . $uniqueID, 'candidateSync.log');
                            }
                            $row['is_ignored'] = $is_ignored;
                            $row['needs_geocoding'] = 1;
                            echo "\n\n\n\n\n";
                            echo htmlspecialchars($row['firstName'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($row['lastName'], ENT_QUOTES, 'UTF-8');
                            echo "\n";
                            var_dump($row);

                            echo "\n\n";
                            $setApplicationNotValid = 0;
                            //Nik:  initializing a vairable
                            //$is_trackable = 0;
                            if (!empty($dbRecord)) {
                                //Nik:  we are here only if the candidate already exists in candidates table
                                //Nik:  so should we look here to see if this if the previous application was added in the same week?
                                //Nik:  and if it NOT,  we set is_trackable = 1 below where we insert the application?
                                //if this is NOT the same application AND it's the first application in the current week, set $is_trackable = 1
                                echo 'Update Record' . "\n";
                                $candidate_id = $dbRecord[0]->id;
                                H::ExcelLog('appsent: ' . $dbRecord[0]->appSentDate . ' - ' . $row['appSentDate'],'candidateSync.log');
                                if($dbRecord[0]->appSentDate > $row['appSentDate']){
                                    //Nik:  we are skipping the update if this application is older than the appSentDate on the existing record
                                    if($dbRecord[0]->appId != $applicationID) $setApplicationNotValid = 1;
                                    echo 'skipping because the appSentDate is older than what is on candidate record' . "\n";
                                    H::ExcelLog('skipping ' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] .
                                                ' because the appSentDate is older than what is on candidate record','candidateSync.log');
                                }elseif($row['appSentDate'] < $dbRecord[0]->rejectedByAgencyDate  ||  ($dbRecord[0]->appWorkflowState == 'Rejected By Agency' && $dbRecord[0]->appSentDate == $row['appSentDate']) ){
                                    //Nik:  we are skipping the update if this application is older than the Rejected By Agency Date on existing record OR it's the same application
                                    if($dbRecord[0]->appId != $applicationID) $setApplicationNotValid = 1;
                                    echo 'skipping because the appSentDate is older than the Rejected By Agency Date or it is the same application' . "\n";
                                    H::ExcelLog('skipping ' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] .
                                                ' because the appSentDate is older than the Rejected By Agency Date OR it is the same application. Application.appSentDate: ' . $row['appSentDate'] .
                                                ' -> candidates.rejectedByAgencyDate: ' . $dbRecord[0]->rejectedByAgencyDate  . ' -> candidates.appSentDate: ' . $dbRecord[0]->appSentDate .
                                                ' -> application.appSentDate: ' . $row['appSentDate'] . ' -> candidates.appWorkFlowState: ' . $dbRecord[0]->appWorkflowState,'candidateSync.log');
                                }else{
                                    $dbWorkFlowState = '';
                                    if(isset($dbRecord[0]->appWorkflowState)){
                                        $dbWorkFlowState = $dbRecord[0]->appWorkflowState;
                                    }
                                    H::ExcelLog('Jobvite appWorkFlowState: ' . $appWorkflowState . ' - DB appWorkFlowState: ' . $dbWorkFlowState,'candidateSync.log');
                                    //Nik:  below we are only updating OVER what is in candidates table existing record WHEN:
                                        //workFlowState on this application is New
                                        //AND appWorkFlowState on existing record is NOT New/RPS, PET
                                        //this is because New/RPS/PET means the existing record is on the dashboard so they are already
                                        //working with this candidate and we don't want to use the new application.
                                        //so the new application should never get set to is_trackable
                                    $excludedWorkFlowStates = [
                                        JobviteTherapyOutcomeEnum::RECRUITER_PHONE_SCREEN->value,
                                        JobviteTherapyOutcomeEnum::SCHEDULED_RECRUITER_PHONE_SCREEN->value,
                                        JobviteTherapyOutcomeEnum::PET_1->value,
                                        JobviteTherapyOutcomeEnum::PET_3->value,
                                        JobviteTherapyOutcomeEnum::PET_2->value,
                                    ];
                                    if ($appWorkflowState == JobviteTherapyOutcomeEnum::NEW->value
                                        && !in_array($dbWorkFlowState, $excludedWorkFlowStates)) {
                                        $row['Search_Name'] = str_replace('  ', ' ', str_replace(',', '', $row['firstName'] . ' ' . $row['lastName'] . ' ' . $row['firstName']));
                                        $row['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                        H::ExcelLog('Updating','candidateSync.log');
                                        H::ExcelLog(print_r($row,true),'candidateSync.log');
                                        DB::table('candidates')->whereRaw('binary `eId`= ?', [$uniqueID])->update($row);
                                    }else{
                                        //Nik:  we are skipping the update if the existing is still in an active status
                                        if($dbRecord[0]->appId != $applicationID) $setApplicationNotValid = 1;
                                        echo 'skipping because the current db record is an active status' . "\n";
                                        H::ExcelLog('skipping ' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] . ' because the current db record is an active status','candidateSync.log');
                                    }
                                }
                            } else {
                                echo 'Insert Record' . "\n";
                                //Nik:  if we are inserting the candidate we know it's the first application for the week so we know
                                        //we should set is_trackable = 1 below
                                //set primary email since this is a new record
                                if (isset($c['email'])) {
                                    $row['email'] = $c['email'];
                                }
                                $row['Search_Name'] = str_replace('  ', ' ', str_replace(',', '', $row['firstName'] . ' ' . $row['lastName'] . ' ' . $row['firstName']));
                                $row['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                $row['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                H::ExcelLog('Inserting','candidateSync.log');
                                H::ExcelLog(print_r($row,true),'candidateSync.log');
                                $candidate_id = DB::table('candidates')->insertGetId($row);
                                $this->mapTouched = true;
                                //send notifications (for now just to Marie)
                                if (isset($job['recruiters'])) {
                                    foreach($job['recruiters'] as $recruiter) {
                                        //echo 'recruiter:' . "\n";
                                        //var_dump($recruiter);
                                        //echo "\n";
                                        $userEmail = $recruiter['userName'];
                                        //echo 'useremail: ' . $userEmail . "\n";
                                        $userid = H::getHelper('ConstantHelper')->getUserByEmail($userEmail);
                                        echo 'userid: ' . htmlspecialchars($userid, ENT_QUOTES, 'UTF-8') . "\n";
                                        if($userid!=''){
                                            $data = [];
                                            $data['user_id'] = $userid;
                                            if (isset($job['title'])) {
                                                $jobTitle = $job['title'];
                                            }else{
                                                $jobTitle = 'Job not specified';
                                            }
                                            $data['title'] = $row['firstName'] . ' ' . $row['lastName'] . ' added to the candidates list for ' . $jobTitle;
                                            $data['type'] = 'candidate';
                                            $data['related_item'] = $candidate_id;
                                            $data['notes'] = '';
                                            Notifications::addNotification($data);
                                        }
                                    }
                                }
                            }
                            //now update the application
                            //Nik:  so should we set is_trackable here based on the above notes?
                            $row = [];
                            echo 'CANDIDATE ID: ' . htmlspecialchars($candidate_id, ENT_QUOTES, 'UTF-8') . "\n";
                            $row['candidate_id'] = $candidate_id;
                            $row['eId'] = $app['eId'];
                            if($job_id!='none') {
                                $row['job_id'] = $job_id;
                            }
                            if (isset($job['eId'])) {
                                $row['job_eId'] = $job['eId'];
                            }
                            if (isset($app['consentStatus'])) {
                                $row['consentStatus'] = $app['consentStatus'];
                            }
                            if (isset($app['disposition'])) {
                                $row['disposition'] = $app['disposition'];
                            }
                            if (isset($app['gender'])) {
                                $row['gender'] = $app['gender'];
                            }
                            if (isset($app['hasArtifacts'])) {
                                $row['hasArtifacts'] = $app['hasArtifacts'];
                            }
                            if (isset($app['hireDate'])) {
                                $row['hireDate'] = H::getDateTimeFromTimestamp($app['hireDate']);
                            }
                            if (isset($app['jobviteChannel'])) {
                                $row['jobviteChannel'] = $app['jobviteChannel'];
                            }
                            if (isset($app['lastUpdatedDate'])) {
                                $row['lastUpdatedDate'] = H::getDateTimeFromTimestamp($app['lastUpdatedDate']);
                            }
                            if (isset($app['personalDataProcessingStatus'])) {
                                $row['personalDataProcessingStatus'] = $app['personalDataProcessingStatus'];
                            }
                            if (isset($app['race'])) {
                                $row['race'] = $app['race'];
                            }
                            if (isset($app['resume']['content'])) {
                                $row['resumeContent'] = $app['resume']['content'];
                            }
                            if (isset($app['resume']['format'])) {
                                $row['resumeFormat'] = $app['resume']['format'];
                            }
                            if (isset($app['resume']['name'])) {
                                $row['resumeName'] = $app['resume']['name'];
                            }
                            if (isset($app['sentDate'])) {
                                $row['sentDate'] = H::getDateTimeFromTimestamp($app['sentDate']);
                            }
                            $row['application_date'] = substr($row['sentDate'],0,10);
                            if (isset($app['source'])) {
                                $row['source'] = $app['source'];
                            }
                            if (isset($app['sourceType'])) {
                                $row['sourceType'] = $app['sourceType'];
                            }
                            if (isset($app['startDate'])) {
                                $row['startDate'] = H::getDateTimeFromTimestamp($app['startDate']);
                            }
                            if (isset($app['veteranStatus'])) {
                                $row['veteranStatus'] = $app['veteranStatus'];
                            }
                            if (isset($app['workflowState'])) {
                                $row['workflowState'] = $app['workflowState'];
                            }
                            if (isset($app['workflowStateEId'])) {
                                $row['workflowStateId'] = $app['workflowStateEId'];
                            }
                            $uniqueID = $app['eId'];
                            echo 'looking for application: ' . htmlspecialchars($uniqueID, ENT_QUOTES, 'UTF-8') . "\n";
                            $dbRecord = DB::select("select * from candidate_applications
                                where binary `eId` = ?",[$uniqueID]);
                            if (!empty($dbRecord)) {
                                //echo 'Update Record' . "\n";
                                //$row['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                //H::ExcelLog('Updating Application','candidateSync.log');
                                //H::ExcelLog(print_r($row,true),'candidateSync.log');
                                //DB::table('candidate_applications')->whereRaw('`eId`= ?', [$uniqueID])->update($row);
                                //set candidate_applications.is_deleted to 1 if this app is not valid

                                //Nik:  see below where if any of the 'skip update' conditions above are true,  we set is_deleted = 1
                                //Nik:  we would want to skip setting is_trackable = 1 if $setApplicationNotValid = 1 because that means
                                        //this application should always be ignored.
                                if($setApplicationNotValid==1){
                                    $applicationRow['is_deleted'] = 1;
                                    DB::table('candidate_applications')->whereRaw('`eId`= ?', [$uniqueID])->update($applicationRow);
                                    H::ExcelLog('set is_deleted = 1' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] . ' because it was skipped above','candidateSync.log');
                                }
                            } else {
                                echo 'Insert Record' . "\n";
                                $row['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                $row['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                                //Nik:  see below where if any of the 'skip update' conditions above are true,  we set is_deleted = 1
                                //Nik:  we would want to skip setting is_trackable etc. . . . .
                                if($setApplicationNotValid==1){
                                    $row['is_deleted'] = 1;
                                    H::ExcelLog('set is_deleted = 1' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] . ' because it was skipped above','candidateSync.log');
                                }
                                H::ExcelLog('Inserting Application','candidateSync.log');
                                H::ExcelLog(print_r($row,true),'candidateSync.log');
                                DB::table('candidate_applications')->insert($row);
                            }
                            echo 'Last Updated Date: ' . htmlspecialchars($last_updated_date, ENT_QUOTES, 'UTF-8') . "\n";
                            echo "\n\n\n\n\n";
                        }else{
                            echo 'Skip because category does not match' . "\n";
                            $cat = 'not set';
                            if(isset($job['categoryName'])) {
                                $cat = $job['categoryName'];
                            }
                            echo 'skipping ' . htmlspecialchars($c['eId'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($c['firstName'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($c['lastName'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . ') because category does not match' . "\n";
                            H::ExcelLog('skipping ' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] . ' (' . $cat . ') because category does not match - ' . $last_updated_date,'candidateSync.log');
                        }
                    }else{
                        echo 'Skip because there is not job associated with record' . "\n";
                        echo 'skipping ' . htmlspecialchars($c['eId'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($c['firstName'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($c['lastName'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($app['eId'], ENT_QUOTES, 'UTF-8') . ') because there is no job attached to application' . "\n";
                        H::ExcelLog('skipping ' . $c['eId'] . ' - ' . $c['firstName'] . ' ' . $c['lastName'] . ' (' . $app['eId'] . ') because there is no job attached to application - ' . $last_updated_date,'candidateSync.log');
                    }
                    $l = date('c', ($app['lastUpdatedDate'] / 1000));
                    $uniqueID = $c['eId'];
                    $last_updated_date = substr($l, 5, 2) . '-' . substr($l, 8, 2) . '-' . substr($l, 0, 4) . substr($l, 10, 12) . substr($l, 23, 2);
                    $last_updated_id = $uniqueID;
                    H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
                }
            }

        if ($this->mapTouched)  {
            event(new MapUpdatedEvent('candidates'));
        }

        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    /**
     * get record for specific candidate.
     */
    public function getCandidate($id)
    {
        $apicall = env('JOBVITE_API_URL') . '/candidate?candidateId=' . $id;
        echo $apicall . "\n";
        $data = $this->getCurl($apicall);
        //var_dump($data);
        if (count($data['candidates']) > 0) {
            foreach ($data['candidates'] as $c) {
                var_dump($c);
                echo "\n";
            }
        }
    }

    public function getCurl($url = 'https://api.jobvite.com/api/v2/candidate',$type="candidate")
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, H::getHelper("Jobvite")->getJobviteAuthenticationHeader($type));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
