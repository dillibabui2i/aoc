<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MssqlPatients;
use App\Helpers\H;

class UpdateIsPrnInPatientTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:prnInPatientTable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the isPRN field in the patients table where the last name is PRN';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $patientData = MssqlPatients::where('Patient_LastName', 'PRN')->get();
        foreach($patientData as $patient){
            if($patient->isPRN != 1){
                $logData = [
                    "ClientId" => $patient->id,
                    "ClientName" => $patient->Patient_FirstName,
                    "ClientLastName" => $patient->Patient_LastName,
                ];
                H::ExcelLog(print_r($logData, true),'PatientUpdatePrn.log');
                // Update the isPRN field
                MssqlPatients::where('id', $patient->id)->update(['isPRN' => 1]);
            }
        }
        return 0;
    }
}
