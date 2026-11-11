<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\FieldEvaluation;
use App\MssqlProviders;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class GenerateFieldEvaluationRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fieldevaluation:generate {type} {provider_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'default : Generate field evaluation records\n
                             colorado : Generate field evaluation records for colorado\n
                               manual : Manually create field evaluation by passing provider id';

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
                MssqlProviders::whereIn('EmploymentStatusID', [1, 24, 25, 6, 21, 22, 2, 23])
                    ->whereNotIn('DisciplineName', ['PT', 'OT', 'ST', 'PTA', 'OTA', 'STA'])
                    ->where('State', '<>', 'CO')
                    ->where('IsOfficeStaff', 0)
                    ->where('IsClinician', 1)
                    ->where('is_deleted', 0)
                    ->whereRaw('
                            DATE(
                                IF(
                                    (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                    HiredDate,
                                    coalesce(RehireDate, HiredDate)
                                )
                            ) < \'' . date('Y') . '-09-01\'')
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($providers) {
                        foreach ($providers as $provider) {
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
                                H::ExcelLog(print_r($logData, true), 'FieldEvaluation.log');

                                $insertData = [];
                                $insertData['aoc_provider_id'] = $provider->id;
                                $insertData['manager'] = $provider->ccm_id;
								$scheduleStartDate = date("Y-09-01",strtotime("-1 Year"));
								$scheduleEndDate = date("Y-08-31");

                                $insertData['item1_score'] = $this->getSuperVisoryVisitAvgForOtherState(
																$provider->id,
																'item1_score',
																$scheduleStartDate,
																$scheduleEndDate
															);
								$insertData['item2_score'] = $this->getSuperVisoryVisitAvgForOtherState(
																$provider->id,
																'item2_score',
																$scheduleStartDate,
																$scheduleEndDate
															);
								$insertData['item3_score'] = $this->getSuperVisoryVisitAvgForOtherState(
																$provider->id,
																'item3_score',
																$scheduleStartDate,
																$scheduleEndDate
															);
								$insertData['item4_score'] = $this->getSuperVisoryVisitAvgForOtherState(
																$provider->id,
																'item4_score',
																$scheduleStartDate,
																$scheduleEndDate
															);
                                $insertData['status'] = 'Generated';
                                $insertData['is_deleted'] = 0;
                                FieldEvaluation::insert($insertData);
                            }
                        }
                    });
                break;
            case 'colorado':
                $date = new \Carbon\Carbon();
                $quarterStartDate = $date->firstOfQuarter()->format('Y-m-d');
                $quarterEndDate = $date->endOfQuarter()->format('Y-m-d');
                $chunkSize = 100;
                MssqlProviders::whereIn('EmploymentStatusID', [1, 2, 6, 21, 22, 23, 24, 25])
                    ->whereNotIn('DisciplineName', ['PT', 'OT', 'ST', 'PTA', 'OTA', 'STA'])
                    ->where('State', 'CO')
                    ->where('IsOfficeStaff', 0)
                    ->where('IsClinician', 1)
                    ->where('is_deleted', 0)
                    ->whereRaw(
                        'MONTH(
                            DATE(
                                IF(
                                    (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                    HiredDate,
                                    coalesce(RehireDate, HiredDate)
                                )
                            )
                        ) >= MONTH(\'' . $quarterStartDate . '\')'
                    )
                    ->whereRaw(
                        'MONTH(
                            DATE(
                                IF(
                                    (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                    HiredDate,
                                    coalesce(RehireDate, HiredDate)
                                )
                            )
                        ) <= MONTH(\'' . $quarterEndDate . '\')'
                    )
                    ->whereRaw('
                        YEAR(
                            DATE(
                                IF(
                                    (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                    HiredDate,
                                    coalesce(RehireDate, HiredDate)
                                )
                            )
                        ) <> YEAR(now())')
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($providers) {

                        foreach ($providers as $provider) {
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
                                H::ExcelLog(print_r($logData, true), 'FieldEvaluation.log');

                                $insertData = [];
                                $insertData['aoc_provider_id'] = $provider->id;
                                $insertData['manager'] = $provider->ccm_id;

                                $insertData['item1_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item1_score', $logData['hireddate']);
                                $insertData['item2_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item2_score', $logData['hireddate']);
                                $insertData['item3_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item3_score', $logData['hireddate']);
                                $insertData['item4_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item4_score', $logData['hireddate']);

                                $insertData['status'] = 'Generated';
                                $insertData['is_deleted'] = 0;
                                FieldEvaluation::insert($insertData);
                            }
                        }
                    });
                break;
            case 'manual':
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
                        H::ExcelLog("Manually created : " . print_r($logData, true), 'FieldEvaluation.log');

                        $insertData = [];
                        $insertData['aoc_provider_id'] = $provider->id;
                        $insertData['manager'] = $provider->ccm_id;

						if ($provider->State == 'CO') {
							$insertData['item1_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item1_score', $logData['hireddate']);
							$insertData['item2_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item2_score', $logData['hireddate']);
							$insertData['item3_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item3_score', $logData['hireddate']);
							$insertData['item4_score'] = $this->getSuperVisoryVisitAvg($provider->id, 'item4_score', $logData['hireddate']);
						} else {
							$scheduleStartDate = date("Y-09-01",strtotime("-1 Year"));
							$scheduleEndDate = date("Y-08-31");

							$insertData['item1_score'] = $this->getSuperVisoryVisitAvgForOtherState(
															$provider->id,
															'item1_score',
															$scheduleStartDate,
															$scheduleEndDate
														);
							$insertData['item2_score'] = $this->getSuperVisoryVisitAvgForOtherState(
															$provider->id,
															'item2_score',
															$scheduleStartDate,
															$scheduleEndDate
														);
							$insertData['item3_score'] = $this->getSuperVisoryVisitAvgForOtherState(
															$provider->id,
															'item3_score',
															$scheduleStartDate,
															$scheduleEndDate
														);
							$insertData['item4_score'] = $this->getSuperVisoryVisitAvgForOtherState(
															$provider->id,
															'item4_score',
															$scheduleStartDate,
															$scheduleEndDate
														);
						}

                        $insertData['status'] = 'Generated';
                        $insertData['is_deleted'] = 0;
                        FieldEvaluation::insert($insertData);
                    }
                }
                break;
        }
        return 0;
    }

    private function getSuperVisoryVisitAvg($aoc_provider_id , $field, $hireddate)
    {
        $date = \Carbon\Carbon::parse($hireddate);
        $date->setYear(now()->year);
        $date->subYear();
        $startDate = $date->format('Y-m-d');
        $date->addYear()->subDays(1);
        $endDate = $date->format('Y-m-d');
        $avg = DB::table('supervisory_visits')
            ->where('aoc_provider_id', $aoc_provider_id)
            ->whereBetween('visit_date_due', [$startDate, $endDate])
            ->whereNotNull('completed_by')
            ->active()
            ->avg($field);

		$avgMapping = [
			1 => 4,
			2 => 3,
			3 => 2,
			4 => 1
		];

		return empty($avg) ? 2 : $avgMapping[(int) $avg];
    }

	private function getSuperVisoryVisitAvgForOtherState($aoc_provider_id , $field, $scheduleStartDate, $scheduleEndDate)
    {
        $avg = DB::table('supervisory_visits')
            ->where('aoc_provider_id', $aoc_provider_id)
            ->whereBetween('visit_date_due', [$scheduleStartDate, $scheduleEndDate])
            ->whereNotNull('completed_by')
            ->active()
            ->avg($field);

		$avgMapping = [
			1 => 4,
			2 => 3,
			3 => 2,
			4 => 1
		];

		return empty($avg) ? 2 : $avgMapping[(int) $avg];
    }
}
