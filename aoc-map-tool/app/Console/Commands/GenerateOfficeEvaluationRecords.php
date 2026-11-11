<?php

namespace App\Console\Commands;
use App\OfficeEvaluation;
use App\MssqlProviders;
use App\Helpers\H;

use Illuminate\Console\Command;

class GenerateOfficeEvaluationRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'officeevaluation:generate {provider_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate office evaluation records';

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
        $provider_id = $this->argument('provider_id');

        if ($provider_id != "") {
            $provider = MssqlProviders::find($provider_id);
            $findExistingRecords = OfficeEvaluation::where('aoc_provider_id', $provider->id)->whereRaw('YEAR(created_at) = YEAR(now())')->get();
            if ($findExistingRecords->count() == 0) {
                $logData = [];
                $logData['id'] = $provider->id;
                $logData['provider_name'] = $provider->fullName;
                $logData['clinicianid'] = $provider->ClinicianID;
                $logData['hireddate'] =  (
                    !empty($provider->RehireDate) &&
                    $provider->RehireDate != '0000-00-00' &&
                    $provider->RehireDate != '1900-01-01'
                ) ? $provider->RehireDate : $provider->HiredDate;
                $logData['disciplinename'] = $provider->DisciplineName;
                H::ExcelLog("Manually created : " . print_r($logData, true),'OfficeEvaluation.log');

                $insertData = [];
                $insertData['aoc_provider_id'] = $provider->id;
                $insertData['manager'] = $provider->ccm_id;
                $insertData['status'] = 'Not Started';
                OfficeEvaluation::insert($insertData);
            }
        } else {

            $chunkSize = 100;
            MssqlProviders::where('IsOfficeStaff', 1)
                        ->where('EmploymentStatusID', 1)
                        ->where('is_deleted',0)
                        ->whereRaw('DATE(
                            IF(
                                (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                HiredDate,
                                coalesce(RehireDate, HiredDate)
                            )
                        ) < \'' . date('Y') . '-02-28\'')
                        ->orderBy('id')
                        ->chunk($chunkSize, function ($providers) {
                /*foreach($providers as $provider) {
                    $insertData = [];
                    $insertData['aoc_provider_id'] = $provider->id;
                    $insertData['manager'] = $provider->ccm_id;
                    $insertData['status'] = 'Draft';
                    OfficeEvaluation::insert($insertData);
                }*/
                foreach($providers as $provider) {
                    $findExistingRecords = OfficeEvaluation::where('aoc_provider_id', $provider->id)->whereRaw('YEAR(created_at) = YEAR(now())')->get();
                    if ($findExistingRecords->count() == 0) {
                        $logData = [];
                        $logData['id'] = $provider->id;
                        $logData['provider_name'] = $provider->fullName;
                        $logData['clinicianid'] = $provider->ClinicianID;
                        $logData['hireddate'] = (
                            !empty($provider->RehireDate) &&
                            $provider->RehireDate != '0000-00-00' &&
                            $provider->RehireDate != '1900-01-01'
                        ) ? $provider->RehireDate : $provider->HiredDate;
                        $logData['disciplinename'] = $provider->DisciplineName;
                        H::ExcelLog(print_r($logData, true),'OfficeEvaluation.log');

                        $insertData = [];
                        $insertData['aoc_provider_id'] = $provider->id;
                        $insertData['manager'] = $provider->ccm_id;
                        $insertData['status'] = 'Not Started';
                        OfficeEvaluation::insert($insertData);
                    }
                }
            });
        }
        return 0;
    }
}
