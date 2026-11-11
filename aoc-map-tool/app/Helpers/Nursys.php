<?php
namespace App\Helpers;


use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\Crypt;
use Mail;

class Nursys
{
    public $privateToken;
    public $ch;

    public function getUserCredentials(){
        $passwordFromDB = $this->getPasswordFromDatabase();

        if(!empty($passwordFromDB)){
            $password = Crypt::decryptString($passwordFromDB);
        } else {
            $password = env("NURSYS_PASSWORD");
        }
        return array(
            'username: '.env("NURSYS_USERNAME"),
            'password: '.$password,
            'Content-Type: application/json');
    }

    

    public function getPasswordFromDatabase(){
        $currentPassword = DB::table("core_setting")->select("*")->where("opt","nursys_password")->first();
        if($currentPassword) {
            return $currentPassword->val;
        } else {
            return '';
        }
    }

    public function setNursyPasswordToDatabase($password){
        $currentPassword = DB::table("core_setting")->select("*")->where("opt","nursys_password")->first();
        
        if(empty($currentPassword)) {
            DB::table("core_setting")->insert(["opt"=>"nursys_password","val"=>$password]);
            
        } else {
            DB::table("core_setting")->where("opt","nursys_password")->update(["val"=>$password]);
        }
    }


    public function getLastEmailDateTime(){
        $nursysEmailTime = DB::table("core_setting")->select("*")->where("opt","nursys_email_time")->first();
        if($nursysEmailTime) {
            return $nursysEmailTime->val;
        } else {
            return '';
        }
    }

    public function setEmailDateTime(){
        $currentPassword = DB::table("core_setting")->select("*")->where("opt","nursys_email_time")->first();
        
        if(empty($currentPassword)) {
            DB::table("core_setting")->insert(["opt"=>"nursys_email_time","val"=>date("Y-m-d H:i:s")]);
        } else {
            DB::table("core_setting")->where("opt","nursys_email_time")->update(["val"=>date("Y-m-d H:i:s")]);
        }
    }

    

    public function randomPassword($len = 8) {

        //enforce min length 8
        if($len < 8) {
            $len = 8;
        }
    
        //define character libraries - remove ambiguous characters like iIl|1 0oO
        $sets = array();
        $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '0123456789';
    
        $password = '';
        
        //append a character from each set - gets first 4 characters
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }
    
        //use all characters to fill up to $len
        while(strlen($password) < $len) {
            //get a random set
            $randomSet = $sets[array_rand($sets)];
            
            //add a random char from the random set
            $password .= $randomSet[array_rand(str_split($randomSet))];
        }
        
        //shuffle the password string before returning!
        return str_shuffle($password);
    }

    public function getNursysEndPoint($parameter) {
        return env("NURSYS_BASEURL").$parameter;
    }

    public function initiateCurlRequest($url, $method, $data=[]) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->getUserCredentials(),
        ));
        if($method=='POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
       
        $response = curl_exec($curl);
        $obj = json_decode($response,true);
       
        if(!$obj['Transaction']['TransactionSuccessFlag']) {
            if(isset($obj['Transaction']['TransactionErrors'][0]['ErrorId'])) {
                if($obj['Transaction']['TransactionErrors'][0]['ErrorId']==100) {
                    // Authentication error
                    //echo "Send Email to support error";
                    $this->sendEmailToSupport();
                }
            }
        }
        
        curl_close($curl);
        return $response;
    }

    private function sendEmailToSupport(){
        $lastEmailDateTime = $this->getLastEmailDateTime();

        if(empty($lastEmailDateTime)) {
            $this->setEmailDateTime();
            Mail::send([], [], function ($message)  {
                $message->from(env('MAIL_FROM_ADDRESS'));
                $message->subject('URGENT:  AOC Nursys API password is invalid');
                $message->to('aocappalerts@angelsofcare.com');
                $message->text('The Nursys process encountered an error with the API.  Please update the password immediately.');
                
            });

        } else {

            $currentTime = date_create(date("Y-m-d H:i:s"));
            $pastTime = date_create($lastEmailDateTime);
    
            $interval = date_diff($currentTime, $pastTime);  
            $interval->format('%R%a days'); 
               
            $min = $interval->days * 24 * 60; 
            $min += $interval->h * 60; 
            $min += $interval->i; 
            
            if($min > 180) {
                $this->setEmailDateTime();
                Mail::send([], [], function ($message)  {
                    $message->from(env('MAIL_FROM_ADDRESS'));
                    $message->subject("URGENT:  AOC Nursys API password is invalid");
                    $message->to('aocappalerts@angelsofcare.com');
                    $message->text('The Nursys process encountered an error with the API.  Please update the password immediately.');
                    
                });
            } 
            
        }
       

    }

    

    public function saveTransactionToDatabase($type,$transactionId,$default) {
        if($default) {
            DB::table("nursys_transactions")->insert(['transaction_type'=>$type,"transaction_id"=>$transactionId]);
        } else {
            DB::table("nursys_transaction_onetime")->insert(['transaction_type'=>$type,"transaction_id"=>$transactionId]);
        }
    }

    public function deleteTransaction($transactionId,$default) {
        if($default) {
            DB::table("nursys_transactions")->where("transaction_id",$transactionId)->delete();
        } else {
            DB::table("nursys_transaction_onetime")->where("transaction_id",$transactionId)->delete();

        }
    }


    public function createNurseLookupTransaction($data,$default=1){
       
        $url = $this->getNursysEndPoint('nurselookup');
        $method = 'POST';
        $response = $this->initiateCurlRequest($url,$method,$data);
        $nursysTransaction = json_decode($response,true);
        if($nursysTransaction['Transaction']['TransactionSuccessFlag']) {
            $this->saveTransactionToDatabase("nursyslookup",$nursysTransaction['Transaction']['TransactionId'],$default);
        }
        return $nursysTransaction;
    }


    public function createManageNurseTransaction($data,$default=1){
        $url = $this->getNursysEndPoint('managenurselist');
        $method = 'POST';
        $response = $this->initiateCurlRequest($url,$method,$data);
        $nursysTransaction = json_decode($response,true);
        if($nursysTransaction['Transaction']['TransactionSuccessFlag']) {
            $this->saveTransactionToDatabase("managenurse",$nursysTransaction['Transaction']['TransactionId'],$default);
        }
        return $nursysTransaction;
    }

    public function createNewPasswordRequest($data){
        $url = $this->getNursysEndPoint('changepassword');
        $method = 'POST'; 

        $encryptedPassword = Crypt::encryptString($data['NewPassword']);
        $response = $this->initiateCurlRequest($url,$method,$data);
        $nursysTransaction = json_decode($response,true);
        H::ExcelLog("New Requested Password: " . $encryptedPassword,'nursys_pass.log');
        H::ExcelLog("Response: " . print_r($nursysTransaction,true),'nursys_pass.log');
        if(isset($nursysTransaction['Transaction']['TransactionSuccessFlag'])
            && $nursysTransaction['Transaction']['TransactionSuccessFlag']) {
            H::getHelper("Nursys")->setNursyPasswordToDatabase($encryptedPassword);
        }
        return $nursysTransaction;
    }

    public function performNurseLookupTransaction($transactionId,$default=1){


        $url = $this->getNursysEndPoint('nurselookup?transactionId='.$transactionId);
        $method = 'GET';
        $response = $this->initiateCurlRequest($url,$method);

        $nursysTransaction = json_decode($response,true);
        if(isset($nursysTransaction['ProcessingCompleteFlag']) && $nursysTransaction['ProcessingCompleteFlag']) {
            if(isset($nursysTransaction['Transaction']['TransactionSuccessFlag'])
                && $nursysTransaction['Transaction']['TransactionSuccessFlag']) {
                $this->deleteTransaction($nursysTransaction['Transaction']['TransactionId'],$default);
            }
        }
        return $nursysTransaction;
    }


    public function performManageUserListTransaction($transactionId,$default=1){

        $url = $this->getNursysEndPoint('managenurselist?transactionId='.$transactionId);
        $method = 'GET';
        $response = $this->initiateCurlRequest($url,$method);
        $nursysTransaction = json_decode($response,true);
        if(isset($nursysTransaction['ProcessingCompleteFlag']) && $nursysTransaction['ProcessingCompleteFlag']) {
            if(isset($nursysTransaction['Transaction']['TransactionSuccessFlag'])
                && $nursysTransaction['Transaction']['TransactionSuccessFlag']) {
                $this->deleteTransaction($nursysTransaction['Transaction']['TransactionId'],$default);
            }
        }
        return $nursysTransaction;
    }


}
