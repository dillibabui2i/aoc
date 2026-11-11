<?php

namespace App\Helpers;
use App\Models\Notes;
use DB;
class Helper
{

    private const OPENSSL_CIPHER_NAME = "aes-128-cbc";
    private const CIPHER_KEY_LEN = 16; //128 bits

    private static function fixKey($key, $keylen = Helper::CIPHER_KEY_LEN)
    {

        if (strlen($key) < $keylen) {
            //0 pad to len 16
            return str_pad("$key", $keylen, "0");
        }
        if (strlen($key) > $keylen) {
            //truncate to 16 bytes
            return substr($key, 0, $keylen);
        }

        return $key;
    }

    private static function getIV()
    {

        $ivlen = openssl_cipher_iv_length(Helper::OPENSSL_CIPHER_NAME);
        $secret_iv = '3aYMa25DtlA/0AWSPi05WA==';
        $secret_iv = self::fixKey($secret_iv, $ivlen);
        // iv - encrypt method AES-256-CBC expects 16 bytes 
        $iv = substr(hash('sha256', $secret_iv), 0, $ivlen);
        return $iv;
    }
    /**
     * Encrypt data using AES Cipher (CBC) with 128 bit key
     * 
     */
    static function encrypt($datastring)
    {

        $secret_key = 'KMLQOQbAQ8JRtWFVj6PbQmxbOssbWtGb';
        $secret_key = self::fixKey($secret_key);
        // hash
        $key = hash('sha256', $secret_key);
        $iv = self::getIV();
        $output = openssl_encrypt($datastring, Helper::OPENSSL_CIPHER_NAME, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    /**
     * Decrypt data using AES Cipher (CBC) with 128 bit key
     * 
     */
    static function decrypt($datastring)
    {
        $secret_key = 'KMLQOQbAQ8JRtWFVj6PbQmxbOssbWtGb';
        $secret_key = self::fixKey($secret_key);
        // hash
        $key = hash('sha256', $secret_key);
        $iv = self::getIV();
        $output = openssl_decrypt(base64_decode($datastring), Helper::OPENSSL_CIPHER_NAME, $key, 0, $iv);
        return $output;
    }

    /**
     * Return Json Response with custom format.
     * 
     * @param int $code - Pre-defined code
     * @param array $data - Processed valid array
     * @param string $message - Either error or success message string
     * @param string $isObject - Blank response should be in object or array
     * @param array $headers - A HTTP headers list
     * @param int $status - Status
     * @param int $options - Valid option
     * @return \Illuminate\Http\JsonResponse
     */
    static function response($code = 200, $data = [], $message = "", $isObject = 1, $headers = [], $status = 200, $options = 0)
    {
        $response_obj = array("code" => $code);
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        $response_obj["message"] = $message;
        if (empty($data)) {
            if ($isObject == 0) {
                $response_obj['data'] = [];
            } else if ($isObject == 1) {
                $response_obj['data'] = (object)[];
            }
        } else {
            $response_obj["data"] = $data;
        }

        return response()->json($response_obj, $status, $headers, $options);
    }
    static function plainResponse($code = 200, $data = [], $message = "", $isObject = 1, $headers = [], $status = 200, $options = 0)
    {
        $response_obj = $data;

        return response()->json($response_obj, $status, $headers, $options);
    }

    static function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return the server IP if the client IP is not found using this method.
    }

    public static function getFormatedDate($date)
    {
        if ($date != '0000-00-00 00:00:00' && $date != '') {
            return date('m/d/Y', strtotime($date));
        }

        return '';
    }
    
    public static function getFormatedTime($datetime)
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new \DateTime($datetime);

            return $datetime->format('g:i A');
        }

        return '';
    }
    public static function getReadableTime($time) {
        $stime = strtotime($time);
        return date("h:i A",$stime);
    }
    public static function getDateAndReadableTime($date){
        if ($date != '0000-00-00 00:00:00' && $date != '') {
            return date('m/d/Y h:i A', strtotime($date));
        }

        return '';
    }
    public static function getTimeDifference($firstTime,$secondTime){

        $data = [];
        $ftime = strtotime($firstTime);
        $stime = strtotime($secondTime);
        $mins = ($stime - $ftime) / 60;

        $data['totalminutes'] = $mins;
        $data['minutes'] = ($mins%60);
        $data['hours'] = round((float)($mins/60),2);
        $data['starthour']=date('H',$ftime);
        $data['startminute']=date('i',$ftime);
        $data['startAMPM'] = date("A",$ftime);
        $data['endAMPM'] = date("A",$stime);
        $data['endhour']=date('H',$stime);
        $data['endminute']=date('i',$stime);
        $data['StartAndEndOnSameDay'] = 1;

        if($data['totalminutes'] < 0) {

            $mins = (strtotime($secondTime." +1 days") - strtotime($firstTime)) / 60;
            $data['totalminutes'] = $mins;
            $data['minutes'] = ($mins%60);
            $data['hours'] = round((float)($mins/60),2);
            $data['StartAndEndOnSameDay'] = 0;
        }

        return $data;
    }

    public static function getDBFormatedDate($date)
    {
        if ($date != '0000-00-00 00:00:00' && $date != '') {
            return date('y-m-d', strtotime($date));
        }
        return '';
    }

    public static function ExcelLog($mytext = '', $logfile = '')
    {
        if (!empty($mytext) && !empty($logfile)) {
            $fname = explode('.', $logfile);
            $logfile = $fname[0] . '_' . date('Y-m-d') . '.log';

            $logtime = date('Y-m-d H:i:s');
            $msg = $logtime . '--->' . $mytext;
            $dirname = storage_path() . '/excelss/';
            $path = $dirname . $logfile;
            if (!file_exists($dirname)) {
                mkdir($dirname);
            }
            $fp = fopen($path, 'a');
            fwrite($fp, "$msg\n");
            fclose($fp);
        }
    }

    public static function setActivityNotes($data)
    {
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $insert = Notes::create($data);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public static function addNotification($data)
    {
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            DB::table('notifications')->insert($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format OTP error response based on SMS and Email results
     *
     * @param array $smsResult - Result from sendSMS() with 'success' and 'error' keys
     * @param array $emailResult - Result from sendEmailOtp() with 'success' and 'error' keys
     * @return array|null - Returns ['code' => int, 'message' => string] if there's an error, null if both succeeded
     */
    public static function formatOtpErrorResponse($smsResult, $emailResult)
    {
        if (!$smsResult['success'] && !$emailResult['success']) {
            return [
                'code' => config('constants.response_code.SOMETHING_WENT_WRONG'),
                'message' => 'SMS Error: ' . $smsResult['error'] . ' | Email Error: ' . $emailResult['error']
            ];
        }

        if (!$smsResult['success']) {
            return [
                'code' => config('constants.response_code.SOMETHING_WENT_WRONG'),
                'message' => 'SMS Error: ' . $smsResult['error']
            ];
        }

        if (!$emailResult['success']) {
            return [
                'code' => config('constants.response_code.SOMETHING_WENT_WRONG'),
                'message' => 'Email Error: ' . $emailResult['error']
            ];
        }

        return null;
    }
}
