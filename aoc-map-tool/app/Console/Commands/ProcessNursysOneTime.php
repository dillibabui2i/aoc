<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\Notifications;

class ProcessNursysOneTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nursys:singleprocess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Nursys one time';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $requestCounter;
    public function __construct()
    {
        parent::__construct();
        $this->requestCounter = 0;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "Process Nursys \n";
        $syncType = 'nursys_onetime';
        $this->requestCounter = 0;
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        // Pre-check for previous Lookup Transaction and process it,
        //it will only create the addnurses transcation to process later
        echo "Pre Checking previous transcation lookup\n";
        H::ExcelLog("Pre Checking previous transaction lookup",'nursys_onetimeprocess.log');
        $pendingNurseLookupTransaction = DB::table("nursys_transaction_onetime")
                ->where("transaction_type","nursyslookup")->orderBy("id")->limit(1)->first();
        if(!empty($pendingNurseLookupTransaction->transaction_id)) {
            H::ExcelLog("--->  TransactionID: " . $pendingNurseLookupTransaction->transaction_id,'nursys_onetimeprocess.log');
            $processPendingViaManagerList = $this->processNursysLookup($pendingNurseLookupTransaction->transaction_id);
            $pendingManageList = $this->prepareDataForManageList($processPendingViaManagerList);
            $manageUserListTransaction = H::getHelper("Nursys")->createManageNurseTransaction($pendingManageList,0);
            var_dump($manageUserListTransaction);
            echo "\n";
        } else {
            echo "\n Not found previous transaction\n";
            H::ExcelLog("Not found previous transaction",'nursys_onetimeprocess.log');
        }

        // GET New Batch of records for processing
        // This gets the next 10 Professional License renewables based on last_nursys_sync
        // and sends lookup request to Nursys API
        $batchMessage = "GET New Batch of records for processing";
        
        $batch_info['batch_size'] = 20;
        H::ExcelLog($batchMessage,'nursys_onetimeprocess.log');
        echo $batchMessage . "\n";
        
        $providersData = DB::table("tempRenewables")
            ->select("mssql_providers.*",
                DB::raw("tempRenewables.id as renewables_id"),"tempRenewables.matched_license_no","tempRenewables.matched_license_state")
            ->leftJoin("mssql_providers",function($query){
                $query->on("tempRenewables.id","=","mssql_providers.ProviderID");
            })
            ->limit($batch_info['batch_size'])
            ->orderBy("mssql_providers.ProviderID")
            ->where("mssql_providers.ProviderID",">",$batch_info['last_updated_id'])
			->where("tempRenewables.nursys_stage","=",'')
            ->whereIn("mssql_providers.DisciplineName",["RN","LVN","LPN"]);
       
        echo "\n";

        var_dump($providersData->toSql());
        $providerRecords  = $providersData->get();
        var_dump($providerRecords);
        //exit;
        $last_updated_id = 0;
        $last_updated_date = date("Y-m-d H:i:s");
        $nursesLookupArray = [];
        foreach($providerRecords as $provider) {
            H::ExcelLog("---> Add to nursesLookupArray:  renewables_id " . $provider->renewables_id . ': ' .
                $provider->ProviderName . ' => ' . $provider->matched_license_state . ' ' .
                $provider->matched_license_no,'nursys_onetimeprocess.log');
            $last_updated_id = $provider->ProviderID;
            $this->prepareDataForLookup($provider,$nursesLookupArray);
        }
        if($last_updated_id!=0) H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        var_dump($nursesLookupArray);
        echo "\n";
        
        // processing nurse lookup and creating transcation for manage nursys
        if(count($nursesLookupArray)) {
            echo "Preparing New Data for Nurse Lookup \n";
            H::ExcelLog("Preparing New Data for Nurse Lookup",'nursys_onetimeprocess.log');
            $nurseLookupContainer = $this->addContainertoNurseLookup($nursesLookupArray);
            $transaction = H::getHelper("Nursys")->createNurseLookupTransaction($nurseLookupContainer,0);
            echo $this->requestCounter++;
            var_dump($transaction);
            H::ExcelLog("request counter: " . $this->requestCounter,'nursys_onetimeprocess.log');
            H::ExcelLog(print_r($transaction,true),'nursys_onetimeprocess.log');
            echo "Processing New Data Nurse Lookup \n";
            $processViaManagerList = $this->processNursysLookup($transaction['Transaction']['TransactionId']);

            
            // Perform Nurse Adding to NURSYS (the remaining list from nursys lookup)
            // This creates a request to add any nurse that is not already listed in AOC's nursys data
             if(count($processViaManagerList)) {
                echo "\n Performing Manage Nursys\n";
                H::ExcelLog("Performing Manage Nursys",'nursys_onetimeprocess.log');
				var_dump($processViaManagerList);
				echo "\n";
                $manageList = $this->prepareDataForManageList($processViaManagerList);

                // create manage list transaction and save into database for processing later
                $manageUserListTransaction = H::getHelper("Nursys")->createManageNurseTransaction($manageList,0);
                var_dump($manageUserListTransaction);
                echo "\n";
            }
        }


        // Process Manage List from database
        echo "\n Checking for previous Nurse Manage list transaction in database and processing ";
        H::ExcelLog("Checking for previous Nurse Manage list transaction in database and processing",
            'nursys_onetimeprocess.log');
        $pendingTransaction = DB::table("nursys_transaction_onetime")
                ->where("transaction_type","managenurse")
                ->orderBy("id")
                ->limit(1)
                ->first();
        if(!empty($pendingTransaction->transaction_id)) {
            H::ExcelLog("call processManageUserList",'nursys_onetimeprocess.log');
            $this->processManageUserList($pendingTransaction->transaction_id);
        }
        echo "\nLookup Counter :".$this->requestCounter;
        echo "\nProccess Finished";
        H::ExcelLog("Lookup Counter: " . $this->requestCounter,'nursys_onetimeprocess.log');
        H::ExcelLog("Process Finished",'nursys_onetimeprocess.log');
        H::getHelper('Sync')->stopBatch($syncType);
        return 0;
    }


    public function processManageUserList($transactionId){
        // This calls api to get the transaction (attempting to add nurses to AOC)
        echo "\n\nProcessing Manage User List API";
        H::ExcelLog("Processing Manage User List API",'nursys_onetimeprocess.log');
        if($this->requestCounter < 18) {
            $manageUserListResponse = H::getHelper("Nursys")->performManageUserListTransaction($transactionId,0);
            echo $this->requestCounter++;
            H::ExcelLog("Request Counter: " . $this->requestCounter,'nursys_onetimeprocess.log');
            if($manageUserListResponse['ProcessingCompleteFlag']) {

                H::ExcelLog("Manage Processing Finished and Data ready for further processing :"
                    .$manageUserListResponse['Transaction']['TransactionId'],'nursys_onetimeprocess.log');
                $addedNurseList = [];
                $failedNurseList = [];
                $processingManageResponseCounter = 0;
                // update nsbcnid to database check the expiry
                foreach($manageUserListResponse['ManageNurseListResponses'] as  $manageListResponse) {
                    H::ExcelLog("manageListResponse: " . print_r($manageListResponse,true),'nursys_onetimeprocess.log');
                    if($manageListResponse['SuccessFlag']){
                        // api found a nurse that matches our license number
                        $addedNurseList[] = $manageListResponse['ManageNurseListRequest'];
                        var_dump($manageListResponse['ManageNurseListRequest']);
                        H::ExcelLog("ManageNurseListRequest: " . 
                            print_r($manageListResponse['ManageNurseListRequest'],true),'nursys_onetimeprocess.log');
                    } else {
                        echo "\n Nursys Error Message Added to DB\n";
                        //exit;
                        // api did not find a nurse that matches our license number
                        H::ExcelLog("Not success!  Set provider to -1",'nursys_onetimeprocess.log');
                        $this->processProviderDatabase("-1",$manageListResponse['ManageNurseListRequest'],[],$manageListResponse['Errors'][0]['ErrorMessage']);
                    }
                }
                if(count($addedNurseList)) {
                    H::ExcelLog("AddedNurseList Count: " . count($addedNurseList),'nursys_onetimeprocess.log');
                    $nurseLookupContainer = $this->addContainertoNurseLookup($addedNurseList);
                    $finalNurseLookupTransaction = H::getHelper("Nursys")
                            ->createNurseLookupTransaction($nurseLookupContainer,0);
                    echo $this->requestCounter++;
                    H::ExcelLog("RequestCounter: " . $this->requestCounter,'nursys_onetimeprocess.log');
                    echo "\nFinal Lookup Going on\n";
                    H::ExcelLog("Final Lookup Going on",'nursys_onetimeprocess.log');
                    $this->processNursysLookup($finalNurseLookupTransaction['Transaction']['TransactionId']);
                }
            } else {

                H::ExcelLog("Manage Processing is not finished yet : "
                    .$manageUserListResponse['Transaction']['TransactionId'],'nursys_onetimeprocess.log');
            }
        }
    }



    public function processNursysLookup($transactionId=''){

        $processViaManagerList = [];
        $keepChecking = 1;
        $finalResponse = [];
        while($keepChecking && $this->requestCounter < 20) {
            
            sleep(1);
            H::ExcelLog("Checking performNurseLookupTransaction: " . $transactionId,'nursys_onetimeprocess.log');
            $finalResponse = H::getHelper("Nursys")->performNurseLookupTransaction($transactionId,0);
            if($finalResponse['ProcessingCompleteFlag']) {
                echo "\n Response Recieved for nurse lookup";
                H::ExcelLog("Response Received for nurse lookup",'nursys_onetimeprocess.log');
                $keepChecking = 0;
            }
           echo $this->requestCounter++;
           H::ExcelLog("requestCounter: " . $this->requestCounter,'nursys_onetimeprocess.log');
        }


        foreach($finalResponse['NurseLookupResponses'] as  $fResponse) {
            $tmpResponse = $fResponse;
            if(isset($tmpResponse['NurseLookupRNAuthorizationsToPractice'])) {
                unset($tmpResponse['NurseLookupRNAuthorizationsToPractice']);
            }
            if(isset($tmpResponse['NurseLookupPNAuthorizationsToPractice'])) {
                unset($tmpResponse['NurseLookupPNAuthorizationsToPractice']);
            }

            H::ExcelLog("fResponse - processNursysLookup",'nursys_onetimeprocess.log');
            H::ExcelLog(print_r($tmpResponse,true),'nursys_onetimeprocess.log');
            var_dump($tmpResponse);
            echo "\n";
            if($fResponse['SuccessFlag']){
                H::ExcelLog("SuccessFlag for " . $fResponse['NurseLookupRequest']['RecordId'],'nursys_onetimeprocess.log');
                if(!empty($fResponse['NcsbnId'])) {
                    $fRequest = $fResponse['NurseLookupRequest'];
                    $flicense= $fResponse['NurseLookupLicenses'];
                    $ncsbnId = $fResponse['NcsbnId'];
                    H::ExcelLog("process NcsbnId - " . $ncsbnId,'nursys_onetimeprocess.log');
                    $this->processProviderDatabase($ncsbnId,$fRequest,$flicense);
                    
                }
            } else {
                H::ExcelLog("Nurse not found so Add to processViaManagerList for " . $fResponse['NurseLookupRequest']['RecordId'],'nursys_onetimeprocess.log');
                $processViaManagerList[] = $fResponse['NurseLookupRequest'];
            }
         }
         return $processViaManagerList;
    }




    public function prepareDataForManageList($nurseList) {
		echo 'NurseList';
		var_dump($nurseList);
		echo "\n";
        foreach($nurseList as $key=> $nurse) {
			echo '****** ' .$nurse["RecordId"] .  "\n";
            $provider = DB::table("tempRenewables")->select("mssql_providers.*")
            ->leftJoin("mssql_providers",function($query){
                $query->on("tempRenewables.id","=","mssql_providers.ProviderID");
            })
			->where("mssql_providers.ProviderID",$nurse["RecordId"])->first();
            $renewableUpdate['nursys_stage'] = 'nurse not found - try to add';
            DB::table("tempRenewables")->where("tempRenewables.id",$nurse["RecordId"])->update($renewableUpdate);
            $nurseList[$key]["SubmissionActionCode"] = 'A';
            $nurseList[$key]["Address1"] = $provider->Address1;
            $nurseList[$key]["Address2"] = $provider->Address2;
            $nurseList[$key]["City"] =  $provider->City;
            $nurseList[$key]["State"] = $provider->State;
            $nurseList[$key]["Zip"]  = $provider->Zip;
            $nurseList[$key]["LastFourSSN"] = substr($provider->SSN,-4);
            $nurseList[$key]["BirthYear"] = date("Y",strtotime($provider->DateOfBirth));
            $nurseList[$key]["HospitalPracticeSetting"] = '-1';
            $nurseList[$key]["NotificationsEnabled"] = 'N';
            $nurseList[$key]["RemindersEnabled"] = 'N';
            H::ExcelLog("processing: " . $key,'nursys_onetimeprocess.log');
            H::ExcelLog(print_r($nurseList[$key],true),'nursys_onetimeprocess.log');
        }
        
        return ["ManageNurseListRequests"=>$nurseList];
    }

    public function processProviderDatabase($ncsbnId,$lookupRequest,$lookupLicense, $errorMessage='') {

		$renewableId = $lookupRequest['RecordId'];
        $renewablesData=DB::table("tempRenewables")->select("*")->where("id",$renewableId)->first();
		H::ExcelLog(print_r($renewablesData,true),'nursys_onetimeprocess.log');
        H::ExcelLog("processProviderDatabase:  Processing: " . $ncsbnId,'nursys_onetimeprocess.log');
        H::ExcelLog("lookupRequest: " . print_r($lookupRequest,true),'nursys_onetimeprocess.log');
        H::ExcelLog("lookupLicense: " . print_r($lookupLicense,true),'nursys_onetimeprocess.log');
        $UpdateData = [];

        if($ncsbnId!='-1') {
            $renewableUpdate['nursys_stage'] = 'nurse added - matching license';
            DB::table("tempRenewables")->where("tempRenewables.id",$renewableId)->update($renewableUpdate);

            H::ExcelLog("Match License to " . $lookupRequest['LicenseNumber'] . ' for '
                .$renewableId,'nursys_onetimeprocess.log');

            $matchingLicense = -1;
            $myCount = 0;
            foreach($lookupLicense as $tmpLicense){
                H::ExcelLog("tmpLicense: " . print_r($tmpLicense,true),'nursys_onetimeprocess.log');
                H::ExcelLog('|' . $tmpLicense['LicenseNumber'] . '| -> |' . $lookupRequest['LicenseNumber'] . '|'
                    ,'nursys_onetimeprocess.log');
                var_dump($tmpLicense['LicenseNumber']);
                echo "\n";
                var_dump($lookupRequest['LicenseNumber']);
                echo "\n";
                if($tmpLicense['LicenseNumber']==$lookupRequest['LicenseNumber']){
                    echo 'matched at ' . $myCount . "\n";
                    $matchingLicense = $myCount;
                    break;
                }
                $myCount++;
            }
            if($matchingLicense > -1){
                // now make sure expiration date matches
                // update stage to renewable record
                $renewableUpdate['nursys_stage'] = 'license found - checking expiration';
                DB::table("tempRenewables")->where("tempRenewables.id",$renewableId)->update($renewableUpdate); 

                $expirationDateFromNursys =
                    strtotime(date("Y-m-d",strtotime($lookupLicense[$matchingLicense]['LicenseExpirationDate'])));
                $lastNameFromNursys = $lookupLicense[$matchingLicense]['LastName'];
                $firstNameFromNursys = $lookupLicense[$matchingLicense]['FirstName'];
                echo "ExpirationDateFromNursys: " .
                    date("Y-m-d",strtotime($lookupLicense[$matchingLicense]['LicenseExpirationDate'])) . "\n";
                    $renewableUpdate['new_expiry_date'] = date("Y-m-d",strtotime($lookupLicense[$matchingLicense]['LicenseExpirationDate']));
					$renewableUpdate['nursys_mismatch'] = 0;
					$renewableUpdate['nursys_sync_status'] = 'Success';
					$renewableUpdate['nursys_stage'] = 'Success';
					$renewableUpdate['nursys_error'] = '';
                    $renewableUpdate['nursys_lastname'] = $lastNameFromNursys;
                    $renewableUpdate['nursys_firstname'] = $firstNameFromNursys;
                    $renewableUpdate['nursys_issue_date'] = date("Y-m-d",strtotime($lookupLicense[$matchingLicense]['LicenseOriginalIssueDate']));
                    $renewableUpdate['nursys_license_status'] = $lookupLicense[$matchingLicense]['LicenseStatus'];
                    $renewableUpdate['nursys_license_no'] = $lookupLicense[$matchingLicense]['LicenseNumber'];
                    $renewableUpdate['nursys_license_state'] = $lookupLicense[$matchingLicense]['JurisdictionAbbreviation'];
                    $renewableUpdate['nursys_license_type'] = $lookupLicense[$matchingLicense]['LicenseType'];
                    DB::table("tempRenewables")->where("tempRenewables.id",$renewableId)->update($renewableUpdate);
            }else{
                $renewableUpdate['nursys_mismatch'] = 1;
                $renewableUpdate['nursys_sync_status'] = 0;
                $renewableUpdate['nursys_stage'] = 'finished';
                $renewableUpdate['nursys_error'] = 'Nurse found but License No not found';
                echo "\n not Match :" . htmlspecialchars($renewableId, ENT_QUOTES, 'UTF-8');
                H::ExcelLog("Did not find license in return array for " . $renewableId,'nursys_onetimeprocess.log');
                DB::table("tempRenewables")->where("tempRenewables.id",$renewableId)->update($renewableUpdate);

            }
        } else {
            if(empty($errorMessage)) {
                $errorMessage = 'Clinician license not found';
            }
            echo "\nFailed : " . htmlspecialchars($renewableId, ENT_QUOTES, 'UTF-8');
            H::ExcelLog("Did not match license",'nursys_onetimeprocess.log');
            $renewableUpdate['nursys_mismatch'] = 1;
            $renewableUpdate['nursys_sync_status'] = 0;
            $renewableUpdate['nursys_stage'] = 'finished';
            $renewableUpdate['nursys_error'] = $errorMessage;
            DB::table("tempRenewables")->where("tempRenewables.id",$renewableId)->update($renewableUpdate);
        }
    }

    public function addContainertoNurseLookup($dataArray) {
        return ["NurseLookupRequests"=>$dataArray];
    }

    public function prepareDataForLookup($provider,&$nursesLookupArray){
        $licenseType = 'RN';
        if($provider->DisciplineName=='LVN' || $provider->DisciplineName=='LPN') {
            $licenseType = 'PN';
        }
    
        $position = count($nursesLookupArray);
      
        $juridiction = $provider->matched_license_state;
        $license = $provider->matched_license_no;

        if($provider->matched_license_state=='LA') {
            $juridiction = '';
            if($provider->DisciplineName=='LVN' || $provider->DisciplineName=='LPN') {
                $juridiction = 'LAPN';
            } else {
                $juridiction = 'LARN';
            }
        }

       
        //if($provider->license_state=='CO') {
            $license= preg_replace("/[^0-9]/", "", $license);
        //}

        $nursesLookupArray[$position]['LicenseNumber'] = $license;
        $nursesLookupArray[$position]['LicenseType'] = $licenseType;
        $nursesLookupArray[$position]['JurisdictionAbbreviation'] = $juridiction;
        $nursesLookupArray[$position]['RecordId'] = $provider->ProviderID;

        H::ExcelLog(print_r($nursesLookupArray[$position],true),'nursys_onetimeprocess.log');
    }
}
