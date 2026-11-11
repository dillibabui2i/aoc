<?php
namespace App\Helpers;
use Illuminate\Support\Facades\DB;

class NTHLanding
{
    public function getLanguagesFromNthMap()
    {
        return DB::table('maps_nth')->select('Language')->distinct()
        ->where('is_ignored',0)->where('Language','!=','')->get();
    }



    public function getNTHShiftDescription($accountId)
    {
        
        $days = ['', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
       

        $dbShifts = DB::table("NeedToHire_Schedules")->select("*")
        ->where("aoc_nthaccounts_id",$accountId)
        ->where("IsActive",1)
        ->orderBy("StartHour")
        ->orderBy("StartDayOfWeekInt")->get();
      
        $shifts = [];
        $scheduleDetails = '';
        foreach ($dbShifts as $shift) {
            
            $startAmPm = 'AM';
            $startHour = $shift->StartHour;
            if ($startHour >= 12) {
                $startAmPm = 'PM';
            }
            if ($startHour > 12) {
                $startHour = $startHour - 12;
            }
            $endHour = $shift->EndHour;
            $endAmPm = 'AM';
            if ($endHour >= 12) {
                $endAmPm = 'PM';
            }
            if ($endHour > 12) {
                $endHour = $endHour - 12;
            }
            $mytime = $startHour;
            if ($shift->StartMinute != 0) {
                $tmp = '0' . $shift->StartMinute;
                $tmp = substr($tmp, -2);
                $mytime .= $tmp;
            }
            $mytime .= $startAmPm . '-';
            $mytime .= $endHour;
            if ($shift->EndMinute != 0) {
                $mytime .= substr('0' . $shift->EndMinute, -2);
            }
            $mytime .= $endAmPm;
            $tmp = '';
            if (isset($shifts[$mytime])) {
                $tmp = $shifts[$mytime] . '&';
            }
            $tmp .= $days[$shift->StartDayOfWeekInt];
            $shifts[$mytime] = $tmp;
        }
       // echo "<pre>";
       // var_dump($shifts);
        
        $shiftDesc = '';
        foreach ($shifts as $key => $value) {
            if ($shiftDesc != '') {
                $shiftDesc .= ', ';
            }
            $tmp = $value;
           
            /*if (substr($tmp, 0, 2) == 'S&') {
                //var_dump($value);
                $tmp = substr($tmp, 2) . '&S';
               
            }*/
            if ($tmp == 'Mon&Tue&Wed&Thu&Fri&Sat&Sun') {
                $tmp = 'Mon-Sun';
            }
            if ($tmp == 'Mon&Tue&Wed&Thu&Fri&Sat') {
                $tmp = 'Mon-Sat';
            }
            if ($tmp == 'Mon&Tue&Wed&Thu&Fri') {
                $tmp = 'Mon-Fri';
            }
            if ($tmp == 'Mon&Tue&Wed&Thu') {
                $tmp = 'Mon-Thu';
            }
            if ($tmp == 'Mon&Tue&Wed') {
                $tmp = 'Mon-Wed';
            }
            if ($tmp == 'Mon&Tue') {
                $tmp = 'Mon-Tue';
            }
            if ($tmp == 'Tue&Wed&Thu&Fri&Sat&Sun') {
                $tmp = 'Tue-Sun';
            }
            if ($tmp == 'Tue&Wed&Thu&Fri&Sat') {
                $tmp = 'Tue-Sat';
            }
            if ($tmp == 'Tue&Wed&Thu&Fri') {
                $tmp = 'Tue-Fri';
            }
            if ($tmp == 'Tue&Wed&Thu') {
                $tmp = 'Tue-Thu';
            }
            if ($tmp == 'Tue&Wed') {
                $tmp = 'Tue-Wed';
            }
            if ($tmp == 'Wed&Thu&Fri&Sat&Sun') {
                $tmp = 'Wed-Sun';
            }
            if ($tmp == 'Wed&Thu&Fri&Sat') {
                $tmp = 'Wed-Sat';
            }
            if ($tmp == 'Wed&Thu&Fri') {
                $tmp = 'Wed-Fri';
            }
            if ($tmp == 'Wed&Thu') {
                $tmp = 'Wed-Thu';
            }
            if ($tmp == 'Thu&Fri&Sat&Sun') {
                $tmp = 'Thu-Sun';
            }
            if ($tmp == 'Thu&Fri&Sat') {
                $tmp = 'Thu-Sat';
            }
            if ($tmp == 'Thu&Fri') {
                $tmp = 'Thu-Fri';
            }
            if ($tmp == 'Fri&Sat&Sun') {
                $tmp = 'Fri-Sun';
            }
            if ($tmp == 'Fri&Sat') {
                $tmp = 'Fri-Sat';
            }
            if ($tmp == 'Sat&Sun') {
                $tmp = 'Sat-Sun';
            }
            $shiftDesc .= $tmp . ', ' . $key;
           
        }
        
        return $shiftDesc;
}

    public function generateTinyURL($clinicianId)
    {
        $finalTinyUrl = '';
        $encryptedClinicianId = H::getHelper("NTHLanding")->encrypt($clinicianId);
        if($encryptedClinicianId == '') {
            return $finalTinyUrl;
        }
        $url = route('nthlanding.nthRedirectPage', ['encryptedClinicianId' => $encryptedClinicianId]);
        $tinyData['url'] = $url;
        $responseArray = H::getHelper('ConstantHelper')->getTinyUrl($tinyData);
        if(!empty($responseArray)) {
            $finalTinyUrl = $responseArray['data']['tiny_url'];
        }
        return $finalTinyUrl;
    }
    public function encrypt($clinicianID)
    {
        try{
            $iv = random_bytes(16);
            $encrypted = openssl_encrypt($clinicianID,'AES-256-CBC',env('NTH_ENCRYPTION_PRIVATE_KEY'),0,$iv);

            if ($encrypted === false) {
                $encryptedData = '';
            } else {
                $encryptedData = base64_encode($iv . $encrypted);
            }
            return $encryptedData;
        } catch (\Exception $e) {
            H::ExcelLog('encrypt Exception='.$e->getMessage(),'nthEncrypt.log');
        }
    }

    public function decrypt($data)
    {
        try{
            $decoded = base64_decode($data);
            $iv = substr($decoded, 0, 16);
            $encrypted = substr($decoded, 16);

            return openssl_decrypt($encrypted,'AES-256-CBC',env('NTH_ENCRYPTION_PRIVATE_KEY'),0,$iv);
        } catch (\Exception $e) {
            H::ExcelLog('decrypt Exception='.$e->getMessage(),'nthDecrypt.log');
        }

    }

    public function checkNthInquiriesByDate($clinicianId,$to_date) {
        return DB::table('nth_inquiries')->where('ClinicianID',$clinicianId)
        ->whereRaw("date(created_at) = ?", [$to_date])
        ->first();
    }

    public function isFlexibleSchedule($nthaccountid){
        $flexible = DB::table("NeedToHire_Schedules")->select("ScheduleType")
        ->where("aoc_nthaccounts_id",$nthaccountid)
        ->where("IsActive",1)
        ->where("ScheduleType",1)
        ->first();

        if($flexible) {
            return 1;
        } else {
            return 0;
        }
    }
}
