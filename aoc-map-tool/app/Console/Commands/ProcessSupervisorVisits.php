<?php

namespace App\Console\Commands;

use App\Enums\MssqlEmploymentStatus;
use App\Enums\MssqlPatientStatus;
use App\Enums\MssqlScheduleMasterStatus;
use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSupervisorVisits extends Command
{

    private const STATE_AZ = 'AZ';

    private const DISCIPLINE_LHA = 'LHA';

    private const PDN_DISCIPLINES = [
        'RN',
        'LVN',
        'LPN',
    ];

    private const REMOVABLE_PROVIDER_STATUSES = [
        MssqlEmploymentStatus::TERMINATED->value,
        MssqlEmploymentStatus::ON_LEAVE->value,
    ];

    private const REMOVABLE_PATIENTS_STATUSES = [
        MssqlPatientStatus::DISCHARGED->value,
        MssqlPatientStatus::TRANSFERRED->value,
    ];

    protected $signature = 'process:supervisorvisits';

    protected $description = 'Inserting the missing renewables with checking renewables rules with clinicians';

    protected array $allowedScheduleStatus = [
        MssqlScheduleMasterStatus::APPROVED->value,
        MssqlScheduleMasterStatus::COMPLETED->value,
        MssqlScheduleMasterStatus::IN_PROGRESS->value,
    ];

    protected array $allowedDiscipline = ['RN', 'LVN', 'LPN', 'CNA', 'LHA', 'IHSS', 'AIDE'];

    protected string $currentDate = '';

    protected string $pastScheduleDate = '';

    protected $patient = null;

    protected $provider = null;

    protected string $logFile = 'supervisory_visits_backend.log';

    protected $cycle = null;

    protected $lastVisit = null;

    public function __construct()
    {
        $this->currentDate = date('Y-m-d');
        $this->pastScheduleDate = date('Y-m-d', strtotime('-1 years'));

        parent::__construct();
    }

    public function handle()
    {
        $terminatedProviders = [];

        $supervisorData = [];
        echo "\n  Processing Clinician Future Visits Removal: \n";
        $this->removeBasedOnFutureVisits();
        echo "\n  Completed : \n";

        echo "\n  Removing AZ PDN(RN,LVN,LPN) future visits: \n";
        $this->removeArizonaPdnFutureVisits();
        echo "\n  Completed : \n";
        H::ExcelLog(' Completed :', $this->logFile);

        $providersQuery = DB::table('mssql_providers')
            ->select(
                'mssql_providers.id',
                'mssql_providers.ProviderID',
                'mssql_providers.DisciplineName',
                'mssql_providers.EmploymentStatusID',
                'mssql_providers.BillingProviderID',
                'mssql_providers.TerminatedDate',
                'mssql_providers.CF_GraduateCNA',
                'mssql_providers.HiredDate'
            )
            ->where('mssql_providers.isOfficeStaff', 0)
            ->whereIn('mssql_providers.DisciplineName', $this->allowedDiscipline)
            ->orderBy('mssql_providers.id');

        $totalProviders = (clone $providersQuery)->count();

        H::ExcelLog('Total Providers : ' . $totalProviders, $this->logFile);
        echo "\n Total Providers :" . $totalProviders . "\n";
        foreach ($providersQuery->cursor() as $provider) {
            echo "\n\n*****************************************************************************\n";
            echo 'Processing Provider-Discipline : ' . htmlspecialchars(
                    $provider->ProviderID,
                    ENT_QUOTES,
                    'UTF-8'
                ) . '-' . htmlspecialchars($provider->DisciplineName, ENT_QUOTES, 'UTF-8') . "\n";

            H::ExcelLog('*****************************************************************************', $this->logFile);
            H::ExcelLog('Provider-Discipline:' . $provider->ProviderID . '-' . $provider->DisciplineName, $this->logFile);

            if ($provider->EmploymentStatusID == MssqlEmploymentStatus::TERMINATED->value) {
                $terminatedProviders[] = $provider;
            }

            $this->provider = $provider;
            $clients = $this->getClientsFromProvider();

            foreach ($clients as $client) {
                if ($client->PatientID) {
                    if ($this->checkFutureDate($client)) {
                        echo "\n---------------\n";
                        echo 'Processing Client-State : ' . htmlspecialchars(
                                $client->PatientID,
                                ENT_QUOTES,
                                'UTF-8'
                            ) . '-' . htmlspecialchars($client->State, ENT_QUOTES, 'UTF-8') . "\n";

                        H::ExcelLog('---------------', $this->logFile);
                        H::ExcelLog('Processing Client-State : ' . $client->PatientID . '-' . $client->State, $this->logFile);

                        $this->patient = $client;
                        $supervisorData = $this->processSupervisoryVisit($supervisorData);
                    }
                }
            }
        }

        echo "\n Processing Insertion : \n";
        H::ExcelLog('Processing Insertion', $this->logFile);
        if (!empty($supervisorData)) {
            $insertChunks = array_chunk($supervisorData, 500);
            foreach ($insertChunks as $chunk) {
                DB::table('supervisory_visits')->insertOrIgnore($chunk);
                echo "\n Inserting.... \n";
                H::ExcelLog('Inserting....', $this->logFile);
                sleep(1);
            }
        }
        echo "\n  Insertion Finished : \n";
        H::ExcelLog('Insertion Finished :', $this->logFile);
        echo "\n  Processing Removal: \n";
        $this->removeTerminatedProvidersVisit($terminatedProviders);
        echo "\n  Terminated Clinician Removed: \n";

        echo "\n  Processing Clinician Future Visits Removal: \n";
        $this->removeBasedOnFutureVisits();
        echo "\n  Completed : \n";
        H::ExcelLog(' Completed :', $this->logFile);
        return 0;
    }

    public function removeBasedOnFutureVisits()
    {

        $supervisoryVisits = DB::table('supervisory_visits')
            ->select(
                'supervisory_visits.id',
                'mssql_patients.PatientID',
                'mssql_providers.ProviderID',
                'mssql_providers.EmploymentStatusID',
                'mssql_patients.Patient_CurrentStatusID'
            )
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id')
            ->leftJoin('mssql_providers', 'mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id')
            ->whereNull('completed_by')
            ->active()
            ->cursor();

        $removalListSV = [];

        foreach ($supervisoryVisits as $svisit) {
            $providerId = $svisit->ProviderID;
            $patientId = $svisit->PatientID;
            H::ExcelLog('Checking Patient ' . $patientId . ' with ' . $providerId, 'supervisoryVisitRemovals.log');

            $shouldRemoveVisit =
                in_array((int)$svisit->EmploymentStatusID, self::REMOVABLE_PROVIDER_STATUSES, true)
                || in_array((int)$svisit->Patient_CurrentStatusID, self::REMOVABLE_PATIENTS_STATUSES, true);

            if (!$shouldRemoveVisit) {
                continue;
            }

            $removalListSV[] = $svisit->id;

            H::ExcelLog('Remove Visit: ' . $svisit->id, 'supervisoryVisitRemovals.log');

            echo 'Remove Visit: ' . htmlspecialchars($svisit->id, ENT_QUOTES, 'UTF-8') . "\n";
        }

        H::ExcelLog(print_r($removalListSV, true), 'supervisoryVisitRemovals.log');

        if (!empty($removalListSV)) {
            foreach (array_chunk($removalListSV, 500) as $chunk) {
                DB::table('supervisory_visits')
                    ->whereIn('id', $chunk)
                    ->softDelete();
            }
        }
    }

    public function checkFutureDate($client): int
    {
        echo "\n Checking Future Date exist : ";

        $providerId = $this->provider->ProviderID;
        $patientId = $client->PatientID;

        $calculateScheduleDate = date('Y-m-d', strtotime('-7 days'));

        $scheduleDate = DB::table('mssql_schedulemaster')
            ->select('ScheduleDate')
            ->where('ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value)
            ->where('ProviderID', $providerId)
            ->where('PatientID', $patientId)
            ->where('mssql_schedulemaster.IsBillable', 1)
            ->whereDate('ScheduleDate', '>=', $calculateScheduleDate)
            ->first();

        if ($scheduleDate) {
            return 1;
        } else {
            return 0;
        }
    }

    public function removeTerminatedProvidersVisit($providers)
    {
        foreach ($providers as $provider) {
            DB::table('supervisory_visits')
                ->where('aoc_provider_id', $provider->id)
                ->whereDate('visit_date_due', '>', date('Y-m-d', strtotime($provider->TerminatedDate)))
                ->whereNull('completed_by')
                ->active()
                ->softDelete();
        }
    }

    public function processSupervisoryVisit($supervisorData)
    {
        $this->removeOutdatedArizonaLhaFutureVisits();

        if ($this->getMaxSupervisorVisits()) {
            $isTerminated = 0;

            if ($this->provider->EmploymentStatusID == MssqlEmploymentStatus::TERMINATED->value) {
                $isTerminated = 1;
            }

            $nextDueDate = $this->getCalculatedDueDate($isTerminated);

            if ($nextDueDate != 'N/A') {
                $clientCCM = $this->getClient();
                H::ExcelLog(
                    'aoc_patient_id: ' . $clientCCM->id . ' patient CCM id: ' . $clientCCM->ccm_id,
                    $this->logFile
                );

                $existingSupVisit = DB::table('supervisory_visits')
                    ->select('id', 'visit_date_due', 'completed_by')->where('aoc_patient_id', $clientCCM->id)
                    ->where('aoc_provider_id', $this->provider->id)
                    ->where('visit_date_due', $nextDueDate)
                    ->where('last_visit', $this->lastVisit)
                    ->where(function ($query) {
                        $query->where('last_visit', '=', $this->lastVisit)
                            ->orWhereNull('last_visit');
                    })
                    ->orderBy('id', 'desc')
                    ->limit(1)
                    ->first();

                if (empty($existingSupVisit)) {
                    $supervisorData[] = [
                        'aoc_provider_id' => $this->provider->id,
                        'aoc_patient_id' => $clientCCM->id,
                        'visit_date_due' => $nextDueDate,
                        'assigned_to' => $clientCCM->ccm_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'updated_by' => 1,
                        'last_visit' => $this->lastVisit,
                        'cycle' => $this->cycle,
                    ];
                } else {
                    H::ExcelLog(
                        'Same date record exist for aoc_patient_id: ' . $clientCCM->id .
                        ' patient CCM id: ' . $clientCCM->ccm_id .
                        ' aoc_provider_id: ' . $this->provider->id .
                        'last visit:' . $this->lastVisit .
                        'visit date due:' . $nextDueDate,
                        $this->logFile
                    );
                }
            }
        }
        return $supervisorData;
    }

    public function getClientsFromProvider()
    {
        return DB::table('mssql_schedulemaster')
            ->distinct()
            ->select('mssql_schedulemaster.PatientID', 'f.State')
            ->leftJoin('mssql_facilities as f', 'mssql_schedulemaster.FacilityID', '=', 'f.FacilityID')
            ->leftJoin('mssql_patients', 'mssql_schedulemaster.PatientID', '=', 'mssql_patients.PatientID')
            ->whereIn('ScheduleStatusID', $this->allowedScheduleStatus)
            ->where('ProviderID', $this->provider->ProviderID)
            ->whereNotIn('mssql_patients.Patient_CurrentStatusID', [
                MssqlPatientStatus::DISCHARGED->value,
                MssqlPatientStatus::TRANSFERRED->value,
            ])
            ->whereDate('ScheduleDate', '<', $this->currentDate)
            ->whereDate('ScheduleDate', '>', $this->pastScheduleDate)
            ->where('mssql_schedulemaster.IsBillable', 1)
            ->where('mssql_patients.IsLatestAdmit', 1)
            ->whereRaw('ifnull(mssql_patients.PatientID,0) <> 0')
            ->get();
    }

    public function canSkipTerminatedProvider()
    {
        echo "\n Processing Terminated Provider \n";
        H::ExcelLog(' Processing Terminated Provider :', $this->logFile);

        $dayCycle = $this->getNextScheduleDay();

        if ($dayCycle != 'N/A') {
            $baseDate = $this->getBaseDate();
            $nextSchedule = $this->getNextDueDate($dayCycle, $baseDate);

            $nextScheduleDateTime = strtotime($this->getNextDueDate($dayCycle, $baseDate));
            $terminatedDate = strtotime($this->provider->TerminatedDate);

            if ($nextScheduleDateTime < $terminatedDate) {
                echo "\n Insert Provider. Terminated Date : " . htmlspecialchars(
                        $this->provider->TerminatedDate,
                        ENT_QUOTES,
                        'UTF-8'
                    ) . " \n";
                H::ExcelLog('Insert Provider. Terminated Date:' . $this->provider->TerminatedDate, $this->logFile);
                return 0;
            } else {
                echo "\n Skip Provider. NXT Date: " . htmlspecialchars($nextSchedule, ENT_QUOTES, 'UTF-8') .
                    ' T.Date: ' . htmlspecialchars($this->provider->TerminatedDate, ENT_QUOTES, 'UTF-8') . " \n";
                H::ExcelLog(
                    'Skip Provider. NXT Date: ' . $nextSchedule . ' T.Date:' . $this->provider->TerminatedDate,
                    $this->logFile
                );
                return 1;
            }
        } else {
            echo "\n Visit Cycle not exist.terminating further process.(terminated provider) \n";
            H::ExcelLog('Visit Cycle not exist.terminating further process.(terminated provider)', $this->logFile);

            return 1;
        }
    }

    public function getMaxSupervisorVisits()
    {
        $patient = $this->getClient();

        $canProcess = 0;

        if (!$patient) {
            H::ExcelLog('Client not exist in db', $this->logFile);
            echo "\nClient not exist in db\n";
            return 0;
        }

        $patientId = $patient->id;
        $providerId = $this->provider->id;
        $empStatusId = $this->provider->EmploymentStatusID;

        $supervisor = DB::table('supervisory_visits')
            ->select('id', 'visit_date_due', 'completed_by')
            ->where('aoc_patient_id', $patientId)
            ->where('aoc_provider_id', $providerId)
            ->orderBy('id', 'desc')
            ->active()
            ->limit(1)
            ->first();

        if (!$supervisor) {
            $canProcess = -1;
            echo "\nSupervisory Record Not Exist\n";
            H::ExcelLog('Supervisory Record Not Exist', $this->logFile);
            if ($empStatusId == MssqlEmploymentStatus::TERMINATED->value) {
                echo "\n its terminated provider \n";

                H::ExcelLog('its terminated provider', $this->logFile);
                $canSkip = $this->canSkipTerminatedProvider();
                if ($canSkip) {
                    $canProcess = 0;
                }
            }
        } elseif (!empty($supervisor->completed_by)) {
            $canProcess = 1;
            echo "\n Supervisory Record Completed \n";
            H::ExcelLog('Supervisory Record Completed', $this->logFile);
            if ($empStatusId == MssqlEmploymentStatus::TERMINATED->value) {
                echo "\n its terminated provider \n";
                H::ExcelLog(' its terminated provider', $this->logFile);
                $canSkip = $this->canSkipTerminatedProvider();
                if ($canSkip) {
                    $canProcess = 0;
                }
            }
        } else {
            echo "\n Supervisory Record not Completed \n";
            H::ExcelLog('Supervisory Record not Completed', $this->logFile);
            $canProcess = 0;
            // Current superisor visit is not completed

        }
        return $canProcess;
    }

    public function getBaseDate($checkMaster = 1, $isMax = 0)
    {
        echo "\n Inside getting Base Date \n";
        H::ExcelLog('Inside getting Base Date', $this->logFile);
        $patientId = $this->patient->PatientID;
        $providerId = $this->provider->ProviderID;

        $masterDate = $this->getLastCompletedDateFromMaster();
        $string = '';
        if ($masterDate && $checkMaster) {
            echo "\n Base Date from Master : " . htmlspecialchars($masterDate->baseDate, ENT_QUOTES, 'UTF-8') . "\n";
            H::ExcelLog('Base Date from Master : ' . $masterDate->baseDate, $this->logFile);
            return $masterDate->baseDate;
        } else {
            if ($isMax) {
                $string = 'max(ScheduleDate) as baseDate';
            } else {
                $string = 'min(ScheduleDate) as baseDate';
            }

            $scheduleDate = DB::table('mssql_schedulemaster')
                ->select(DB::raw($string))
                ->whereIn('ScheduleStatusID', $this->allowedScheduleStatus)
                ->where('ProviderID', $providerId)
                ->where('PatientID', $patientId)
                ->whereDate('ScheduleDate', '<', $this->currentDate)
                ->whereDate('ScheduleDate', '>', $this->pastScheduleDate)
                ->where('mssql_schedulemaster.IsBillable', 1)
                ->first();

            return $scheduleDate->baseDate;
        }
    }

    public function getCalculatedDueDate($isTerminated)
    {
        if ($isTerminated) {
            $dayAddition = $this->getNextScheduleDay();
            $baseDate = $this->getBaseDate();

            $nextDueDate = $this->getNextDueDate($dayAddition, $baseDate);
            echo "\nNext Due Date: " . htmlspecialchars($nextDueDate, ENT_QUOTES, 'UTF-8') . "\n";
            H::ExcelLog('Next Due Date: ' . $nextDueDate, $this->logFile);
            return $nextDueDate;
        } else {
            $dayAddition = $this->getNextScheduleDay();
            echo "\nVisit Cycle: " . htmlspecialchars($dayAddition, ENT_QUOTES, 'UTF-8') . "\n";
            H::ExcelLog('Visit Cycle: ' . $dayAddition, $this->logFile);
            if ($dayAddition != 'N/A') {
                $baseDate = $this->getBaseDate();
                $this->cycle = $dayAddition;
                $this->lastVisit = $baseDate;
                echo "\nBase Date: " . htmlspecialchars($baseDate, ENT_QUOTES, 'UTF-8') . "\n";
                H::ExcelLog('Base Date: ' . $baseDate, $this->logFile);
                $nextDueDate = $this->getNextDueDate($dayAddition, $baseDate);
                echo "\nNext Due Date:: " . htmlspecialchars($nextDueDate, ENT_QUOTES, 'UTF-8') . "\n";
                H::ExcelLog('Next Due Date:: ' . $nextDueDate, $this->logFile);
                return $nextDueDate;
            }
            return 'N/A';
        }
    }

    public function getClient()
    {
        $clientId = $this->patient->PatientID;
        return DB::table('mssql_patients')
            ->select('*')
            ->where('PatientID', $clientId)
            ->where('IsLatestAdmit', '1')
            ->first();
    }

    public function getLastCompletedDateFromMaster()
    {
        $patient = $this->getClient();

        return DB::table('supervisory_form_master')
            ->select(DB::raw('form_date as baseDate'))
            ->where('client_id', $patient->BillingInternalPatientID)
            ->where('supervisee_cgid', $this->provider->BillingProviderID)
            ->orderBy('form_date', 'desc')
            ->first();
    }

    public function getDaysForLHA($default, $diff)
    {
        echo "\nDate Difference From current date: " . htmlspecialchars($diff, ENT_QUOTES, 'UTF-8') . "\n";
        H::ExcelLog('Date Difference From current date:' . $diff, $this->logFile);
        if ($diff < 7) {
            echo "\n return 7 visit cycle due to additional service \n";
            H::ExcelLog('return 7 visit cycle due to additional service', $this->logFile);
            return 7;
        } elseif ($diff < 30) {
            echo "\n return 30 visit cycle due to additional service \n";
            H::ExcelLog('return 30 visit cycle due to additional service', $this->logFile);
            return 30;
        } else {
            echo "\n Default " . htmlspecialchars($default, ENT_QUOTES, 'UTF-8') . " visit cycle due to add service \n";
            H::ExcelLog('Default ' . $default . ' visit cycle due to add service', $this->logFile);
            return $default;
        }
    }

    public function getDaysForCNAAIDE(): int
    {
        return 14;
    }

    public function getNextScheduleDay()
    {
        echo "\n--- Inside to get Next Schedule Day--\n";
        H::ExcelLog('Inside to get Next Schedule Day', $this->logFile);
        $nextDay = 'N/A';

        $state = $this->patient->State;
        $discipline = $this->provider->DisciplineName;

        $visit = DB::table('supervisory_visits_matrix')
            ->select('visit_cycle')
            ->where('state', $state)
            ->where('discipline', $discipline)
            ->first();

        if (!$visit) {
            return $nextDay;
        }
        $days = $visit->visit_cycle;

        if ($state === self::STATE_AZ && in_array($discipline, self::PDN_DISCIPLINES, true)) {
            H::ExcelLog(
                'AZ PDN discipline detected. Supervisory visit generation skipped for PDN clinician.',
                $this->logFile
            );

            return 'N/A';
        }

        if ($state === self::STATE_AZ && $discipline === self::DISCIPLINE_LHA) {
            return $this->getArizonaLhaVisitCycle((int)$days);
        }

        if (in_array($discipline, ['LHA'])) {
            echo "\n--- Discipline LHA--\n";
            H::ExcelLog('Discipline LHA', $this->logFile);
            // check for additional service
            $isAdditionalService = $this->isAdditionalService();
            if ($isAdditionalService > 1) {
                echo "\n------Yes Additional Service------\n";
                H::ExcelLog('Yes Additional Service', $this->logFile);
                $minimumDate = $this->getBaseDate(0, 0);

                if (!empty($minimumDate)) {
                    echo "\nMinimum Date: " . htmlspecialchars($minimumDate, ENT_QUOTES, 'UTF-8') . "\n";
                    H::ExcelLog('Minimum Date:' . $minimumDate, $this->logFile);
                    $diff = $this->getDateDifference(date('Y-m-d'), $minimumDate);
                    $days = $this->getDaysForLHA($days, $diff);
                }
            } else {
                H::ExcelLog('LHA visit cycle  No add Service', $this->logFile);
                echo "\n LHA visit cycle  No add Service \n";
            }
        } elseif (
            in_array($discipline, ['CNA', 'AIDE'])
            && ((in_array($discipline, ['CNA']) && !in_array($state, ['NC']))
                || (in_array($discipline, ['AIDE']) && !in_array($state, ['TX'])))
        ) {
            // check first SV in schedule master
            echo "\n --- Discipline CNA,AIDE-- \n";
            H::ExcelLog('Discipline CNA,AIDE', $this->logFile);

            $isAdditionalService = $this->isAdditionalService();
            if ($isAdditionalService > 1) {
                $days = $this->getDaysForCNAAIDE();
                echo "\n ------Yes Additional Service------ \n";
                echo "\n return " . $days . " visit cycle due to additional service \n";
                H::ExcelLog('Yes Additional Service', $this->logFile);
                H::ExcelLog('return ' . $days . ' visit cycle due to additional service', $this->logFile);
            }
            if ($state == 'CO' && $discipline == 'CNA') {
                $tempDays = $this->graduateClinicianProcess();
                if ($tempDays) {
                    $days = $tempDays;
                }
            }
        } else {
            echo "\n return default value from chart \n";
            H::ExcelLog('return default value from chart', $this->logFile);
        }
        return $days;
    }

    public function graduateClinicianProcess()
    {
        $graduateCNA = trim(strtolower($this->provider->CF_GraduateCNA));
        $providerId = $this->provider->ProviderID;
        $scheduleDate = DB::table('mssql_schedulemaster')->select(DB::raw('min(ScheduleDate) as baseDate'))
            ->whereIn('ScheduleStatusID', $this->allowedScheduleStatus)
            ->where('ProviderID', $providerId)->where('mssql_schedulemaster.IsBillable', 1)->first();

        $baseDate = $scheduleDate->baseDate;

        $defaultDays = 0;
        if ($graduateCNA == 'yes') {
            if ($baseDate) {
                echo "\n Yes Graduate CNA : " . htmlspecialchars($baseDate, ENT_QUOTES, 'UTF-8');
                $dayDiff = $this->getDateDifference(date('Y-m-d'), $baseDate);
                var_dump($dayDiff);
                if ($dayDiff < 30) {
                    $defaultDays = 7;
                } else {
                    $defaultDays = 14;
                }
            } else {
                $defaultDays = 14;
            }
        }

        return $defaultDays;
    }

    public function isAdditionalService()
    {
        $patientId = $this->patient->PatientID;

        $additionalServices = DB::table('mssql_schedulemaster')
            ->distinct()
            ->select('service_code_crosswalk.service')
            ->leftJoin('service_codes_daas', 'mssql_schedulemaster.ServiceCodeID', '=', 'service_codes_daas.service_id')
            ->leftJoin('service_code_crosswalk', 'service_code_crosswalk.service_name', '=', 'service_codes_daas.name')
            ->where('mssql_schedulemaster.PatientID', $patientId)
            ->whereDate('ScheduleDate', '>=', date('Y-m-d'))
            ->where('ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value)
            ->where('mssql_schedulemaster.IsBillable', 1)
            ->get();

        echo '----Additional Service Check---' . $additionalServices->count() . "\n";
        H::ExcelLog('Additional Service Check---' . $additionalServices->count(), $this->logFile);

        if ($additionalServices->count() <= 1) {
            $rawSchedules = DB::table('mssql_schedulemaster')
                ->select(
                    'mssql_schedulemaster.id',
                    'mssql_schedulemaster.ScheduleDate',
                    'mssql_schedulemaster.ScheduleStatusID',
                    'mssql_schedulemaster.IsBillable',
                    'mssql_schedulemaster.ServiceCodeID',
                    'service_code_crosswalk.service as crosswalk_service'
                )
                ->leftJoin('service_codes_daas', 'mssql_schedulemaster.ServiceCodeID', '=', 'service_codes_daas.service_id')
                ->leftJoin('service_code_crosswalk', 'service_code_crosswalk.service_name', '=', 'service_codes_daas.name')
                ->where('mssql_schedulemaster.PatientID', $patientId)
                ->whereDate('mssql_schedulemaster.ScheduleDate', '>=', date('Y-m-d'))
                ->orderBy('mssql_schedulemaster.ScheduleDate')
                ->limit(20)
                ->get();

            H::ExcelLog('RAW SCHEDULES FOR DEBUG: ' . json_encode($rawSchedules), $this->logFile);
        }

        echo "\n ----Additional Service Check---" . $additionalServices->count() . "\n";
        H::ExcelLog('Additional Service Check---' . $additionalServices->count(), $this->logFile);
        return $additionalServices->count();
    }

    public function getDateDifference($date1, $date2)
    {
        $date1 = new \DateTime($date1);
        $date2 = new \DateTime($date2);
        $interval = $date1->diff($date2);
        return $interval->days;
    }

    public function getNextDueDate($days, $date = '')
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        return date('Y-m-d', strtotime($date . ' +' . $days . ' days'));
    }

    private function hasPlannedArizonaLhaScheduleForCurrentProvider(): bool
    {
        return DB::table('mssql_schedulemaster as sm')
            ->leftJoin('mssql_facilities as f', 'sm.FacilityID', '=', 'f.FacilityID')
            ->where('sm.PatientID', $this->patient->PatientID)
            ->where('sm.ProviderID', $this->provider->ProviderID)
            ->where('f.State', self::STATE_AZ)
            ->where('sm.IsBillable', 1)
            ->whereIn('sm.ScheduleStatusID', $this->allowedScheduleStatus)
            ->whereDate('sm.ScheduleDate', '>=', date('Y-m-d'))
            ->exists();
    }

    private function hasPlannedArizonaPdnSchedule(): bool
    {
        // LHA and PDN are separate charts (distinct mssql_patients.PatientID rows) for the
        // same client in Arizona. ADMIT_GROUP_NO does not link them (it's either null or
        // equal to the chart's own PatientID); PatientAccount does, sharing everything
        // before the last "-" (e.g. "AMA-AM00086-LHA" / "AMA-AM00086-PDN").
        $accountPrefix = $this->getClientAccountPrefix($this->patient->PatientID);

        if ($accountPrefix === null) {
            return false;
        }

        return DB::table('mssql_schedulemaster as sm')
            ->join('mssql_providers as p', 'p.ProviderID', '=', 'sm.ProviderID')
            ->leftJoin('mssql_facilities as f', 'sm.FacilityID', '=', 'f.FacilityID')
            ->join('mssql_patients as mp', 'mp.PatientID', '=', 'sm.PatientID')
            ->where('mp.IsLatestAdmit', 1)
            ->whereRaw(
                "SUBSTRING(mp.PatientAccount, 1, LENGTH(mp.PatientAccount) - LENGTH(SUBSTRING_INDEX(mp.PatientAccount, '-', -1)) - 1) = ?",
                [$accountPrefix]
            )
            ->where('f.State', self::STATE_AZ)
            ->whereIn('p.DisciplineName', self::PDN_DISCIPLINES)
            ->where('sm.IsBillable', 1)
            ->whereIn('sm.ScheduleStatusID', $this->allowedScheduleStatus)
            ->whereDate('sm.ScheduleDate', '>=', date('Y-m-d'))
            ->exists();
    }

    private function getClientAccountPrefix(string $patientId): ?string
    {
        $patientAccount = trim((string) DB::table('mssql_patients')
            ->where('PatientID', $patientId)
            ->value('PatientAccount'));

        if ($patientAccount === '' || !str_contains($patientAccount, '-')) {
            return null;
        }

        return substr($patientAccount, 0, strrpos($patientAccount, '-'));
    }

    private function getArizonaLhaVisitCycle(int $defaultDays): int|string
    {
        if (!$this->hasPlannedArizonaLhaScheduleForCurrentProvider()) {
            H::ExcelLog('AZ LHA: active schedule for current LHA provider not found.', $this->logFile);

            return 'N/A';
        }

        if (!$this->hasPlannedArizonaPdnSchedule()) {
            H::ExcelLog('AZ LHA only: using default 60-day cadence.', $this->logFile);

            return $defaultDays; // AZ LHA = 60 from supervisory_visits_matrix
        }

        $minimumDate = $this->getBaseDate(0, 0);

        if (empty($minimumDate)) {
            return 'N/A';
        }

        $diff = $this->getDateDifference(date('Y-m-d'), $minimumDate);

        if ($diff < 7) {
            H::ExcelLog('AZ LHA + PDN: returning 7-day cadence.', $this->logFile);

            return 7;
        }

        H::ExcelLog('AZ LHA + PDN: returning 14-day cadence.', $this->logFile);

        return 14;
    }

    private function removeArizonaPdnFutureVisits(): void
    {
        $ids = DB::table('supervisory_visits as sv')
            ->join('mssql_patients as pt', 'pt.id', '=', 'sv.aoc_patient_id')
            ->join('mssql_providers as pr', 'pr.id', '=', 'sv.aoc_provider_id')
            ->join('mssql_schedulemaster as sm', function ($join) {
                $join->on('sm.PatientID', '=', 'pt.PatientID')
                    ->on('sm.ProviderID', '=', 'pr.ProviderID');
            })
            ->join('mssql_facilities as f', 'f.FacilityID', '=', 'sm.FacilityID')
            ->where('f.State', self::STATE_AZ)
            ->whereIn('pr.DisciplineName', self::PDN_DISCIPLINES)
            ->whereNull('sv.completed_by')
            ->whereDate('sv.visit_date_due', '>=', $this->currentDate)
            ->active('sv')
            ->pluck('sv.id')
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table('supervisory_visits')
                ->whereIn('id', $chunk)
                ->active()
                ->softDelete();
        }

        H::ExcelLog('Removed AZ PDN future visits: ' . implode(',', $ids), $this->logFile);
    }

    private function removeOutdatedArizonaLhaFutureVisits(): void
    {
        if (
            $this->patient->State !== self::STATE_AZ
            || $this->provider->DisciplineName !== self::DISCIPLINE_LHA
        ) {
            return;
        }

        $patient = $this->getClient();

        if (!$patient) {
            return;
        }

        $futureVisits = DB::table('supervisory_visits')
            ->select('id', 'visit_date_due', 'last_visit', 'cycle')
            ->where('aoc_patient_id', $patient->id)
            ->where('aoc_provider_id', $this->provider->id)
            ->whereNull('completed_by')
            ->whereDate('visit_date_due', '>=', $this->currentDate)
            ->active()
            ->orderBy('visit_date_due')
            ->orderBy('id')
            ->get();

        if ($futureVisits->isEmpty()) {
            return;
        }

        $expectedVisit = $this->getExpectedArizonaLhaFutureVisitPayload();

        if (!$expectedVisit) {
            $idsToDelete = $futureVisits->pluck('id')->all();

            $this->deleteSupervisoryVisitsByIds(
                $idsToDelete,
                'Removed outdated AZ LHA future visits because expected visit could not be resolved'
            );

            return;
        }

        $idsToDelete = [];
        $expectedVisitAlreadyKept = false;

        foreach ($futureVisits as $futureVisit) {
            $matchesExpectedVisit =
                $this->normalizeDate($futureVisit->visit_date_due) === $expectedVisit['visit_date_due']
                && $this->normalizeDate($futureVisit->last_visit) === $expectedVisit['last_visit']
                && (int) $futureVisit->cycle === $expectedVisit['cycle'];

            if ($matchesExpectedVisit && !$expectedVisitAlreadyKept) {
                $expectedVisitAlreadyKept = true;
                continue;
            }

            $idsToDelete[] = $futureVisit->id;
        }

        if (empty($idsToDelete)) {
            return;
        }

        $this->deleteSupervisoryVisitsByIds(
            $idsToDelete,
            'Removed outdated AZ LHA future visits'
        );
    }

    private function getExpectedArizonaLhaFutureVisitPayload(): ?array
    {
        if (!$this->hasPlannedArizonaLhaScheduleForCurrentProvider()) {
            return null;
        }

        $visit = DB::table('supervisory_visits_matrix')
            ->select('visit_cycle')
            ->where('state', self::STATE_AZ)
            ->where('discipline', self::DISCIPLINE_LHA)
            ->first();

        if (!$visit) {
            return null;
        }

        $cycle = $this->getArizonaLhaVisitCycle((int) $visit->visit_cycle);

        if ($cycle === 'N/A') {
            return null;
        }

        $baseDate = $this->getBaseDate();
        $nextDueDate = $this->getNextDueDate($cycle, $baseDate);

        return [
            'visit_date_due' => $this->normalizeDate($nextDueDate),
            'last_visit' => $this->normalizeDate($baseDate),
            'cycle' => (int) $cycle,
        ];
    }

    private function deleteSupervisoryVisitsByIds(array $ids, string $reason): void
    {
        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table('supervisory_visits')
                ->whereIn('id', $chunk)
                ->active()
                ->softDelete();
        }

        H::ExcelLog($reason . ': ' . implode(',', $ids), $this->logFile);
    }

    private function normalizeDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return date('Y-m-d', strtotime($date));
    }

}
