<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\FieldEvaluation;
use App\MssqlProviders;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class GenerateTherapyEvaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'therapyevaluation:generate {type} {provider_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
    default : Monthly auto process to created evaluations WHERE State  IN (\'CO\',\'TX\') AND RehireDate/HiredDate is during the NEXT months days for all years\n
    manual : Manually create therapy evaluation by passing provider id\n';
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
        $type = $this->argument('type');
        switch ($type) {
                case 'default':
                    $chunkSize = 100;
                    MssqlProviders::select('mssql_providers.id', 'mssql_providers.ccm_id','mssql_providers.fullName',
                    'mssql_providers.ClinicianID',
                    DB::raw("case when
                        (RehireDate = '0000-00-00' || RehireDate = '1900-01-01' || RehireDate = '' || RehireDate is null)
                        then HiredDate else RehireDate end as HiredDate"
                    ),
                    'mssql_providers.DisciplineName')
                    ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
                    ->whereIn('mssql_facilities.State', ['CO', 'TX'])
                    ->where(function ($query) {
                        $query->where('EmploymentStatusID', 1)
                              ->orWhere('EmploymentStatusID',21);
                    })
                    ->whereRaw("month(case when
                        (RehireDate = '0000-00-00' || RehireDate = '1900-01-01' || RehireDate = '' || RehireDate is null)
                        then HiredDate else RehireDate end) = " . date('m',strtotime('first day of +1 month')))
                    ->whereIn('DisciplineName', ['PT', 'OT', 'ST', 'PTA', 'OTA', 'STA'])
                    ->where('mssql_providers.is_deleted',0)
                    ->where(function ($query) {
                        $query->where('IsClinician', 1)
                            ->orWhere(function ($subquery) {
                                $subquery->where('IsOfficeStaff', 1)
                                    ->where('IsClinician', 1);
                            });
                    })
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($providers) {
                        foreach($providers as $provider) {
                            $findExistingRecords = FieldEvaluation::where('aoc_provider_id', $provider->id)
                                                    ->whereRaw('YEAR(created_at) = YEAR(now())')
                                                    ->whereNested(function ($q) {
                                                        $q->where('is_deleted', 0)
                                                        ->orWhereNull('is_deleted');
                                                    })
                                                    ->get();
                            if ($findExistingRecords->count() == 0) {
                                $logData = [];
                                $logData['id'] = $provider->id;
                                $logData['provider_name'] = $provider->fullName;
                                $logData['clinicianid'] = $provider->ClinicianID;
                                $logData['hireddate'] = $provider->HiredDate;
                                $logData['disciplinename'] = $provider->DisciplineName;
                                H::ExcelLog(print_r($logData, true),'TherapyEvaluation_3.log');

                                $insertData = [];
                                $insertData['aoc_provider_id'] = $provider->id;
                                $insertData['manager'] = $provider->ccm_id;
                                $insertData['status'] = 'Generated';
                                $insertData['is_deleted'] = 0;
                                FieldEvaluation::insert($insertData);
                            }
                        }
                    });
                    break;
                case '4':
                    $provider_id = $this->argument('provider_id');
                    if ($provider_id != "") {
                        $provider = MssqlProviders::find($provider_id);
                        $findExistingRecords = FieldEvaluation::where('aoc_provider_id', $provider->id)
                                                ->whereRaw('YEAR(created_at) = YEAR(now())')
                                                ->whereNested(function ($q) {
                                                    $q->where('is_deleted', 0)
                                                    ->orWhereNull('is_deleted');
                                                })
                                                ->get();
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
                            H::ExcelLog("Manually created : " . print_r($logData, true),'TherapyEvaluation_3.log');

                            $insertData = [];
                            $insertData['aoc_provider_id'] = $provider->id;
                            $insertData['manager'] = $provider->ccm_id;
                            $insertData['status'] = 'Generated';
                            $insertData['is_deleted'] = 0;
                            FieldEvaluation::insert($insertData);
                        }
                    }
                    break;
            default:
                echo 'no match found!';
        }
        return 0;
    }
}
