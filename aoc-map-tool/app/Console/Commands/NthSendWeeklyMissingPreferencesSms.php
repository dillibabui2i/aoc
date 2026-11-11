<?php

namespace App\Console\Commands;

use App\Events\Map\MapUpdatedEvent;
use App\MssqlProviders;
use App\TwilioErrorLogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class NthSendWeeklyMissingPreferencesSms extends Command
{
    private bool $mapTouched = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:NthWeeklyMissingPreferencesSms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'NTH SMS | Send Weekly Missing Preferences SMS Until Preferences Are Saved';

    protected $logFile = 'NthWeeklyMissingPreferencesSms.log';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo "\n Sending NTH Weekly Missing Preferences SMS \n";
        $missingPreferenceLogMsg = 'Missing Preferences SMS sent to [ClinicianPhoneNumber] on [DateTime]';
        $missingPreferenceMsg = "Angels of Care: Hi [FirstName], we don't have your preferences. Update them here: [TinyURL] Reply STOP to opt out.";
        $removedFromPreferenceLogMsg = 'Removed from NTH Alerts list due to missing preferences on [DateTime].';
        $dateTime = date('m/d/Y H:i:s');
        DB::table('maps_nthalerts')
        ->select('id','ClinicianID', 'MobilePhone','weekly_sms_attempts')
        ->where('Preference_Status',0)
        ->where('SMS_Status', 'Active')
        ->where('is_ignored', 0)
        ->where('is_active', 1)
        ->orderBy('ClinicianID')
        ->chunk(1000, function ($records) use ($missingPreferenceMsg, $missingPreferenceLogMsg,$removedFromPreferenceLogMsg,$dateTime) {
            foreach ($records as $record) {
                H::ExcelLog("Sending NTH Weekly Missing Preferences SMS for ClinicianID - " . $record->ClinicianID, $this->logFile);
                $sendSmsData = [];
                $sendSmsData['to'] = $record->MobilePhone;
                $sendSmsData['from'] = env('TWILIO_NTH_TOLL_FREE');
                $sendSmsData['messagingServiceSid'] = env('TWILIO_NTH_MESSAGING_SERVICE_SID');
                try {
                    $providerDetail = DB::table('mssql_providers')->select('id','FirstName')->where('ClinicianID',$record->ClinicianID)->first();
                    //Set SMS status "Inactive - Missing Preferences"
                    if($record->weekly_sms_attempts == 6) {
                        H::ExcelLog("Weekly SMS Attempts Exceeded for - " . $record->ClinicianID, $this->logFile);
                        $updateData = ['SMS_Status' => 'Inactive - Missing Preferences'];
                        DB::table('mssql_providers')->where('ClinicianID',$record->ClinicianID)->update($updateData);
                        $updateData['is_active'] = 0;
                        DB::table('maps_nthalerts')->where('id',$record->id)->update($updateData);
                        $this->mapTouched = true;
                        $removedFromPreferenceLogMsg = str_replace('[DateTime]',$dateTime,$removedFromPreferenceLogMsg);
                        $noteData = [];
                        $noteData['entity'] = 'clinician';
                        $noteData['entity_id'] = $providerDetail->id;
                        $noteData['notes'] = $removedFromPreferenceLogMsg;

                        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                        continue;
                    }
                    echo $record->MobilePhone . "\n";
                    // Format the phone number
                    $sendSmsData['to'] = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($sendSmsData['to']);

                    H::ExcelLog("Check the opt-out table for Number - " . $sendSmsData['to'], $this->logFile);
                    // Check the opt-out table
                    $optOutDetails = DB::table('text_opt_out')->where('phone', $sendSmsData['to'])->first();
                    if (!empty($optOutDetails)) {
                        H::ExcelLog("Opt-out Number - " . $sendSmsData['to'] . " For ClinicianID - " . $record->ClinicianID, $this->logFile);

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
                        continue;
                    }

                    H::ExcelLog("Twilio Lookup API for - " . $sendSmsData['to'], $this->logFile);
                    // Validate the phone number using Twilio Lookup API
                    $numberDetails = H::getHelper('MassMessageWidget')->checkValidNumber($sendSmsData['to']);
                    if (!empty($numberDetails) && $numberDetails->valid == 1) {
                        H::ExcelLog("Receiver Details",$this->logFile);
                        $tinyURL = H::getHelper("NTHLanding")->generateTinyURL($record->ClinicianID);
                        H::ExcelLog("tinyURL =".$tinyURL,$this->logFile);
                        $sendSmsMsg = str_replace(['[FirstName]', '[TinyURL]'],[$providerDetail->FirstName, $tinyURL],$missingPreferenceMsg);
                        $sendSmsData['msg'] = $sendSmsMsg;
                        H::ExcelLog("Valid Number - " . $sendSmsData['to'], $this->logFile);
                        H::ExcelLog('Message Detail:'.$sendSmsMsg, $this->logFile);
                        H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);

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

                        DB::table('maps_nthalerts')->where('id', $record->id)
                            ->update([
                            'weekly_sms_attempts' => $record->weekly_sms_attempts + 1
                            ]);
                        $this->mapTouched = true;
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

        if($this->mapTouched){
            event(new MapUpdatedEvent('maps_nthalerts'));
        }

        return Command::SUCCESS;
    }
}
