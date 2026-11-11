<?php
namespace App\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\BranchLocations as BranchLocations;
use App\Helpers\H;
use Mail;
use PDF as PdfAttachment;

class EmailNotification
{
    public function sendIpeaNotificationEmail($candidate, $ipeaData)
    {

        if(empty($ipeaData['ipea_location_id'])){
            H::ExcelLog('Error sending IPEA Notification Email because ipea_location_id is missing','emailErrors.log');
            return;
        }
        $branch = BranchLocations::find($ipeaData['ipea_location_id']);
        if(empty($branch->branch_email)) {
            H::ExcelLog('Error sending IPEA Notification Email because branch email is empty','emailErrors.log');
            return;
        }
        $email = $branch->branch_email;
        H::ExcelLog('Sending email to ' . $email . ' for ' . $candidate,'emailErrors.log');
        $subject = "A new IPEA has been scheduled for " . $candidate;
        $notification = "An IPEA has been scheduled for " . $candidate . "\n\n";
        $notification .= "Date: " . $ipeaData['appointment_date'] . "\n";
        $notification .= "Time: " . $ipeaData['appointment_time'] . "\n";
        $result = $this->sendEmailNotification($email, $subject, $notification,'text',$ipeaData);
    }

    public function sendEmailNotification($email, $subject, $notification,$type='text',$candidateData=[])
    {
        try {
            $data = [];
            $data['from_name']  =  'Angels Of Care';
            $data['from_email']  =  env('MAIL_FROM_ADDRESS');
            $data['body']  = $notification;
            $data['subject']  = $subject;
            $data['to_email']  = $email;
            $fileName = '';

            if(!empty($candidateData)) {
                H::ExcelLog('in Send Email 1','emailErrors.log');
                if(!empty($candidateData['aoc_candidate_id'])) {
                    H::ExcelLog('In Send Email 2','emailErrors.log');

                    $resumeContent = DB::table("candidates")->select("candidate_applications.resumeContent",DB::raw("CONCAT(candidates.firstName,'_',candidates.lastName) as candidatefullName"))
                    ->leftjoin("candidate_applications",function($q){
                        $q->on("candidates.id","=","candidate_applications.candidate_id");
                    })->where("candidate_id",$candidateData['aoc_candidate_id'])->first();

                    $rcontent = '';
                    if(isset($resumeContent->resumeContent)) {
                        $rcontent = $resumeContent->resumeContent;
                    }

                    $pdfContent = nl2br(ltrim($this->replaceBetween($rcontent,"{{DOCUMENT_DATESTAMP}}","{{END_DOCUMENT_DATESTAMP}}","")));
                    if(!empty($pdfContent)) {
                        if(!empty($resumeContent->candidatefullName)) {
                            $fileName = $resumeContent->candidatefullName.'_resume.pdf';
                        } else {
                            $fileName = $candidateData['aoc_candidate_id'].'_resume.pdf';
                        }


                        view()->share('content',$pdfContent);
                        $pdf = PdfAttachment::loadView('candidates.pdfdownload');
                        $pdf->save(public_path().'/'.$fileName);
                    }
                }
            }
            H::ExcelLog('in Send Email 3','emailErrors.log');

            Mail::send([], [], function ($message) use ($data,$type,$fileName) {
                $message->from( $data['from_email'],$data['from_name']);
                $message->subject($data['subject']);
                $message->to($data['to_email']);
                if ($type == 'html') {
                    $message->html($data['body']);
                } else {
                    $message->text($data['body']);
                }
                if(is_file(public_path().'/'.$fileName)) {
                    $destinationPath = public_path().'/'.$fileName;
                    $message->attach($destinationPath, array(
                        'as' => $fileName)
                    );
                }
            });
            $filePath = realpath(public_path().'/'.$fileName);
            if(is_file($filePath)) {
                unlink($filePath);
            }


        } catch (\Exception $ex) {
            //do something here
            H::ExcelLog($ex->getMessage(), 'sendEmailNotification.log');
        }
    }

    public function sendEmailsWithoutAttach($to,$subject,$body,$type='text'){
        H::ExcelLog('in sendEmailsWithoutAttach','emailErrors.log');

        try {
            $fromName  =  'Angels Of Care';
            $fromEmail  =  env('MAIL_FROM_ADDRESS');
            Mail::send([], [], function ($message) use ($type,$to,$body,$subject,$fromName,$fromEmail) {
                $message->from($fromEmail,$fromName);
                $message->subject($subject);
                $message->to($to);
                if ($type == 'html') {
                    $message->html($body);
                } else {
                    $message->text($body);
                }
            });
        } catch (\Exception $ex) {
            H::ExcelLog($ex->getMessage(), 'sendEmailNotification.log');
        }
    }

    public function replaceBetween($str, $needle_start, $needle_end, $replacement) {
        $pos = strpos($str, $needle_start);

        if($pos!==false) {
            $start = $pos == false ? 0 : $pos + strlen($needle_start);

            $pos = strpos($str, $needle_end, $start);
            $end = $pos == false ? strlen($str) : $pos;

            return str_replace($needle_start,"",str_replace($needle_end,"",substr_replace($str, $replacement, $start, $end - $start)));
        } else {
            return $str;
        }
    }

}
