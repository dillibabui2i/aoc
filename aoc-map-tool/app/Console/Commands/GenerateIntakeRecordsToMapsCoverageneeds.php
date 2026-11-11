<?php

namespace App\Console\Commands;

use App\Events\Map\MapUpdatedEvent;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class GenerateIntakeRecordsToMapsCoverageneeds extends Command
{
    private bool $mapTouched = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:intake_map_converageneeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Intake records into maps_coverageneeds table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->insertIntakeRecords();
        return 0;
    }

    private function weekDaysBetween($requiredDays, $start, $end){
        $startTime = Carbon::createFromFormat('Y-m-d', $start);
        $endTime = Carbon::createFromFormat('Y-m-d', $end);
    
        $result = [];
    
        while ($startTime->lt($endTime)) {
            
            if(in_array($startTime->dayOfWeek, $requiredDays)){
                array_push($result, $startTime->copy());
            }
            
            $startTime->addDay();
        }
    
        return $result;
    }

    private function insertIntakeRecords()
    {
        $days = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        $rawData = DB::table('NeedToHire_Schedules')
                    ->select(
                        'mssql_patients.Patient_FirstName',
                        'mssql_patients.Patient_LastName',
                        'mssql_patients.Payer',
                        DB::raw('IF((NeedToHire_Accounts.ServicesNeeded = "CNA" OR NeedToHire_Accounts.ServicesNeeded = "IHSS" OR NeedToHire_Accounts.ServicesNeeded = "Skilled Nursing" OR NeedToHire_Accounts.ServicesNeeded = "PNA"), NeedToHire_Accounts.ServicesNeeded, "SN") as discipline'),
                        'NeedToHire_Schedules.StartHour',
                        'NeedToHire_Schedules.StartMinute',
                        'NeedToHire_Schedules.EndHour',
                        'NeedToHire_Schedules.EndMinute',
                        'NeedToHire_Schedules.ShiftHours',
                        'mssql_patients.Patient_Address1',
                        'mssql_patients.Patient_State',
                        'mssql_patients.Patient_City',
                        'mssql_patients.Patient_Zip',
                        'mssql_facilities.FacilityName',
                        'NeedToHire_Schedules.StartDayOfWeek',
                        'mssql_patients.CF_GoalSOCdate',
                        'mssql_patients.CF_Skills',
                        'NeedToHire_Accounts.aoc_patient_id'
                    )
                    ->leftJoin('NeedToHire_Accounts', 'NeedToHire_Schedules.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
                    ->leftJoin('mssql_patients', 'NeedToHire_Accounts.aoc_patient_id', '=', 'mssql_patients.id')
                    ->leftJoin('mssql_facilities', 'mssql_patients.aoc_facility_id', '=', 'mssql_facilities.FacilityID')
                    ->where('NeedToHire_Accounts.IsActive', 1)
                    ->where('NeedToHire_Schedules.IsActive', 1)
                    ->where('NeedToHire_Schedules.AcceptsCoverage', 1)
                    ->where('mssql_patients.Patient_CurrentStatusID', 4)
                    ->whereDate('mssql_patients.CF_GoalSOCdate', '>=', Carbon::now()->format('Y-m-d'))
                    ->whereDate('mssql_patients.CF_GoalSOCdate', '<=', Carbon::now()->addDays(90)->format('Y-m-d'))
                    ->get();

        if ($rawData->count() > 0) {
            foreach($rawData as $row) {
                $dates = $this->weekDaysBetween([$days[$row->StartDayOfWeek]],Carbon::parse($row->CF_GoalSOCdate)->addDays(1)->format('Y-m-d'), Carbon::now()->addDays(90)->format('Y-m-d'));
                foreach ($dates as $date) {
                    $schedule_time = H::getHelper('ConstantHelper')->getSmallTimeFormat($row->StartHour.':'.$row->StartMinute) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($row->EndHour.':'.$row->EndMinute);
                    $data = [];
                    $data['Date'] = $date->format('m/d/Y');
                    $data['filterDate'] = $date->format('m/d/Y');
                    $data['Client_First_Name'] = $row->Patient_FirstName;
                    $data['Client_Last_Name'] = $row->Patient_LastName;
                    $data['Name'] =  $row->Patient_FirstName . ' ' .  $row->Patient_LastName;
                    $data['Payer'] = $row->Payer;
                    $data['Discipline'] = $row->discipline;
                    $data['Service'] = 'Coverage' . ' ' . $row->discipline . ((H::getHelper('NTHChecklist')->isSpecialized($row->CF_Skills) == true) ? " Yes" : "");
                    $data['Schedule_Time'] = $schedule_time;
                    $data['Hours'] = $row->ShiftHours;
                    $data['Client_Address'] = $row->Patient_Address1;
                    $data['Client_City'] = $row->Patient_City;
                    $data['Client_ZipCode'] = $row->Patient_Zip;
                    $data['Locations'] = $row->FacilityName;
                    $data['State'] = $row->Patient_State;
                    $data['intake'] = 'Yes';
                    $data['shift_date'] = $date->format('Y-m-d');
                    $data['aoc_patient_id'] = $row->aoc_patient_id;
                    $data['is_updated'] = 1;
                    $data['updated_at'] = Carbon::now();

                    DB::table('maps_coverageneeds')->updateOrInsert(
                        [
                            'shift_date' => $date->format('Y-m-d'),
                            'Client_First_Name' => $row->Patient_FirstName,
                            'Client_Last_Name' => $row->Patient_LastName,
                            'Schedule_Time' => $schedule_time,
                        ],
                        $data
                    );
                    $this->mapTouched = true;
                }
            }
        }

        if($this->mapTouched) {
            event(new MapUpdatedEvent('maps_coverageneeds'));
        }
    }
}
