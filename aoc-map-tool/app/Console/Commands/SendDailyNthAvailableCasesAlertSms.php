<?php

namespace App\Console\Commands;

use App\Events\Map\MapUpdatedEvent;
use App\MssqlProviders;
use App\Services\LogSq;
use App\TwilioErrorLogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class SendDailyNthAvailableCasesAlertSms extends Command
{
    private bool $mapTouched = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:DailyNthAvailableCasesAlertSms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $logFile = 'DailyNthAvailableCasesAlertSms.log';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::enableQueryLog();

        echo "\n Sending Daily NTH Available Cases Alert SMS \n";
        $missingPreferenceLogMsg = 'NTH Alert SMS sent to [ClinicianPhoneNumber] [DateTime]';
        $missingPreferenceMsg = "Hi [FirstName], Angels of Care has permanent [CliniciansDiscipline] opportunities near you! Tap the link to view cases: [TinyURL] Reply STOP to opt out.";
        $dateTime = date('m/d/Y H:i:s');
        DB::table('maps_nthalerts')
        ->select('id','ClinicianID', 'MobilePhone','weekly_sms_attempts')
        ->where('SMS_Status', 'Active')
        ->where('is_ignored', 0)
        ->where('is_active', 1)
        ->orderBy('ClinicianID')
        ->chunk(1000, function ($records) use ($missingPreferenceMsg, $missingPreferenceLogMsg,$dateTime) {
            foreach ($records as $record) {
                H::ExcelLog("Sending Daily NTH Available Cases Alert SMS for ClinicianID - " . $record->ClinicianID, $this->logFile);
                $sendSmsData = [];
                $sendSmsData['to'] = $record->MobilePhone;
                $sendSmsData['from'] = env('TWILIO_NTH_TOLL_FREE');
                $sendSmsData['messagingServiceSid'] = env('TWILIO_NTH_MESSAGING_SERVICE_SID');
                try {
                    $providerDetail = DB::table('mssql_providers')->select('id','FirstName','DisciplineName')->where('ClinicianID',$record->ClinicianID)->first();
                    echo $record->MobilePhone . "\n";
                    // Format the phone number
                    $sendSmsData['to'] = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($sendSmsData['to']);

                    H::ExcelLog("Check the opt-out table for Number - " . $sendSmsData['to'], $this->logFile);
                    // Check the opt-out table
                    $optOutDetails = DB::table('text_opt_out')->where('phone', $sendSmsData['to'])->first();
                    if (!empty($optOutDetails)) {
                        H::ExcelLog("Opt-out Number - " . $sendSmsData['to'] . " For ClinicianID - " . $record->ClinicianID, $this->logFile);
                        H::ExcelLog(print_r($optOutDetails,true),$this->logFile);

                        DB::table('maps_nthalerts')
                            ->where('id', $record->id)
                            ->update([
                                'SMS_Status' => MssqlProviders::UNSUBSCRIBED_SMS_STATUS,
                                'is_active'  => 0,
                            ]);

                        $this->mapTouched = true;

                        DB::table('mssql_providers')
                            ->where('ClinicianID', $record->ClinicianID)
                            ->update([
                                'SMS_Status' => MssqlProviders::UNSUBSCRIBED_SMS_STATUS
                            ]);

                        H::getHelper('ActivityNotes')->setActivityNotesFromConsole([
                            'entity'     => 'clinician',
                            'entity_id'  => $providerDetail->id ?? null,
                            'notes'      => 'NTH Alerts: Unsubscribed (STOP) detected pre-send. Deactivated.',
                        ]);

                        continue;
                    }

                    H::ExcelLog("Check Twilio Error Table for Number - " . $sendSmsData['to'], $this->logFile);

                    // Check for Twilio errors
                    $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number', $sendSmsData['to'])->first();
                    if (!empty($twilioErrorData)) {
                        H::ExcelLog("Twilio Error - " . $sendSmsData['to'] . " For ClinicianID - " . $record->ClinicianID, $this->logFile);
                        H::ExcelLog(print_r($twilioErrorData,true),$this->logFile);
                        continue;
                    }

                    H::ExcelLog("Twilio Lookup API for - " . $sendSmsData['to'], $this->logFile);
                    // Validate the phone number using Twilio Lookup API
                    $numberDetails = H::getHelper('MassMessageWidget')->checkValidNumber($sendSmsData['to']);
                    if (!empty($numberDetails) && $numberDetails->valid == 1) {
                        H::ExcelLog("Receiver Details",$this->logFile);
                        $tinyURL = H::getHelper("NTHLanding")->generateTinyURL($record->ClinicianID);
                        H::ExcelLog("tinyURL =".$tinyURL,$this->logFile);
                        $sendSmsMsg = str_replace(['[FirstName]', '[TinyURL]','[CliniciansDiscipline]'],[$providerDetail->FirstName, $tinyURL, $providerDetail->DisciplineName],$missingPreferenceMsg);
                        $sendSmsData['msg'] = $sendSmsMsg;
                        H::ExcelLog("Valid Number - " . $sendSmsData['to'], $this->logFile);
                        H::ExcelLog('Message Detail:'.$sendSmsMsg, $this->logFile);
                        $smsResponseLog =  H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);
                        H::ExcelLog(print_r($smsResponseLog,true),$this->logFile);


                        if (($smsResponseLog['successSent'] ?? 0) == TwilioErrorLogs::UNSUCCESS_CODE) {
                            $code = (int)($smsResponseLog['errorCode'] ?? TwilioErrorLogs::UNSUCCESS_CODE);

                            if ($code === TwilioErrorLogs::UNSUBSCRIBED_PHONE_CODE) {
                                DB::table('text_opt_out')
                                    ->updateOrInsert([
                                        'phone' => $sendSmsData['to']
                                    ]);

                                DB::table('maps_nthalerts')
                                    ->where('id', $record->id)
                                    ->update([
                                        'SMS_Status' => MssqlProviders::UNSUBSCRIBED_SMS_STATUS,
                                        'is_active'  => 0,
                                    ]);
                                $this->mapTouched = true;

                                DB::table('mssql_providers')
                                    ->where('ClinicianID', $record->ClinicianID)
                                    ->update([
                                        'SMS_Status' => MssqlProviders::UNSUBSCRIBED_SMS_STATUS,
                                    ]);

                                H::getHelper('ActivityNotes')->setActivityNotesFromConsole([
                                    'entity'     => 'clinician',
                                    'entity_id'  => $providerDetail->id ?? null,
                                    'notes'      => 'NTH Alerts: Unsubscribed via Twilio (21610). Deactivated.',
                                ]);

                                continue;
                            }

                            if (in_array($code, TwilioErrorLogs::BAD_PHONE_CODES)) {
                                H::ExcelLog('sendSMS ErrorCode='.$code.' && ErrorMessage='.$smsResponseLog['errorMessage'], $this->logFile);

                                $twilioErrorData = [
                                    'To'         => $sendSmsData['to'],
                                    'ErrorCode'  => $code,
                                    'SmsStatus'  => $smsResponseLog['status'] ?? 'undelivered',
                                ];

                                H::getHelper('MessageWidget')->handleTwilioError($twilioErrorData);

                                DB::table('maps_nthalerts')
                                    ->where('id', $record->id)
                                    ->update([
                                        'SMS_Status' => MssqlProviders::BAD_PHONE_NUMBER_SMS_STATUS,
                                        'is_active'  => 0,
                                    ]);
                                $this->mapTouched = true;

                                DB::table('mssql_providers')
                                    ->where('ClinicianID', $record->ClinicianID)
                                    ->update([
                                        'SMS_Status' => MssqlProviders::BAD_PHONE_NUMBER_SMS_STATUS,
                                    ]);
                            }
                        }

                        //Log
                        $missingPreferenceLogMsg = str_replace(['[ClinicianPhoneNumber]', '[DateTime]'],[$record->MobilePhone, $dateTime],$missingPreferenceLogMsg);
                        $smsSendNoteData = [];
                        $smsSendNoteData['entity'] = 'clinician';
                        $smsSendNoteData['entity_id'] = $providerDetail->id;
                        $smsSendNoteData['notes'] = $missingPreferenceLogMsg;
                        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($smsSendNoteData);
                    } else {
                        H::ExcelLog("Invalid Receiver Number - Twilio Error - " . $sendSmsData['to'] . " For ClinicianID - " . $record->ClinicianID, $this->logFile);
                    }
                } catch (\Throwable $th) {
                    H::ExcelLog($sendSmsData['to'] . " Error Inside Throwable - processSendMassMessage === " . $th->getMessage(), $this->logFile);
                }
            }
        });

        if ($this->mapTouched) {
            event(new MapUpdatedEvent('maps_nthalerts'));
        }

        LogSq::processSqlLogs('SendDailyNthAvailableCasesAlertSms');

        return Command::SUCCESS;
    }
}
