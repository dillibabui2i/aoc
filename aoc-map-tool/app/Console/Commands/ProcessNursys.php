<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\Notifications;

class ProcessNursys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nursys:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Nursys';

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
        $syncType = 'nursys';
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
        H::ExcelLog("Pre Checking previous transaction lookup",'nursys_process.log');
        $pendingNurseLookupTransaction = DB::table("nursys_transactions")
                ->where("transaction_type","nursyslookup")->orderBy("id")->limit(1)->first();
        if(!empty($pendingNurseLookupTransaction->transaction_id)) {
            H::ExcelLog("--->  TransactionID: " . $pendingNurseLookupTransaction->transaction_id,'nursys_process.log');
            $processPendingViaManagerList = $this->processNursysLookup($pendingNurseLookupTransaction->transaction_id);
            $pendingManageList = $this->prepareDataForManageList($processPendingViaManagerList);
            $manageUserListTransaction = H::getHelper("Nursys")->createManageNurseTransaction($pendingManageList);
            var_dump($manageUserListTransaction);
            echo "\n";
        } else {
            echo "\n Not found previous transaction\n";
            H::ExcelLog("Not found previous transaction",'nursys_process.log');
        }

        // GET New Batch of records for processing
        // This gets the next 10 Professional License renewables based on last_nursys_sync
        // and sends lookup request to Nursys API
        $batchMessage = "GET New Batch of records for processing";

        $batch_info['batch_size'] = 20;
        H::ExcelLog($batchMessage,'nursys_process.log');
        echo $batchMessage . "\n";

        $providersData = DB::table("renewables")
            ->select("mssql_providers.*",
                DB::raw("renewables.id as renewables_id"),"renewables.license_no","renewables.license_state")
            ->leftJoin("mssql_providers",function($query){
                $query->on("renewables.aoc_provider_id","=","mssql_providers.id");
            })
            ->leftJoin("renewables_types",function($query){
                $query->on("renewables_types.renewable_type_id","=","renewables.renewables_type");
            })
            ->limit($batch_info['batch_size'])
            ->orderBy("renewables.last_nursys_sync")
            ->where("renewables_types.name","PROFESSIONAL LICENSE")
            ->whereIn("mssql_providers.DisciplineName",["RN","LVN","LPN"])
            ->where("renewables.license_no","!=",'')
            ->whereNotNull("renewables.license_no")
            ->where("renewables.IsMissing",0)
            ->whereRaw("ifnull(renewables.last_nursys_sync, '2001-01-01') < '".date('Y-m-d') . "'")
            ->where("mssql_providers.EmploymentStatusID","!=",12)
            ->where('mssql_providers.is_deleted',0);

        echo "\n";

        var_dump($providersData->toSql());
        $providerRecords  = $providersData->get();

        $last_updated_id = 0;
        $last_updated_date = date("Y-m-d H:i:s");
        $nursesLookupArray = [];
        foreach($providerRecords as $provider) {
            H::ExcelLog("---> Add to nursesLookupArray:  renewables_id " . $provider->renewables_id . ': ' .
                $provider->ProviderName . ' => ' . $provider->license_state . ' ' .
                $provider->license_no,'nursys_process.log');
            $last_updated_id = $provider->renewables_id;
            $this->prepareDataForLookup($provider,$nursesLookupArray);
            //update renewables.last_nursys_sync to it gets pushed to the end of the list!
            $renewableUpdate['last_nursys_sync'] = date("Y-m-d H:i:s");
            $renewableUpdate['nursys_stage'] = 'added to queue';
            DB::table("renewables")->where("renewables.id",$provider->renewables_id)->update($renewableUpdate);
        }
        H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        var_dump($nursesLookupArray);
        echo "\n";

        // processing nurse lookup and creating transcation for manage nursys
        if(count($nursesLookupArray)) {
            echo "Preparing New Data for Nurse Lookup \n";
            $nurseLookupContainer = $this->addContainertoNurseLookup($nursesLookupArray);
            $transaction = H::getHelper("Nursys")->createNurseLookupTransaction($nurseLookupContainer);
            echo $this->requestCounter++;
            var_dump($transaction);
            H::ExcelLog("request counter: " . $this->requestCounter,'nursys_process.log');
            H::ExcelLog(print_r($transaction,true),'nursys_process.log');
            echo "Processing New Data Nurse Lookup \n";
            $transactionId = $transaction['Transaction']['TransactionId'] ?? '';
            $processViaManagerList = $this->processNursysLookup($transactionId);


            // Perform Nurse Adding to NURSYS (the remaining list from nursys lookup)
            // This creates a request to add any nurse that is not already listed in AOC's nursys data
             if(count($processViaManagerList)) {
                echo "\n Performing Manage Nursys\n";
                H::ExcelLog("Performing Manage Nursys",'nursys_process.log');
                $manageList = $this->prepareDataForManageList($processViaManagerList);

                // create manage list transaction and save into database for processing later
                $manageUserListTransaction = H::getHelper("Nursys")->createManageNurseTransaction($manageList);
                 H::ExcelLog("CREATE MANAGE NURSE TRANSACTION RESPONSE:\n" . print_r($manageUserListTransaction, true), 'nursys_process.log');
                var_dump($manageUserListTransaction);
                echo "\n";
            }
        }


        // Process Manage List from database
        echo "\n Checking for previous Nurse Manage list transaction in database and processing ";
        H::ExcelLog("Checking for previous Nurse Manage list transaction in database and processing",
            'nursys_process.log');
        $pendingTransaction = DB::table("nursys_transactions")
                ->where("transaction_type","managenurse")
                ->orderBy("id")
                ->limit(1)
                ->first();
        if(!empty($pendingTransaction->transaction_id)) {
            H::ExcelLog("call processManageUserList",'nursys_process.log');
            $this->processManageUserList($pendingTransaction->transaction_id);
        }
        echo "\nLookup Counter :".$this->requestCounter;
        echo "\nProccess Finished";
        H::ExcelLog("Lookup Counter: " . $this->requestCounter,'nursys_process.log');
        H::ExcelLog("Process Finished",'nursys_process.log');
        H::getHelper('Sync')->stopBatch($syncType);
        return 0;
    }


    public function processManageUserList($transactionId){
        // This calls api to get the transaction (attempting to add nurses to AOC)
        echo "\n\nProcessing Manage User List API";
        H::ExcelLog("Processing Manage User List API",'nursys_process.log');
        if($this->requestCounter < 18) {
            $manageUserListResponse = H::getHelper("Nursys")->performManageUserListTransaction($transactionId);
            H::ExcelLog("RAW RESPONSE FROM performManageUserListTransaction (ID: $transactionId):\n" .
                print_r($manageUserListResponse, true), 'nursys_process.log');
            echo $this->requestCounter++;
            H::ExcelLog("Request Counter: " . $this->requestCounter,'nursys_process.log');
            if($manageUserListResponse['ProcessingCompleteFlag']) {
                echo "\n Manage Processing Finished and Data ready for further processing :"
                . htmlspecialchars($manageUserListResponse['Transaction']['TransactionId'], ENT_QUOTES, 'UTF-8');
                $addedNurseList = [];
                $failedNurseList = [];
                $processingManageResponseCounter = 0;
                // update nsbcnid to database check the expiry
                if (isset($manageUserListResponse['ManageNurseListResponses'])
                    && isset($manageUserListResponse['ManageNurseListRequest'])) {
                    foreach ($manageUserListResponse['ManageNurseListResponses'] as $manageListResponse) {
                        if ($manageListResponse['SuccessFlag']) {
                            // api found a nurse that matches our license number
                            $addedNurseList[] = $manageListResponse['ManageNurseListRequest'];
                            var_dump($manageListResponse['ManageNurseListRequest']);
                        } else {
                            echo "\n Nursys Error Message Added to DB\n";
                            //exit;

                            H::ExcelLog("FAILED TO ADD NURSE. RAW RESPONSE: \n" . print_r($manageListResponse, true),
                                'nursys_process.log');

                            // api did not find a nurse that matches our license number
                            if (isset($manageListResponse['Errors'][0]['ErrorMessage'])) {
                                $this->processProviderDatabase("-1", $manageListResponse['ManageNurseListRequest'], [], htmlspecialchars($manageListResponse['Errors'][0]['ErrorMessage'], ENT_QUOTES, 'UTF-8'));
                            }
                        }
                    }
                }
                if(count($addedNurseList)) {
                    $nurseLookupContainer = $this->addContainertoNurseLookup($addedNurseList);
                    $finalNurseLookupTransaction = H::getHelper("Nursys")
                            ->createNurseLookupTransaction($nurseLookupContainer);
                    echo $this->requestCounter++;
                    echo "\nFinal Lookup Going on\n";
                    $finalTransactionId = $finalNurseLookupTransaction['Transaction']['TransactionId'] ?? '';
                    $this->processNursysLookup($finalTransactionId);
                }
            } else {
                echo "\n Manage Processing is not finished yet : "
                . htmlspecialchars($manageUserListResponse['Transaction']['TransactionId'], ENT_QUOTES, 'UTF-8');
                H::ExcelLog("Manage Processing is not finished yet : "
                    .$manageUserListResponse['Transaction']['TransactionId'],'nursys_process.log');
            }
        }
    }



    public function processNursysLookup($transactionId=''){

        $processViaManagerList = [];
        $keepChecking = 1;
        $finalResponse = [];
        while($keepChecking && $this->requestCounter < 20) {

            sleep(1);
            H::ExcelLog("Checking performNurseLookupTransaction: " . $transactionId,'nursys_process.log');
            $finalResponse = H::getHelper("Nursys")->performNurseLookupTransaction($transactionId);
            if($finalResponse['ProcessingCompleteFlag'] ?? false) {
                echo "\n Response Recieved for nurse lookup";
                H::ExcelLog("Response Received for nurse lookup",'nursys_process.log');
                $keepChecking = 0;
            }
           echo $this->requestCounter++;
           H::ExcelLog("requestCounter: " . $this->requestCounter,'nursys_process.log');
        }


        foreach($finalResponse['NurseLookupResponses'] as  $fResponse) {
            $tmpResponse = $fResponse;
            if(isset($tmpResponse['NurseLookupRNAuthorizationsToPractice'])) {
                unset($tmpResponse['NurseLookupRNAuthorizationsToPractice']);
            }
            if(isset($tmpResponse['NurseLookupPNAuthorizationsToPractice'])) {
                unset($tmpResponse['NurseLookupPNAuthorizationsToPractice']);
            }

                var_dump($tmpResponse);
                echo "\n";
                if ($fResponse['SuccessFlag'] ?? false) {
                    if (!empty($fResponse['NcsbnId'])) {
                        $fRequest = $fResponse['NurseLookupRequest'];
                        $flicense = $fResponse['NurseLookupLicenses'];
                        $ncsbnId = $fResponse['NcsbnId'];

                    $this->processProviderDatabase(
                        htmlspecialchars($ncsbnId, ENT_QUOTES, 'UTF-8'),
                        $fRequest,
                        $flicense
                    );
                }
            } else {
                H::ExcelLog("Add to processViaManagerList",'nursys_process.log');
                $processViaManagerList[] = $fResponse['NurseLookupRequest'];
            }
         }
         return $processViaManagerList;
    }




    public function prepareDataForManageList($nurseList) {

        foreach($nurseList as $key=> $nurse) {
			$provider = DB::table("renewables")->select("mssql_providers.*",
                "mssql_facilities.Address1 as branchAddress1",
                "mssql_facilities.City as branchCity","mssql_facilities.State as branchState",
                "mssql_facilities.Zip as branchZip")
            ->leftJoin("mssql_providers",function($query){
                $query->on("renewables.aoc_provider_id","=","mssql_providers.id");
            })
            ->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_facilities.FacilityID","=","mssql_providers.FacilityID");
            })
            ->where("renewables.id",$nurse["RecordId"])->where('mssql_providers.is_deleted',0)->first();
            $renewableUpdate['nursys_stage'] = 'nurse not found - try to add';
            DB::table("renewables")->where("renewables.id",$nurse["RecordId"])->update($renewableUpdate);
            $nurseList[$key]["SubmissionActionCode"] = 'A';
			$nurseList[$key]["Address1"] = $provider->branchAddress1;
			$nurseList[$key]["Address2"] = '';
			$nurseList[$key]["City"] =  $provider->branchCity;
			$nurseList[$key]["State"] = $provider->branchState;
			$nurseList[$key]["Zip"]  = $provider->branchZip;
			$nurseList[$key]["LastFourSSN"] = substr($provider->SSN,-4);
            $nurseList[$key]["BirthYear"] = date("Y",strtotime($provider->DateOfBirth));
            $nurseList[$key]["HospitalPracticeSetting"] = '-1';
            $nurseList[$key]["NotificationsEnabled"] = 'N';
            $nurseList[$key]["RemindersEnabled"] = 'N';
            H::ExcelLog("processing: " . $key,'nursys_process.log');
            H::ExcelLog(print_r($nurseList[$key],true),'nursys_process.log');
        }

        return ["ManageNurseListRequests"=>$nurseList];
    }

    public function processProviderDatabase($ncsbnId,$lookupRequest,$lookupLicense, $errorMessage='') {
        $renewableId = $lookupRequest['RecordId'];
        $renewablesData=DB::table("renewables")->select("*")->where("id",$renewableId)->first();
        H::ExcelLog("processProviderDatabase:  Processing: " . $ncsbnId,'nursys_process.log');
        H::ExcelLog("lookupRequest: " . print_r($lookupRequest,true),'nursys_process.log');
        H::ExcelLog("lookupLicense: " . print_r($lookupLicense,true),'nursys_process.log');
        $UpdateData = [];

        if($ncsbnId!='-1') {
            $renewableUpdate['nursys_stage'] = 'nurse added - matching license';
            DB::table("renewables")->where("renewables.id",$renewableId)->update($renewableUpdate);

            H::ExcelLog("Match License to " . $lookupRequest['LicenseNumber'] . ' for '
                .$renewableId,'nursys_process.log');
            echo "Match License to " . htmlspecialchars($lookupRequest['LicenseNumber'], ENT_QUOTES, 'UTF-8') . "\n";
            $matchingLicense = -1;
            $myCount = 0;
            foreach($lookupLicense as $tmpLicense){
                H::ExcelLog("tmpLicense: " . print_r($tmpLicense,true),'nursys_process.log');
                H::ExcelLog('|' . $tmpLicense['LicenseNumber'] . '| -> |' . $lookupRequest['LicenseNumber'] . '|'
                    ,'nursys_process.log');
                var_dump($tmpLicense['LicenseNumber']);
                echo "\n";
                var_dump($lookupRequest['LicenseNumber']);
                echo "\n";
                if(($tmpLicense['LicenseNumber']==$lookupRequest['LicenseNumber']) && ($tmpLicense['LicenseType']==$lookupRequest['LicenseType'])){
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
                DB::table("renewables")->where("renewables.id",$renewableId)->update($renewableUpdate);

                $expirationDateFromNursys = strtotime(date('Y-m-d', strtotime($lookupLicense[$matchingLicense]['LicenseExpirationDate'])));
                $expirationDateFromDB = strtotime(date('Y-m-d', strtotime($renewablesData->expiration_date)));

                if ($expirationDateFromNursys === $expirationDateFromDB) {
                    // expiration matches - update to renewable
                    $renewableUpdate['nursys_mismatch'] = 0;
                    $renewableUpdate['nursys_sync_status'] = 1;
                    $renewableUpdate['nursys_stage'] = 'finished';
                    $renewableUpdate['nursys_error'] = '';
                } else {
                    $renewableUpdate['nursys_mismatch'] = 1;
                    $renewableUpdate['nursys_sync_status'] = 0;
                    $renewableUpdate['nursys_stage'] = 'finished';
                    $renewableUpdate['nursys_error'] = 'Expiration date does not match';
                }
            }else{
                $renewableUpdate['nursys_mismatch'] = 1;
                $renewableUpdate['nursys_sync_status'] = 0;
                $renewableUpdate['nursys_stage'] = 'finished';
                $renewableUpdate['nursys_error'] = 'License not found';
            }
        } else {
            if(empty($errorMessage)) {
                $errorMessage = 'Clinician license not found';
            }

            $renewableUpdate['nursys_mismatch'] = 1;
            $renewableUpdate['nursys_sync_status'] = 0;
            $renewableUpdate['nursys_stage'] = 'finished';
            $renewableUpdate['nursys_error'] = $errorMessage;
        }
        $renewableUpdate = $this->applyMismatchCreatedAt($renewableUpdate,$renewablesData);
        DB::table("renewables")->where("renewables.id",$renewableId)->update($renewableUpdate);

        $UpdateData['NcsbnId']=$ncsbnId;
        DB::table("mssql_providers")->where("mssql_providers.id",$renewablesData->aoc_provider_id)->update($UpdateData);
    }

    private function applyMismatchCreatedAt($renewableUpdate,$renewablesData) {
        if(!isset($renewableUpdate['nursys_mismatch'])) {
            return $renewableUpdate;
        }

        if($renewableUpdate['nursys_mismatch'] === 1) {
            if(empty($renewablesData->nursys_mismatch_created_at)) {
                $renewableUpdate['nursys_mismatch_created_at'] = date('Y-m-d H:i:s');
            }
        } else {
            $renewableUpdate['nursys_mismatch_created_at'] = null;
        }

        return $renewableUpdate;
    }

    public function addContainertoNurseLookup($dataArray) {
        return ["NurseLookupRequests"=>$dataArray];
    }

    public function prepareDataForLookup($provider,&$nursesLookupArray){
        $licenseType = 'RN';
        if($provider->DisciplineName === 'LVN' || $provider->DisciplineName === 'LPN') {
            $licenseType = 'PN';
        }

        $position = count($nursesLookupArray);

        $juridiction = $provider->license_state;
        $license = $provider->license_no;

        if($provider->license_state=='LA') {
            $juridiction = '';
            if($provider->DisciplineName === 'LVN' || $provider->DisciplineName === 'LPN') {
                $juridiction = 'LAPN';
            } else {
                $juridiction = 'LARN';
            }
        } else if($provider->license_state=='WV') {
            $juridiction = '';
            if($provider->DisciplineName === 'LVN' || $provider->DisciplineName === 'LPN') {
                $juridiction = 'WVPN';
            } else {
                $juridiction = 'WVRN';
            }
        }


        if($provider->license_state=='CO') {
            $license= preg_replace("/[^0-9]/", "", $license);
        }

        $nursesLookupArray[$position]['LicenseNumber'] = $license;
        $nursesLookupArray[$position]['LicenseType'] = $licenseType;
        $nursesLookupArray[$position]['JurisdictionAbbreviation'] = $juridiction;
        $nursesLookupArray[$position]['RecordId'] = $provider->renewables_id;

        H::ExcelLog(print_r($nursesLookupArray[$position],true),'nursys_process.log');
    }
}
