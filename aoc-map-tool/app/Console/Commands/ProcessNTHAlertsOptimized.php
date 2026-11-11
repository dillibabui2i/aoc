<?php

namespace App\Console\Commands;

use App\Events\Map\MapUpdatedEvent;
use App\Services\LogSq;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use Illuminate\Support\Str;

/**
 * POC, the SOLID principles are broken. Even more batching and query optimizations can be done.
 */
class ProcessNTHAlertsOptimized extends Command
{
    private bool $mapTouched = false;
    protected $signature = 'process:nthalerts-optimize';
    protected $description = 'Process NTH Alerts Lists';
    protected $logFile = 'process_nthalerts.log';
    protected $currentDate;

    public function __construct()
    {
        $this->currentDate = date('Y-m-d');
        parent::__construct();
    }

    public function handle()
    {
        echo "Memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
        DB::enableQueryLog();

        H::ExcelLog("Starting process", $this->logFile);

        $providers = DB::table("mssql_providers")
            ->select(
                "mssql_providers.id",
                "mssql_providers.ProviderID",
                "mssql_providers.ClinicianID",
                DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as ProviderName"),
                "mssql_providers.DisciplineName",
                "mssql_providers.Mobile",
                "mssql_providers.External_Email",
                "mssql_employmentstatus.EmploymentStatus",
                "mssql_providers.Gender",
                "mssql_providers.Bilingual",
                "mssql_facilities.FacilityName",
                "mssql_providers.City",
                "mssql_providers.Address1",
                "mssql_providers.Address2",
                "mssql_providers.State",
                "mssql_providers.Zip",
                "mssql_providers.Language",
                "mssql_providers.aoc_cf_city",
                "mssql_providers.aoc_cf_state",
                "mssql_providers.aoc_cf_mile_radius",
                "mssql_providers.aoc_cf_skills",
                "mssql_providers.aoc_cf_schedule",
                "mssql_providers.aoc_cf_environment",
                "mssql_providers.aoc_cf_lifting_requirements",
                "mssql_providers.aoc_cf_language",
                'mssql_providers.CF_RemoveFromTextBlast',
                'mssql_providers.CF_NotEligibleForRehire',
                'mssql_providers.CF_DoNotContact',
                'mssql_providers.SMS_Status',
                'mssql_providers.Preference_Status',
                "maps_nthalerts.created_at"
            )
            ->leftJoin(
                "mssql_employmentstatus",
                "mssql_employmentstatus.EmploymentStatusID",
                "=",
                "mssql_providers.EmploymentStatusID"
            )
            ->leftJoin("mssql_facilities", "mssql_facilities.FacilityID", "=", "mssql_providers.FacilityID")
            ->leftJoin("maps_nthalerts", "mssql_providers.ClinicianID", "=", "maps_nthalerts.ClinicianID")
            ->where("mssql_providers.is_deleted", 0)
            ->whereIn("mssql_providers.DisciplineName", ["RN", "LVN", "LPN"])
            ->where(function ($q) {
                $q->where("mssql_providers.SMS_Status", "!=", "Unsubscribed")->orWhereNull(
                    "mssql_providers.SMS_Status"
                );
            })
            ->orderBy("id")
            ->get();

        H::ExcelLog("Total Providers : " . $providers->count(), $this->logFile);
        echo "\n Total Providers :" . $providers->count() . "\n";

        if ($providers->isEmpty()) {
            H::ExcelLog("No providers found.", $this->logFile);
            echo "No providers found.\n";
            return 0;
        }

        $providerIDs = $providers->pluck('ProviderID')->filter()->unique()->values()->all();
        $mobilesRaw = $providers->pluck('Mobile')->filter()->unique()->values()->all();

        $normalizedMobiles = array_map(function ($m) {
            return str_replace('-', '', $m);
        }, $mobilesRaw);

        [$lastWeek, $nextWeek] = $this->getDateRange();
        $schedulesRaw = DB::table("mssql_schedulemaster")
            ->select("ProviderID", "service_codes_daas.name as ServiceName", "mssql_schedulemaster.Hours")
            ->leftJoin("service_codes_daas", "mssql_schedulemaster.ServiceCodeID", "=", "service_codes_daas.service_id")
            ->where("mssql_schedulemaster.IsBillable", 1)
            ->whereIn("ProviderID", $providerIDs)
            ->whereIn("mssql_schedulemaster.ScheduleStatusID", [1, 2, 3, 5])
            ->whereDate("mssql_schedulemaster.ScheduleDate", ">", $lastWeek)
            ->whereDate("mssql_schedulemaster.ScheduleDate", "<=", $nextWeek)
            ->get();

        $allSchedules = $schedulesRaw->groupBy('ProviderID');

        H::ExcelLog("Preloaded schedules for providers: " . count($allSchedules), $this->logFile);

        $twilioQuery = DB::table("twilio_error_logs")->select("phone_number", "error_code");
        if (!empty($normalizedMobiles)) {
            $twilioQuery->where(function ($q) use ($normalizedMobiles) {
                foreach ($normalizedMobiles as $num) {
                    if (strlen($num) > 2) {
                        $q->orWhere("phone_number", "like", "%" . $num . "%");
                    }
                }
            });
        } else {
            $twilioQuery = null;
        }

        $twilioErrorsRaw = $twilioQuery ? $twilioQuery->get() : collect([]);
        $twilioErrors = $twilioErrorsRaw->groupBy('phone_number');

        H::ExcelLog("Preloaded twilio errors: " . $twilioErrorsRaw->count(), $this->logFile);

        $insert = [];
        $update = [];
        $updateProviders = [];

        foreach ($providers as $provider) {
            H::ExcelLog("\n\nProcessing : " . $provider->id, $this->logFile);

            $schedules = $allSchedules->has($provider->ProviderID) ? $allSchedules->get(
                $provider->ProviderID
            ) : collect();
            $hasBadMobile = $this->hasTwilioErrorFromCollection($provider->Mobile, $twilioErrors);
            [$include, $average, $SMSstatus, $reason] = $this->allowedRulesCached(
                $provider,
                $schedules,
                $hasBadMobile
            );

            H::ExcelLog("Can Include : " . $include, $this->logFile);
            H::ExcelLog("Weekly Average : " . $average, $this->logFile);
            H::ExcelLog("SMS Status : " . $SMSstatus, $this->logFile);
            H::ExcelLog("Reason : " . $reason, $this->logFile);

            if (!empty($SMSstatus)) {
                $updateProviders[$SMSstatus][] = $provider->id;

                H::ExcelLog("Current Status : " . $provider->SMS_Status, $this->logFile);
                H::ExcelLog("New Status : " . $SMSstatus, $this->logFile);

                if ($provider->SMS_Status != $SMSstatus) {
                    $noteData = [
                        'entity_id' => $provider->id,
                        'entity' => 'clinician',
                        'notes' => $reason,
                    ];

                    H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                    H::ExcelLog("Set Activity Note for Status change ", $this->logFile);
                }
            }

            var_dump(sprintf(
                '%s->%s->%s->%->%s',
                $provider->ClinicianID,
                $provider->created_at,
                $include,
                $SMSstatus,
                $average
            ));

            if (empty($provider->created_at)) {
                if ($include) {
                    $insert[] = $this->createDataArray('insert', $provider, $average, $SMSstatus, 1);
                    H::ExcelLog('Prepared Data for Insert', $this->logFile);
                }
            } else {
                if ($include) {
                    $update[] = $this->createDataArray('update', $provider, $average, $SMSstatus, 1);
                    H::ExcelLog('Updating Clinician (Active)', $this->logFile);
                } else {
                    $update[] = $this->createDataArray('update', $provider, $average, $SMSstatus, 0);
                    H::ExcelLog('Updating Clinician (InActive)', $this->logFile);
                }
            }

            H::ExcelLog("Finished\n\n " . $provider->id, $this->logFile);
        }

        // --- Batch update providers table SMS status (grouped) ---
        foreach ($updateProviders as $status => $ids) {
            DB::table("mssql_providers")->whereIn("id", $ids)->update(["SMS_Status" => $status]);
            H::ExcelLog(
                "Updated mssql_providers SMS_Status to '{$status}' for " . count($ids) . " providers",
                $this->logFile
            );
        }

        // --- Batch insert maps_nthalerts in chunks ---
        echo "\n\nInserting";
        echo "Total Insert : " . count($insert);
        H::ExcelLog("Total Insert: " . count($insert), $this->logFile);

        if (!empty($insert)) {
            $insertChunks = array_chunk($insert, 500);
            foreach ($insertChunks as $chunk) {
                DB::table("maps_nthalerts")->insert($chunk);
                echo "\n Inserting chunk.... \n";
                H::ExcelLog("Inserted chunk of " . count($chunk), $this->logFile);
                sleep(1); // keep original pacing behavior
            }
            $this->mapTouched = true;
        }
        H::ExcelLog("\nInsert Finished....", $this->logFile);

        // --- Updates for maps_nthalerts ---
        echo "\n Insert finished";
        echo "\n\nTotal Update : " . count($update);
        H::ExcelLog("Total Update: " . count($update), $this->logFile);
        echo "\nUpdating";

        // Original updated per ClinicianID; do same here (could be optimized with upsert if desired)
        foreach ($update as $data) {
            DB::table("maps_nthalerts")->where("ClinicianID", $data['ClinicianID'])->update($data);
            $this->mapTouched = true;
        }

        H::ExcelLog("Update Finished", $this->logFile);

        echo "Peak memory usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
        LogSq::processSqlLogs('nth-optimized');

        if ($this->mapTouched) {
            event(new MapUpdatedEvent('maps_nthalerts'));
        }

        return 0;
    }

    private function hasTwilioErrorFromCollection($mobile, $twilioErrorsGroup): bool
    {
        if (empty($mobile)) {
            return false;
        }

        $mobileNormalized = str_replace('-', '', $mobile);

        foreach ($twilioErrorsGroup as $phoneNumber => $group) {
            if (Str::contains($phoneNumber, $mobileNormalized)) {
                return true;
            }
        }

        return false;
    }

    private function allowedRulesCached($provider, $schedules, $hasBadMobile): array
    {
        $canInclude = 0;
        $SMSstatus = 'Inactive - Case Assignment';
        $average = 0;
        $reason = 'Status: Inactive';
        $defaultNote = 'Update to NTH Alerts Subscription Status <br>';

        H::ExcelLog("Clnicain DB Status : " . $provider->EmploymentStatus, $this->logFile);

        $status = $provider->EmploymentStatus;

        if ($status == 'Active' || $status == 'Pending Renewable') {
            $average = $this->getScheduleCount($schedules, ["average"]);

            if ($average < 12) {
                $canInclude = 1;
                $SMSstatus = 'Active';
                $reason = 'Status: Active';
            } else {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Case Assignment';
                $reason = 'Status: Inactive';
                $reason .= "<br>Reason: " . $SMSstatus;
            }
        } elseif ($status == 'Active Coverage') {
            // original logic: exclude ServiceName like '%Coverage%' in query,
            // so here we remove schedules that contain "Coverage" in ServiceName
            $coverageExcluded = collect($schedules)->filter(function ($s) {
                if (empty($s->ServiceName)) {
                    return true;
                }
                return !Str::contains($s->ServiceName, 'Coverage');
            })->values();

            $total = $this->getScheduleCount($coverageExcluded, ["total"]);
            $average = $this->getScheduleCount($coverageExcluded, ["average"]);

            if ($total > 12) {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Case Assignment';
                $reason = 'Status: Inactive';
                $reason .= "<br>Reason: " . $SMSstatus;
            } else {
                $canInclude = 1;
                $SMSstatus = 'Active';
                $reason = 'Status: Active';
            }
        } elseif (in_array($status, ['PRN Agreement', 'Standby', 'Inactive', 'Terminated'])) {
            $average = $this->getScheduleCount($schedules, ["average"]);
            $canInclude = 1;
            $SMSstatus = 'Active';
            $reason = 'Status: Active';
        }

        if ($SMSstatus == 'Active') {
            if ($provider->CF_RemoveFromTextBlast == 1
                || $provider->CF_NotEligibleForRehire == 1
                || $provider->CF_DoNotContact == 1) {
                $canInclude = 0;
                $SMSstatus = 'Inactive - Advocacy';
                $reason = 'Status: Inactive';
                $reason .= "<br>Reason: " . $SMSstatus;
            } else {
                if ($hasBadMobile) {
                    $canInclude = 0;
                    $SMSstatus = 'Inactive - Bad phone number';
                    $reason = 'Status: Inactive';
                    $reason .= "<br>Reason: " . $SMSstatus;
                }
            }
        }

        return [$canInclude, $average, $SMSstatus, $defaultNote . $reason];
    }

    private function getScheduleCount($schedules, $op = [])
    {
        $operation = $op[0] ?? null;
        $sum = 0;

        if (empty($schedules)) {
            return 0;
        }

        $coll = is_array($schedules) ? collect($schedules) : $schedules;

        foreach ($coll as $schedule) {
            $hours = isset($schedule->Hours) ? floatval($schedule->Hours) : 0;
            $sum += $hours;
        }

        if ($operation == 'average') {
            if ($sum) {
                return $sum / 4;
            }
            return 0;
        }

        if ($operation == 'total') {
            if ($sum) {
                return $sum;
            }
            return 0;
        }

        return 0;
    }

    private function createDataArray($mode, $provider, $average, $SMSstatus, $isActive = 1)
    {
        $pref = $provider->Preference_Status;

        if (empty($provider->aoc_cf_city) && empty($provider->aoc_cf_state) && empty($provider->aoc_cf_mile_radius)) {
            $pref = "0";
        }

        $data = [
            'ClinicianID' => $provider->ClinicianID,
            'ProviderName' => $provider->ProviderName,
            'Discipline' => $provider->DisciplineName,
            'MobilePhone' => $provider->Mobile,
            'ExternaleEmail' => $provider->External_Email,
            'ProviderStatus' => $provider->EmploymentStatus,
            'Gender' => $provider->Gender,
            'WeeklyAverage' => $average,
            'IsBilingual' => $provider->Bilingual,
            'HomeBranch' => $provider->FacilityName,
            'Address1' => $provider->Address1,
            'Address2' => $provider->Address2,
            'City' => $provider->City,
            'State' => $provider->State,
            'Zip' => $provider->Zip,
            'Language' => $provider->Language,
            'City_Preference' => $provider->aoc_cf_city,
            'State_Preference' => $provider->aoc_cf_state,
            'Mile_Preference' => $provider->aoc_cf_mile_radius,
            'Schedule_Preference' => $provider->aoc_cf_skills,
            'Skill_Preference' => $provider->aoc_cf_schedule,
            'Environment_Preference' => $provider->aoc_cf_environment,
            'Lifting_Preference' => $provider->aoc_cf_lifting_requirements,
            'Language_Preference' => $provider->aoc_cf_language,
            'Preference_Status' => $pref,
            'SMS_Status' => $SMSstatus,
            'is_active' => $isActive,
        ];

        if ($mode == 'insert') {
            $data['created_at'] = date("Y-m-d H:i:s");
            $data['updated_at'] = date("Y-m-d H:i:s");
        } else {
            $data['updated_at'] = date("Y-m-d H:i:s");
        }

        return $data;
    }

    private function getDateRange()
    {
        $lastWeek = date('Y-m-d', strtotime("sunday -3 week"));
        $nextWeek = date('Y-m-d', strtotime("sunday 1 week"));

        return [$lastWeek, $nextWeek];
    }
}
