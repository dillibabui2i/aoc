<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\H;

class SetIntakeShiftInfoDeprecated extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intake:setshifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set admission and first billable shift data for intake clients';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        try{
            $result = DB::statement("update `mssql_patients`
                                set intake_admission_visit = NULL;");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }

        try{
            $result = DB::statement("update `mssql_patients`
                                set intake_first_billable_visit = NULL;");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }

        try{
            $result = DB::statement("update `service_codes_daas`
                                set is_admissions = 0;");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }

        try{
            $result = DB::statement("update `service_codes_daas`
                                set is_admissions = 1
                                where name IN (
                                    'CNA Admission (Add LOB)', 'CNA Admission Field (Add LOB)', 'IHSS Admission',
                                    'PDN Admission (Add LOB)', 'PDN Admission (Billable)', 'PDN Admission Field (Add LOB)',
                                    'RN ADMISSION', 'RN Admission Field', 'RN Admission OASIS',
                                    'RN Admission OASIS Field', 'SV Admission', 'SV Admission (Add LOB)',
                                    'SV Admission (NONBILL)', 'SV Admission (OFFICE NONBILL)', 'SV Admission (OFFICE)');");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }

        try{
            $result = DB::statement("update `service_codes_daas`
                                set is_admissions = 1
                                where name IN (
                                    'Physical Therapy Initial Eval - High Complexity',
                                    'Physical Therapy Initial Eval - Low Complexity',
                                    'Physical Therapy Initial Eval - Mod Complexity',
                                    'Physical Therapy Initial Evaluation',
                                    'Tele-Physical Therapy Initial Evaluation',
                                    'PT Initial Evaluation - Acute',
                                    'PT Initial Evaluation - Acute EI',
                                    'PT Initial Evaluation - Long Term',
                                    'PT Initial Evaluation - Long Term EI',
                                    'PT SOC Initial Evaluation - Acute',
                                    'PT SOC Initial Evaluation - Acute - OASIS',
                                    'PT SOC Initial Evaluation - Acute EI',
                                    'PT SOC Initial Evaluation - Long Term',
                                    'PT SOC Initial Evaluation - Long Term - OASIS',
                                    'PT SOC Initial Evaluation - Long Term EI',
                                    'Occupational Therapy Initial Eval - Low Complexity',
                                    'Occupational Therapy Initial Eval - Mod Complexity',
                                    'Occupational Therapy Initial Eval High Complexity',
                                    'Occupational Therapy Initial Evaluation',
                                    'Tele-Occupational Therapy Initial Evaluation',
                                    'OT Initial Evaluation - Acute',
                                    'OT Initial Evaluation - Acute EI',
                                    'OT Initial Evaluation - Long Term',
                                    'OT Initial Evaluation - Long Term EI',
                                    'OT SOC Initial Evaluation - Acute',
                                    'OT SOC Initial Evaluation - Acute - OASIS',
                                    'OT SOC Initial Evaluation - Acute EI',
                                    'OT SOC Initial Evaluation - Long Term',
                                    'OT SOC Initial Evaluation - Long Term - OASIS',
                                    'OT SOC Initial Evaluation - Long Term EI',
                                    'Behavioral & Qual Analys of Voice & Resonance',
                                    'Eval of Oral & Pharyngeal Swallow Function',
                                    'Eval of Speech Sound Production w/Language Comp & Expr',
                                    'Evaluation of Speech Fluency',
                                    'Evaluation of Speech Sound Production',
                                    'Tele-Behavioral & Qual Analys of Voice & Resonance',
                                    'Tele-Eval of Oral & Pharyngeal Swallow Function',
                                    'Tele-Eval of Speech Sound Production w/Language Comp & Expr',
                                    'Tele-Evaluation of Speech Fluency',
                                    'Tele-Evaluation of Speech Sound Production',
                                    'ST Initial Evaluation - Acute',
                                    'ST Initial Evaluation - Acute EI',
                                    'ST Initial Evaluation - Long Term',
                                    'ST Initial Evaluation - Long Term EI',
                                    'ST SOC Initial Evaluation - Acute',
                                    'ST SOC Initial Evaluation - Acute - OASIS',
                                    'ST SOC Initial Evaluation - Acute EI',
                                    'ST SOC Initial Evaluation - Long Term',
                                    'ST SOC Initial Evaluation - Long Term - OASIS',
                                    'ST SOC Initial Evaluation - Long Term EI',
                                    'Eval of Speech Sound Prod w/Language Comp & Expr',
                                    'Behavioral & Qual Anlys of Voice & Resonance',
                                    'Tele-Behavioral & Qual Anlys of Voice & Resonance',
                                    'Tele-Eval of Speech Sound Prod w/Language Comp &');");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }

        try {
            // Query 1 to set admission visit
            DB::unprepared("CREATE TEMPORARY TABLE latest_admission_visit AS
                        SELECT sm2.PatientID, sm2.CGTaskID, sm2.ScheduleDate
                        FROM mssql_schedulemaster sm2
                        left join service_codes_daas sc on sm2.ServiceCodeID = sc.service_id
                        WHERE sm2.ScheduleDate > '2024-01-01'
                            AND sc.is_admissions = 1
                        ORDER BY sm2.ScheduleDate DESC;
                    CREATE INDEX idx_latest_admission ON latest_admission_visit (PatientID, CGTaskID, ScheduleDate);
                    UPDATE mssql_patients p
                    JOIN latest_admission_visit lav ON p.PatientID = lav.PatientID
                    SET p.intake_admission_visit =  (select CGTaskID from latest_admission_visit lav where lav.PatientID = p.PatientID
                                                        order by lav.ScheduleDate desc limit 1)
                    WHERE p.IsLatestAdmit = 1;
                    DROP TEMPORARY TABLE latest_admission_visit;");

            //DB::statement('update intake left join mssql_patients on mssql_patients.id = intake.aoc_patient_id
            //set intake.intake_completed = 1 where mssql_patients.intake_first_billable_visit is not null;');
        } catch (\Exception $e){
            echo 'error' . "\n";
            var_dump($e->getMessage());
            echo "\n";
            //do something with $e->getMessage();
        }
        try {
            //Query 2 to set first billable visit
            DB::unprepared("CREATE TEMPORARY TABLE latest_admission_visit AS
                    SELECT sm2.PatientID, sm2.CGTaskID, sm2.ScheduleDate
                    FROM mssql_schedulemaster sm2
                    left join mssql_patients p on sm2.PatientID = p.PatientID
                    left join mssql_schedulemaster sm3 on p.intake_admission_visit = sm3.CGTaskID
                    WHERE sm2.ScheduleStatusID in (1,2,3,5)
                        and sm2.IsBillable = 1
                        and sm2.ScheduleDate > '2024-01-01'
                        and sm2.ScheduleDate > ifnull(sm3.ScheduleDate, '2001-01-01')
                    ORDER BY sm2.ScheduleDate ASC;
                CREATE INDEX idx_latest_admission ON latest_admission_visit (PatientID, CGTaskID, ScheduleDate);
                UPDATE mssql_patients p
                SET p.intake_first_billable_visit = (select CGTaskID from latest_admission_visit lav where lav.PatientID = p.PatientID
                                                    order by lav.ScheduleDate asc limit 1)
                WHERE p.IsLatestAdmit = 1;
                DROP TEMPORARY TABLE latest_admission_visit;");
        } catch (\Exception $e){
            echo 'error' . "\n";
            var_dump($e->getMessage());
            echo "\n";
            //do something with $e->getMessage();
        }

		/*try{
            $result = DB::statement("update intake left join mssql_patients on mssql_patients.id = intake.aoc_patient_id
            set intake.intake_completed = 1 where mssql_patients.intake_first_billable_visit is not null;");
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'SetIntakeShiftInfo.log');
        }*/


        return Command::SUCCESS;
    }
}
