<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SFTP;
use Illuminate\Support\Facades\Mail;

class syncPaycom
{
    private $fileName = '';

    private $downloadDirectory = '';

    const PAYCOMDIRECTORY = '/paycom/';

    private $logFile = 'sync_paycom.log';


    public function __construct(){
        $this->downloadDirectory = storage_path() . SELF::PAYCOMDIRECTORY;
        $this->fileName = $this->downloadFile();
        H::ExcelLog("File Name :".$this->fileName,$this->logFile);
    }
    
    private function downloadFile(){
       
       
        $root = "Home/000_0ix13_azure";

        $sftp = new SFTP(env('PAYCOM_HOST'), 22);
		
		try {
			$sftp->login(env('PAYCOM_USERNAME'), env('PAYCOM_PASSWORD'));
		} catch(\Exception $e) {
			$this->sendFailureEmail('Paycom SFTP Login Failed, not able to download the file.');
			exit('Login Failed');
		}
        $remote_directory = 'outbound';
        $sftp->chdir($remote_directory);
        $files = $sftp->nlist();
       
        $fileName = '';
        foreach($files as $file) {
            
            if($fileArray = preg_match('/^'.date('Ymd').'.*_IT Demographics Report_/i',$file)) {
                $fileName = $file;
               
                if(File::exists($this->downloadDirectory.$fileName)) {
                    
                    H::ExcelLog("File already processed :".$fileName,$this->logFile);
                    echo "File already processed :".$fileName."\n";
                    return '';

                } else {

                    H::ExcelLog("Downloading File",$this->logFile);
                    echo "Downloading File\n";
                    if(!File::exists($this->downloadDirectory)) {
                        File::makeDirectory($this->downloadDirectory, $mode = 0777, true, true);
                    }
                    if ($sftp->get($file, $this->downloadDirectory.$fileName)) {
                        echo "File downloaded successfully to $file\n";
                    } else {
                        echo "File download failed!\n";
                    }

                    echo "Downloaded File\n";
                    H::ExcelLog("Downloaded File",$this->logFile);
                   
                    return $fileName;

                }
            } 
        }
        H::ExcelLog("File Not Exist on server :".$fileName,$this->logFile);
        echo "File Not Exist on server  :".$fileName."\n";
        return '';
       
    }

    private function prepareData(){
        $importData = [];
        if($this->fileName) {
            $csvData = $this->csvToArray($this->downloadDirectory.$this->fileName);
           
            $i = 0;
            echo "Preparing Data for Import\n";
            H::ExcelLog("Preparing Data for Import",$this->logFile);
            foreach($csvData as $data) {
                $PaycomFacilityName = "_Unknown";

                //$FacilityData = DB::table("mssql_facilities")->select("FacilityName")->where("BranchName",$data['Location_Desc'])->first();
				$FacilityData = preg_replace('/\s/', ' - ', $data['Work_Location'], 1);
				
                if(!empty($FacilityData)) {
                    $PaycomFacilityName = $FacilityData;
                }

                $importData[$i]['PaycomEmploymentStatus'] = $data['Employee_Status'];
                $importData[$i]['LocationGroup'] = $data['Location_Desc'];
                $importData[$i]['Division'] = $data['Division_Desc'];
                $importData[$i]['Department'] = $data['Department_Desc'];
                $importData[$i]['EmployeeCode'] = $data['Employee_Code'];
                $importData[$i]['DOLStatus'] = $data['DOL_Status'];
                $importData[$i]['Firstname'] = $data['Legal_Firstname'];
                $importData[$i]['Lastname'] = $data['Legal_Lastname'];
                $importData[$i]['Nickname'] = $data['Nickname'];
                $importData[$i]['Position'] = $data['Position'];
                $importData[$i]['State'] = $data['SUI_State'];
                $importData[$i]['WorkEmail'] = $data['Work_Email'];
                $importData[$i]['SupervisorPrimaryClincianID'] = $data['Supervisor_Primary_Code'];
                $importData[$i]['PersonalEmail'] = $data['Personal_Email'];
                $importData[$i]['PaycomFacilityName'] = $PaycomFacilityName;
                $importData[$i]['is_updated'] = 1;
                $i++;
            }
            echo "Data Prepared\n";
            H::ExcelLog("Data Prepared",$this->logFile);
          
        } else {
            echo "Exiting...\n";
            H::ExcelLog("Exiting...",$this->logFile);
            exit;
        }
       

        return $importData;
       
    }

    public function processImport(){
        try {
            $data = $this->prepareData();
            $insertData = [];
            echo "Setting up 0 for all existing records\n";
            H::ExcelLog("Setting up 0 for all existing records",$this->logFile);
            DB::table("paycom")->update(["is_updated"=>0]);
            
            echo "Importing Data to paycom\n";
            H::ExcelLog("Importing Data to paycom",$this->logFile);
            foreach($data as $d){
                $paycom = DB::table("paycom")->where("EmployeeCode",$d['EmployeeCode'])->first();
                if(isset($paycom->id)) {
                    echo "Updating :".$d['EmployeeCode']."\n";
                    H::ExcelLog("Updating :".$d['EmployeeCode'],$this->logFile);
                    DB::table("paycom")->where("EmployeeCode",$d['EmployeeCode'])->update($d);
                } else {
                    $insertData[] = $d;
                }
            }
            if(count($insertData)) {
                $insertChunks = array_chunk($insertData, 500);
                foreach($insertChunks as $chunk) {
                    DB::table("paycom")->insert($chunk);
                    echo "\n Inserting.... \n";
                    H::ExcelLog("Inserting....",$this->logFile);
                    sleep(1); 
                }
            }

            echo "\n Finished.... \n";
            H::ExcelLog("Finished....",$this->logFile);
        } catch(\Exception $e) {
            H::ExcelLog("Error :".$e->getMessage(),$this->logFile);
        }
          
    }

    private function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    public function sendFailureEmail($messageContent)
    {
        $toEmail = 'aocappalerts@angelsofcare.com';
        $subject = 'HIGH: Paycom SFTP Login Failed';
    
        $data = [
            'message' => $messageContent,
        ];
    
        Mail::send([], $data, function ($mailMessage) use ($toEmail, $subject, $messageContent) {
            $mailMessage->to($toEmail)
                        ->subject($subject)
                        ->html('<p>' . $messageContent . '</p>');
        });
    
        H::ExcelLog('Failure email sent: ' . $messageContent, $this->logFile);
    }
    
    
    
}
