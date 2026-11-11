<?php

namespace App\Http\Controllers;

use App\DTO\GpCalculator\RequestPayerRateDTO;
use App\Enums\MssqlScheduleMasterStatus;
use App\Enums\PayerRatesStatus;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlPatients;
use App\MssqlProviders;
use App\PayerRates;
use App\Repositories\GpCalculator\ProvidersRepository;
use App\ScheduleMaster;
use App\Services\GpCalculator\PayerRatesService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GPCalculatorController extends Controller
{
    public function __construct(private readonly PayerRatesService $payerRatesService)
    {
    }

    public function index(Request $request)
    {
        $id = $request->input('id');
        $aoc_patient_id = $request->input('aoc_patient_id');

        if (isset($id) && $id != '') {
            $gpData = DB::table('gp_calculator')->where('id', $id)->first();
            $gpAocPatientData = json_decode($gpData->aoc_patient_id, true);
            $gpAocPatientIds = array_keys($gpAocPatientData);
            $gpConcateData = array_values($gpAocPatientData);
            $gpAocPatientProviders = $specialized = $respite = $patientsPayer = $patientsRespitePayer = $pname = [];

            foreach ($gpConcateData as $key => $val) {
                $parts = explode('@', $val);
                $gpAocPatientProviders[$key] = $parts[0] ?? null;
                $specialized[$key] = $parts[1] ?? null;
                $respite[$key] = $parts[2] ?? null;
                $pname[$key] = $parts[3] ?? null;
            }

            $nurseData = json_decode($gpData->nurse_data, true);
            $nurseCount = (array_sum(array_map('count', $nurseData)) > 0) ?
            array_sum(array_map('count', $nurseData)) : 0;
            $patientsData = [];

            if (count($gpAocPatientIds) > 0) {
                foreach ($gpAocPatientIds as $key => $gpAocPatientId) {
                    if ($gpAocPatientId > 0) {
                        $pData = MssqlPatients::find($gpAocPatientId);
                        $patientsData[] = $pData;
                        $patientsPayer[] = $pData->Payer;
                        $patientsRespitePayer[] = $pData->RespitePayer;
                    } else {
                        $patient = new \stdClass();
                        $patient->id = $gpAocPatientId;
                        $patient->name = $pname[$key];
                        $patientsData[] = $patient;
                        $patientsPayer[] = $pname[$key];
                        $patientsRespitePayer[] = '';
                    }
                }
            }

            return view('gpCalculator.index', [
                'mode' => 'edit',
                'nursesCount' => $nurseCount,
                'gpData' => $gpData,
                'nurseData' => $nurseData,
                'patientsData' => $patientsData,
                'gpAocPatientData' => $gpAocPatientData,
                'gpAocPatientProviders' => $gpAocPatientProviders,
                'specialized' => $specialized,
                'respite' => $respite,
                'patientsPayer' => $patientsPayer,
                'patientsRespitePayer' => $patientsRespitePayer,
                'clientCount' => (count(array_keys($nurseData)) > 0) ?
                count(array_keys($nurseData)) - 1 : 0,
            ]);
        } elseif (isset($aoc_patient_id) && $aoc_patient_id != '') {
            $patientData = [];
            $patientData = MssqlPatients::find($aoc_patient_id);

            if (LD::checkFeatureFlag('gp-calculator-whole-home-calculation-change')) {
                $nurseData = DB::table('mssql_schedulemaster')
                ->select(
                    DB::raw('CONCAT(mssql_providers.FirstName," ",mssql_providers.LastName) as nurseName'),
                    DB::raw('mssql_providers.id as aoc_provider_id'),
                    DB::raw('0 as aoc_candidate_id'),
                    DB::raw('service_codes_daas.name as discipline'),
                    //DB::raw('avg(mssql_schedulemaster.PlannedHours) as preHours'),
                    //DB::raw('0 as hours'),
                    DB::raw('(SELECT (sum(msm.PlannedHours) / 4) FROM `mssql_schedulemaster` as msm left join service_codes_daas scd on scd.service_id = msm.ServiceCodeID where msm.ProviderID = mssql_schedulemaster.ProviderID and msm.ScheduleStatusID IN (5,3,2,1) and PatientID != "' . $patientData->PatientID . '" and msm.ScheduleDate > "' .
                    Carbon::now()->previous('Sunday')->format('Y-m-d') . '" and  msm.ScheduleDate < "' .
                    Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d') . '"  and scd.name not like "%coverage%") as preHours'),
                    DB::raw('(SELECT (sum(msm.PlannedHours) / 4) FROM `mssql_schedulemaster` as msm left join service_codes_daas scd on scd.service_id = msm.ServiceCodeID where msm.ProviderID = mssql_schedulemaster.ProviderID and msm.ScheduleStatusID IN (5,3,2,1) and PatientID = "' . $patientData->PatientID . '" and msm.ScheduleDate > "' .
                    Carbon::now()->previous('Sunday')->format('Y-m-d') . '" and  msm.ScheduleDate < "' .
                    Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d') . '" and scd.name not like "%coverage%") as hours'),
                    /*DB::raw('case when ifnull(mssql_schedulemaster.ActualHours,0) = 0 then 0
                    else round(ifnull(mssql_schedulemaster.Cost,0) / mssql_schedulemaster.ActualHours,2) end as desiredRate'),*/
                    DB::raw('(select case when ifnull(SUM(msm.PlannedHours),0) = 0 then 0
					else round(ifnull(SUM(msm.Cost),0) / SUM(msm.PlannedHours),2) end
                    from `mssql_schedulemaster` as msm left join service_codes_daas scd on scd.service_id = msm.ServiceCodeID where msm.ProviderID = mssql_schedulemaster.ProviderID and msm.ScheduleStatusID in (5,3,2,1)
					and msm.ScheduleDate > "' .
                    Carbon::now()->previous('Sunday')->format('Y-m-d') . '" and  msm.ScheduleDate < "' .
                    Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d') . '" and scd.name not like "%coverage%") as desiredRate'),
                    DB::raw('1 as isOldNurse'),
                    DB::raw('CASE
						WHEN service_codes_daas.name NOT LIKE "%RESPITE%" THEN 1
						WHEN service_codes_daas.name LIKE "%RESPITE%" THEN 3
					END as priority')
                )
                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
                ->leftJoin('service_codes_daas', 'service_codes_daas.service_id', '=', 'mssql_schedulemaster.ServiceCodeID')
                ->whereNot('mssql_providers.LastName', 'like', '\_U%')
                ->where('PatientID', $patientData->PatientID)
                ->where('ScheduleStatusID', 5)
                ->whereNot('service_codes_daas.name', 'like', '%coverage%')
                /*->whereBetween('ScheduleDate', [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->addWeeks(3)->endOfWeek()->format('Y-m-d')])*/
                ->where('ScheduleDate', '>', Carbon::now()->parse('this sunday')->format('Y-m-d'))
                ->where('ScheduleDate', '<', Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d'))
                ->whereNotNull('mssql_schedulemaster.ProviderID')
                ->orderBy('priority', 'asc')
                ->groupBy('mssql_schedulemaster.ProviderID')
                ->get()
                ->toArray();
            } else {
                $nurseData = DB::table('mssql_schedulemaster')
                ->select(
                    DB::raw('(select id from mssql_providers where
                                ProviderID = mssql_schedulemaster.ProviderID and mssql_providers.is_deleted = 0) as aoc_provider_id'),
                    DB::raw('0 as aoc_candidate_id'),
                    DB::raw('(select DisciplineName from mssql_providers where
                                ProviderID = mssql_schedulemaster.ProviderID and mssql_providers.is_deleted = 0) as discipline'),
                    DB::raw('(SELECT avg(msm.PlannedHours) FROM `mssql_schedulemaster` as msm where
                                msm.ProviderID = mssql_schedulemaster.ProviderID and msm.PatientID = mssql_schedulemaster.PatientID
                                and msm.ScheduleStatusID = 5 and msm.ScheduleDate > "' . Carbon::now()->parse('first Sunday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d') . '"
                                and  msm.ScheduleDate < "' . Carbon::now()->parse('last Monday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d') . '" limit 1) as hours'),
                    DB::raw('0 as preHours'),
                    DB::raw('(select case when ifnull(msm.ActualHours,0) = 0 then 0
                    else round(ifnull(msm.Cost,0) / msm.ActualHours,2) end
                    from `mssql_schedulemaster` as msm where msm.ProviderID = mssql_schedulemaster.ProviderID and
                    msm.PatientID = mssql_schedulemaster.PatientID and msm.ScheduleStatusID in (1,2,3)
                    and msm.ScheduleDate > "' .
                    Carbon::now()->previous('Sunday')->subWeeks(3)->format('Y-m-d') . '" and  msm.ScheduleDate < "' .
                    Carbon::now()->parse('-1 Monday')->format('Y-m-d') . '" limit 1) as desiredRate'),
                    DB::raw('1 as isOldNurse')
                )
                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
                ->whereNot('mssql_providers.LastName', 'like', '\_U%')
                ->where('PatientID', $patientData->PatientID)
                ->where('ScheduleStatusID', 5)
                ->where('ScheduleDate', '>', Carbon::now()->parse('this sunday')->format('Y-m-d'))
                ->where('ScheduleDate', '<', Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d'))
                ->whereNotNull('mssql_schedulemaster.ProviderID')
                ->groupBy('mssql_schedulemaster.ProviderID')
                ->get()
                ->toArray();
            }

            $nurseData = json_decode(json_encode($nurseData), true);
            $nurseCount = count($nurseData);

            return response()->json([
                'nurseHtml' => view('gpCalculator.nurseForm', [
                    'mode' => 'edit',
                    'nursesCount' => $nurseCount,
                    'nurseData' => $nurseData,
                    'isOldNurse' => 1,
                    'loadwholenurse' => 1,
                    'clientIndex' => 0,
                    'formRowCount' => $request->input('formRowCount'),
                ])->render(),
                'nurseCount' => $nurseCount,
            ]);
        } else {
            return view('gpCalculator.index');
        }
    }

    public function loadGPNurseForm(Request $request)
    {
        return view('gpCalculator.nurseForm', [
            'nursesCount' => $request->input('nursesCount'),
            'clientCount' => $request->input('clientCount'),
            'isOldNurse' => 0,
        ]);
    }

    public function loadScheduleHours(Request $request)
    {
        try {
            $providerId = $request->input('providerId');

            if (isset($providerId) && $providerId != '') {
                $providerData = MssqlProviders::select('ProviderID')->where('id', $providerId)->first();
                if (isset($providerData->ProviderID) && $providerData->ProviderID != 0) {
                    $currentDate = now();
                    $currentDateString = $currentDate->toDateString();

                    if (LD::checkFeatureFlag('update-hours-logic-for-new-week')) { // AOC-3675
                        $isTodaySaturday = $currentDate->isSaturday();
                        $currentPayrollStart = $isTodaySaturday
                            ? $currentDateString
                            : date('Y-m-d', strtotime('last Saturday', strtotime($currentDateString)));
                        $windowStart = date('Y-m-d', strtotime('-3 weeks ' . $currentPayrollStart));
                        $windowEnd = date('Y-m-d', strtotime('+1 day', strtotime($currentPayrollStart)));

                        // The overlap payroll week ends on the following Sunday.
                        // Cap the range at today so future rows are not included in the average.
                        if (strtotime($windowEnd) > strtotime($currentDateString)) {
                            $windowEnd = $currentDateString;
                        }
                    } else {
                        // TODO: Remove legacy Sunday/Monday boundaries after release of update-hours-logic-for-new-week.
                        $windowStart = date('Y-m-d', strtotime('last Sunday', strtotime('-3 weeks ' . $currentDateString))); /// use 3 weeks.
                        $windowEnd = date('Y-m-d', strtotime('last Monday', strtotime($currentDateString)));
                    }

                    $data = ScheduleMaster::select(DB::raw('ROUND(AVG(ActualHours),2) as ActualHours'))
                        ->where('ProviderID', $providerData->ProviderID)
                        ->whereIn('ScheduleStatusID', [
                            MssqlScheduleMasterStatus::APPROVED->value,
                            MssqlScheduleMasterStatus::COMPLETED->value,
                            MssqlScheduleMasterStatus::IN_PROGRESS->value,
                        ])
                        ->whereBetween('ScheduleDate', [$windowStart, $windowEnd])
                        ->first();

                    return response()->json(['status'=>true, 'hour'=>($data?->ActualHours ?? '')]);
                }
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'gpCalculation.log');

            return response()->json(['status'=>false, 'msg'=>$e->getMessage()]);
        }
    }

    public function saveGp(Request $request)
    {
        try {
            $reqData = $request->all();
            $gpId = $reqData['gpId'] ?? '';
            $nurseData = [];

            foreach ($reqData['aoc_patient_id'] as $k => $v) {
                $status = $reqData['status'][0][0] ?? '';
                foreach ($reqData['aoc_clinician_id'][$k] as $k1 => $v1) {
                    $clientPayer = $reqData['clientPayer'][$k] ?? '';
                    $selectedPayer = $reqData['selectedPayer'][$k][$k1] ?? '';
                    if (isset($reqData['aoc_clinician_id'][$k][$k1])) {
                        $nurseData[$v][] = [
                            'aoc_provider_id' => ($status == 'current' && $reqData['aoc_clinician_id'][$k][$k1] != -1 ? $reqData['aoc_clinician_id'][$k][$k1] : 0),
                            'aoc_candidate_id' => ($status == 'new' && $reqData['aoc_clinician_id'][$k][$k1] != -1 ? $reqData['aoc_clinician_id'][$k][$k1] : 0),
                            'discipline' => $reqData['gpNurseDiscipline'][$k][$k1] ?? '',
                            //this is the fkn hack until we figure out what to do with same client calculation \w different respite options from the business
                            //as of now we have a bug AOC-1874/ If we add one client twice and choose respite and non-respite at the same time we would have wrong calculation
                            //as we merge this records into one and first record is overridden with second, and only one saved
                            //so we added selectedPayer to the nurse data and loading services using this field for it
                            'selectedPayer' => $gpId && $selectedPayer ? $selectedPayer : $clientPayer,
                            'hours' => $reqData['hours'][$k][$k1] ?? 0,
                            'preHours' => $reqData['prevHours'][$k][$k1] ?? 0,
                            'desiredRate' => $reqData['desiredRate'][$k][$k1] ?? 0,
                            'isOldNurse' => $reqData['isOldNurse'][$k][$k1] ?? 0,
                            'nurseName' => $reqData['nurseId'][$k][$k1],
                        ];
                    }
                }
            }

            $concateClientPayerSpecialized = [];

            foreach ($reqData['clientPayer'] as $key => $value) {
                $concateClientPayerSpecialized[] = $value . '@' . $reqData['specialized'][$key] . '@' . $reqData['respite'][$key] . (($reqData['aoc_patient_id'][$key] < 0) ? '@' . $reqData['clientSelectionForGP'][$key] : '');
            }

            $insertUpdateData = [
                'user_id' => Auth::user()->id,
                'aoc_patient_id' => json_encode(
                    array_combine(
                        array_values($reqData['aoc_patient_id']),
                        array_values($concateClientPayerSpecialized)
                    )
                ),
                'nurse_data' => json_encode($nurseData),
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ];

            if (isset($reqData['gpId']) && $reqData['gpId'] != '') {
                //Edit the current record
                $id = $reqData['gpId'];
                $insertUpdateData['updated_at'] = date('Y-m-d H:i:s');
                DB::table('gp_calculator')->where('id', $id)->update($insertUpdateData);
            } else {
                //Insert new GP records
                $insertUpdateData['created_at'] = date('Y-m-d H:i:s');
                $insertUpdateData['updated_at'] = date('Y-m-d H:i:s');
                $id = DB::table('gp_calculator')->insertGetId($insertUpdateData);
            }

            return response()->json(['status'=>true, 'data'=>['id'=>$id]]);
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(), 'gpCalculation.log');

            return response()->json(['status'=>false, 'msg'=>$e->getMessage()]);
        }
    }

    public function calculateGpTable(Request $request)
    {
        $calculatedData = $this->gpCalculation($request->id);
        $preview = $request->preview ?? false;

        return view('gpCalculator.ideaTable', ['calculatedData' => $calculatedData, 'previewMode' => $preview]);
    }

    public function gpCalculation($id)
    {
        $otHoursRateMultiplier = config('constant')['gpOtHoursRateMultiplier'];
        $finalResponse = [];
        $gpDetails = DB::table('gp_calculator')->where('id', $id)->first();

        if (empty($gpDetails)) {
            return $finalResponse;
        }

        if ($gpDetails->nurse_data == '' || !H::isJSON($gpDetails->nurse_data)) {
            return $finalResponse;
        }

        $dataRaw = json_decode($gpDetails->nurse_data, true);
        $regularFinalPay = $overTimeFinalPay = $regularFinalHours = $overTimeFinalHours = 0;
        $finalReimbursementRate = $finalInsurance = $overtimeBlendedRate = 0;
        $totalRecords = 0;
        //Reimbursement
        $gpAocPatientData = json_decode($gpDetails->aoc_patient_id, true);
        $gpAocPatientIds = array_keys($gpAocPatientData);
        $gpConcateData = array_values($gpAocPatientData);
        $gpAocPatientProviders = $specialized = $clientData = $pname = [];
        foreach ($gpConcateData as $key => $val) {
            $parts = explode('@', $val);
            $gpAocPatientProviders[$key] = $parts[0] ?? null;
            $specialized[$key] = $parts[1] ?? null;
            $pname[$key] = $parts[3] ?? null;
        }
        /* TODO currently if the same client picked for whole nurse model, the client info is merged, */
        /* TODO probably we should discuss and change it, so the info in edit mode would display as in add client mode during creation */
        // This one is used for display purpose only
        foreach ($gpAocPatientIds as $key => $value) {
            if ($value > 0) {
                $patientDetails = MssqlPatients::find($value);
                $clientData[$key]['client_name'] = $patientDetails->Patient_FirstName . ' ' . $patientDetails->Patient_LastName;
                $clientData[$key]['payer'] = $gpAocPatientProviders[$key];
            } else {
                $clientData[$key]['client_name'] = $pname[$key];
                $clientData[$key]['payer'] = $gpAocPatientProviders[$key];
            }
        }
        $previewIdx = 0;
        foreach ($gpAocPatientIds as $key => $gpAocPatientId) {
            if ($gpAocPatientId > 0) {
                $clientlist = DB::table('mssql_patients')
                ->select('mssql_patients.id', 'mssql_patients.Payer', 'mssql_patients.CF_Skills', 'mssql_facilities.State', 'mssql_patients.PatientAccount')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->where('mssql_patients.id', $gpAocPatientId)
                ->first();
            } else {
                $patient = new \stdClass();
                $patient->id = $gpAocPatientId;
                $patient->State = '';
                $patient->PatientAccount = '';
                $clientlist = $patient;
            }

            $totalRecords += count($dataRaw[$clientlist->id]);
            $isSpecialized = $specialized[$key];
            //P1-3650 - Set up rate based on state
            $adminPct = config('constant')['gpAdminPct'][$clientlist->State] ?? 0.1;
            $insurance = config('constant')['gpInsuranceCost'][$clientlist->State] ?? 40;
            $cnt = 0;

            foreach ($dataRaw[$clientlist->id] as $dataTemp) {
                /* AOC-1874 introducing selected payer for a proper calculation,
                   if same client chosen more than once \w different respite option.
                   Old approach saved as default for backwards compatibility */
                $selectedPayer = $dataTemp['selectedPayer'] ?? $gpAocPatientProviders[$key];
                $serviceCode = $dataTemp['discipline'];
                $serviceCode .= $isSpecialized == 1 ? ' SPECIALIZED' : '';

                $requestPayerRate = new RequestPayerRateDTO(
                    $clientlist,
                    $selectedPayer,
                    $serviceCode,
                    $dataTemp['discipline'],
                    (bool) $isSpecialized
                );
                $rate = $this->payerRatesService->getPayerRate($requestPayerRate);
                //Regular Pay (Sum for each nurse)
                $regularHours = $overTimeHours = $finalPay = $totalPaid = $totalRegularOtHours = 0;
                $totalRegularOtHours = (float) ($dataTemp['hours']);
                $totalPreviousHours = (float) ($dataTemp['preHours']);

                if ($totalRegularOtHours + $totalPreviousHours > 40) {
                    if ($totalPreviousHours > 40) {
                        $regularHours = 0;
                        $overTimeHours = $totalRegularOtHours;
                    } else {
                        $regularHours = 40 - $totalPreviousHours;
                        $overTimeHours = $totalRegularOtHours - $regularHours;
                    }
                } else {
                    if ($totalRegularOtHours > 40) {
                        $regularHours = 40;
                        $overTimeHours = $totalRegularOtHours - 40;
                    } else {
                        $regularHours = $totalRegularOtHours;
                        $overTimeHours = 0;
                    }
                }

                $dataRaw[$clientlist->id][$cnt]['regular_hours'] = $regularHours;
                $regularPay = $regularHours * (float) $dataTemp['desiredRate'];
                $dataRaw[$clientlist->id][$cnt]['regular_pay'] = $regularPay;

                $dataRaw[$clientlist->id][$cnt]['overtime_hours'] = $overTimeHours;
                $overTimePay = ($overTimeHours * (float) $dataTemp['desiredRate'] * $otHoursRateMultiplier);
                $dataRaw[$clientlist->id][$cnt]['overtime_pay'] = $overTimePay;

                $finalHours = $regularHours + $overTimeHours;
                $finalPay = $regularPay + $overTimePay;

                $dataRaw[$clientlist->id][$cnt]['reimbursement_rate'] = ($rate * $finalHours);

                $totalReimbursement = $rate * $finalHours;

                if ($finalHours >= 30) {
                    $totalPaid = $finalPay + ($finalPay * $adminPct) + $insurance;
                    $dataRaw[$clientlist->id][$cnt]['insurance'] = $insurance;
                } else {
                    $totalPaid = $finalPay + ($finalPay * $adminPct);
                    $dataRaw[$clientlist->id][$cnt]['insurance'] = 0;
                }

                $totalProfitability = $totalReimbursement - $totalPaid;
                $totalGP = ($totalReimbursement > 0) ?
                    number_format(($totalProfitability / $totalReimbursement) * 100, 2) :
                    0;
                $dataRaw[$clientlist->id][$cnt]['total_profitability'] = $totalProfitability;
                $dataRaw[$clientlist->id][$cnt]['total_paid'] = $totalPaid;
                $dataRaw[$clientlist->id][$cnt]['total_reimbursement'] = $totalReimbursement;
                $dataRaw[$clientlist->id][$cnt]['total_gp'] = $totalGP;
                $dataRaw[$clientlist->id][$cnt]['preview'] = (string) $previewIdx . '0';
                $cnt++;
                $previewIdx++;
            }
        }

        $newFinalResponse = [];
        $newFinalResponse['client_data'] = $clientData;
        $newFinalResponse['data'] = $dataRaw;

        foreach ($dataRaw as $clientId => $nursesData) {
            foreach ($nursesData as $nurseKey => $nurseData) {
                $regularFinalPay += $nurseData['regular_pay'];
                $overTimeFinalPay += $nurseData['overtime_pay'];
                $regularFinalHours += $nurseData['regular_hours'];
                $overTimeFinalHours += $nurseData['overtime_hours'];
                $finalReimbursementRate += $nurseData['reimbursement_rate'];
                $finalInsurance += $nurseData['insurance'];
            }
        }

        //  NEW CALCULATION BASED ON AOC-1782

        // 1️⃣ Aggregate totals
        $totalRegularHours = $totalOvertimeHours = $totalAllHours = 0;
        $totalRegularPay = $totalOvertimePay = $totalAllPay = 0;

        foreach ($dataRaw as $clientId => $nursesData) {
            foreach ($nursesData as $nurseKey => $nurseData) {
                $totalRegularHours += (float) $nurseData['regular_hours'];
                $totalOvertimeHours += (float) $nurseData['overtime_hours'];
                $totalRegularPay += (float) $nurseData['regular_pay'];
                $totalOvertimePay += (float) $nurseData['overtime_pay'];
            }
        }

        $totalAllHours = $totalRegularHours + $totalOvertimeHours;
        $totalAllPay = $totalRegularPay + $totalOvertimePay;

        //Blended rates
        $regularBlendedRate = $totalRegularHours > 0 ? $totalRegularPay / $totalRegularHours : 0;
        $otBlendedRate = $totalOvertimeHours > 0 ? $totalOvertimePay / $totalOvertimeHours : 0;
        $allBlendedRate = $totalAllHours > 0 ? $totalAllPay / $totalAllHours : 0;

        //Store to response
        $newFinalResponse['regular_final_hours'] = $totalRegularHours;
        $newFinalResponse['overtime_final_hours'] = $totalOvertimeHours;
        $newFinalResponse['all_final_hours'] = $totalAllHours;

        $newFinalResponse['regular_final_pay'] = number_format($totalRegularPay, 2);
        $newFinalResponse['overtime_final_pay'] = number_format($totalOvertimePay, 2);
        $newFinalResponse['all_final_pay'] = number_format($totalAllPay, 2);

        $newFinalResponse['regular_blended_rate'] = number_format($regularBlendedRate, 2);
        $newFinalResponse['overtime_blended_rate'] = number_format($otBlendedRate, 2);
        $newFinalResponse['all_blended_rate'] = number_format($allBlendedRate, 2);

        //Reimbursement
        $finalReimbursementRate = $totalAllHours > 0 ? $finalReimbursementRate / $totalAllHours : 0;
        $newFinalResponse['final_reimbursement_rate'] = number_format($finalReimbursementRate, 2);
        $finalReimbursement = $finalReimbursementRate * $totalAllHours;
        $newFinalResponse['final_reimbursement'] = number_format($finalReimbursement, 2);

        //i-icon totals
        $finalPaid = $totalAllPay + ($totalAllPay * 0.10) + $finalInsurance;
        $newFinalResponse['final_paid'] = number_format($finalPaid, 2);

        $finalProfitability = $finalReimbursement - $finalPaid;
        $newFinalResponse['final_profitability'] = number_format($finalProfitability, 2);

        $finalGP = ($finalReimbursement > 0)
            ? number_format(($finalProfitability / $finalReimbursement) * 100, 2)
            : 0;
        $newFinalResponse['final_gp'] = $finalGP;

        $newFinalResponse['final_insurance'] = $finalInsurance;
        $newFinalResponse['final_weekly_pay_pre_tax'] = number_format($totalAllPay, 2);

        // Ideal table
        $gpFixedPercentage = config('constant')['gpFixedPercentage'];
        $ideaTable = [];

        foreach ($gpFixedPercentage as $gpPercent) {
            $totalProfitability = ($finalReimbursement * $gpPercent) / 100;
            $totalPaid = $finalReimbursement - $totalProfitability;
            $weeklyPay = ($totalPaid - $finalInsurance) / 1.1;

            if ($totalOvertimeHours > 0) {
                $calculatedHours = $totalRegularHours + ($totalOvertimeHours * 1.5);
                $baseRate = $weeklyPay / $calculatedHours;
            } else {
                $baseRate = $weeklyPay / $totalAllHours;
            }

            $ideaTable[] = [
                'ideal_profitability' => number_format($totalProfitability, 2),
                'ideal_paid' => number_format($totalPaid, 2),
                'ideal_base_rate' => number_format($baseRate, 2),
                'ideal_gp' => $gpPercent,
            ];
        }

        $newFinalResponse['ideal_table'] = $ideaTable;

        return $newFinalResponse;
    }

    public function list(Request $request)
    {
        $columns = [
            ['name' => 'id', 'label' => 'ID', 'visible' => false],
            ['name' => 'client', 'label' => 'Client'],
            ['name' => 'userName', 'label' => 'User'],
            ['name' => 'created_at', 'label' => 'Created At'],
            ['name' => 'updated_at', 'label' => 'Updated At'],
        ];
        if ($request->ajax()) {
            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = DB::table('gp_calculator')
                ->leftJoin('mssql_patients', function ($join) {
                    $join->on(
                        DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(gp_calculator.aoc_patient_id), '$[0]')) AS UNSIGNED)"),
                        '=',
                        'mssql_patients.id'
                    );
                })
                ->leftJoin('users', 'users.id', '=', 'gp_calculator.user_id')
                ->select(
                    'gp_calculator.*',
                    'users.name as userName',
                    DB::raw("
                    CASE
                        WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(gp_calculator.aoc_patient_id), '$[0]')) AS SIGNED) > 0 THEN
                            CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)
                        ELSE
                            SUBSTRING_INDEX(
                                CAST(
                                    JSON_UNQUOTE(
                                        JSON_EXTRACT(
                                            gp_calculator.aoc_patient_id,
                                            CONCAT(
                                                '$.\"',
                                                JSON_UNQUOTE(JSON_EXTRACT(JSON_KEYS(gp_calculator.aoc_patient_id), '$[0]')),
                                                '\"'
                                            )
                                        )
                                    ) AS CHAR CHARACTER SET utf8mb3
                                ) COLLATE utf8mb3_unicode_ci,
                                CAST('@' AS CHAR CHARACTER SET utf8mb3) COLLATE utf8mb3_unicode_ci,
                                -1
                            )
                    END AS client
                "),
                    DB::raw("DATE_FORMAT(gp_calculator.created_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as created_at"),
                    DB::raw("DATE_FORMAT(gp_calculator.updated_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as updated_at")
                );

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->where(function ($query) use ($search) {
                    $query->where('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }

        return view('gpCalculator.grid', ['columns' => $columns]);
    }

    public function clear(Request $request)
    {
        try {
            $gpId = $request->input('gpId');
            if (isset($gpId) && $gpId != '') {
                DB::table('gp_calculator')->where('id', $gpId)->delete();
            }

            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function getService(Request $request)
    {
        try {
            $aoc_patient_id = $request->input('aoc_patient_id');
            $isRespite = $request->input('isRespite');
            $permOnly = $request->input('permOnly');
            $payer = $request->input('payer');
            $onlyByPayer = LD::checkFeatureFlag('expand-services-to-payer');
            $useGaPayerServiceFallback = LD::checkFeatureFlag('gp-ga-payer-services-without-auth');
            $additionalOptions = collect();

            //TODO TEMPORARILY. GP Calculator. Allow to load GA payer services without authorization-based, is_billing filtering
            if ($useGaPayerServiceFallback) {
                $query = DB::query()
                    ->from('service_codes_daas', 'SVM')
                    ->select('SVM.payerid', 'SVM.name', DB::raw('NULL as authorization_id'))
                    ->join('payment_source AS PS', 'PS.payersource_id', '=', 'SVM.payerid')
                    ->where('PS.name', $payer)
                    ->where('SVM.is_billable_daas', 1)
                    ->where('SVM.is_terminated', 0)
                    ->groupBy('SVM.name');

                if ($permOnly === 'true') {
                    $query->where('SVM.name', 'NOT LIKE', '%coverage%');
                }

                if ($isRespite === 'true') {
                    $query->where('SVM.name', 'LIKE', '%respite%');
                }

                if ($isRespite === 'false') {
                    $query->where('SVM.name', 'NOT LIKE', '%respite%');
                }

            } else {
                $query = DB::query()
                    ->from('authorizations_service', 'AUS')
                    ->select('SVM.payerid', 'SVM.name', 'AUS.authorization_id')
                    ->join('service_codes_daas AS SVM', 'SVM.service_id', '=', 'AUS.service_id')
                    ->join('authorizations AS AM', 'AM.auth_id', '=', 'AUS.authorization_id')
                    ->whereRaw('CURDATE() BETWEEN date(AM.start_date) and date(AM.end_date)')
                    ->where('SVM.is_billable_daas', 1)
                    ->where('SVM.is_terminated', 0)
                    ->groupBy('SVM.name');
                if (is_numeric($aoc_patient_id) && $aoc_patient_id > 0 && !$onlyByPayer) {
                    $patientData = MssqlPatients::find($aoc_patient_id);
                    $query->where('AM.PatientID', $patientData->PatientID);
                    if ($permOnly === 'true') {
                        $query->where('SVM.name', 'NOT LIKE', '%coverage%');
                    }
                    if ($isRespite === 'true') {
                        $query->where('SVM.name', 'LIKE', '%respite%');
                    }
                    if ($isRespite === 'false') {
                        $query->where('SVM.name', 'NOT LIKE', '%respite%');
                    }
                } else {
                    $query->join('payment_source AS PS', 'PS.payersource_id', '=', 'SVM.payerid');
                    $query->where('PS.name', $payer);
                }
            }

            if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates') && $payer){
                $paymentSource = DB::table('payment_source')->where('name', $payer)->first();
                $payerSourceId = $request->input('payer_id') ?: ($paymentSource ? $paymentSource->payersource_id : null);

                $payerRate = PayerRates::with(['services' => function ($query){
                    $query->whereNotNull('rate');
                }])->where(function($q) use ($payer, $payerSourceId) {
                    $q->where('payer', $payer)->where('is_custom', 1);

                    if ($payerSourceId) {
                        $q->orWhere(function($subQ) use ($payerSourceId) {
                            $subQ->where('payer_id', $payerSourceId)->where('is_custom', 0);
                        });
                    }
                })
                    ->where('payer_rates.is_deleted', 0)
                    ->where('status', PayerRatesStatus::APPROVED->value)
                    ->whereDate('effectiveThrough', '>=', now())
                    ->whereDate('effectiveFrom', '<=', now())
                    ->first();

                if ($payerRate && $payerRate->services) {
                    $additionalOptions = collect($payerRate->services)->map(function ($service) {
                        return (object) [
                            'payerid' => null,
                            'name' => $service->service_name,
                            'authorization_id' => null,
                        ];
                    });
                }
            }

            $options = $query->get();

            if ($additionalOptions->isNotEmpty()) {
                $options = $options->merge($additionalOptions)->unique('name')->values();
            }

            return response()->json(['status' => true, 'options' => $options->toArray() ?? []]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function loadPayerDropdown(Request $request)
    {
        try {
            $options = [];
            $isRespite = $request->input('isRespite');

            if ($isRespite == 'true') {
                $options = H::getHelper('ConstantHelper')->getRespitePayers();
            } else {
                $options = H::getHelper('ConstantHelper')->getPayers([]);
            }

            return response()->json(['status' => true, 'options' => $options]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Temporary placing this guy here to address issues. New controller version is in development.
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getCandidateName(Request $request) : JsonResponse
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $providerRepository = new ProvidersRepository();
        $clientList = $providerRepository->getProvidersList($term);
        $clientListData = [];
        foreach ($clientList as $clientSubData) {
            if (LD::checkFeatureFlag('add-schedule-validation')) {
                $nthSchedulesIds = [];
                $nthScheduleMsg = '';
                $ipeamesg = '';

                if (!empty($clientSubData['nth_schedule_ids'])) {
                    $nthRecords = explode('**', $clientSubData['nth_schedule_ids']);
                    foreach ($nthRecords as $nthRecord) {
                        [$Recruiter, $aoc_patient_id, $nth_schedule_ids, $client_name, $pay_rate] = explode('#', $nthRecord);
                        $nthSchedulesIds = array_merge($nthSchedulesIds, explode(',', $nth_schedule_ids));
                        $nthScheduleMsg .= 'This candidate is being recruited by <b>' . $Recruiter . '</b> for ' . $client_name . ' at $' . $pay_rate . '/hr with the following schedules:<ul>';
                        $nthScheduleList = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($aoc_patient_id, $nth_schedule_ids);
                        foreach ($nthScheduleList as $nthSchedule) {
                            $nthScheduleMsg .= '<li>' . $nthSchedule['StartDayOfWeek'] . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['StartHour'] . ':' . $nthSchedule['StartMinute']) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthSchedule['EndHour'] . ':' . $nthSchedule['EndMinute']) . '</li>';
                        }
                        $nthScheduleMsg .= '</ul>';
                    }
                    $nthScheduleMsg .= '<p>Please do not recruit the candidate for conflicting schedules.</p>';
                }

                if (!empty($clientSubData['ipea_schedule_ids'])) {
                    $ipeaRecords = explode('**', $clientSubData['ipea_schedule_ids']);
                    foreach ($ipeaRecords as $ipeaRecord) {
                        [$Recruiter, $appointment_date, $Branch_Location] = explode('#', $ipeaRecord);
                        $ipeamesg .= '<p>This candidate was scheduled for an IPEA on ' . $appointment_date . ' in ' . $Branch_Location . ' by ' . $Recruiter . '.</p>';
                    }
                    $nthScheduleMsg .= $ipeamesg;
                }

                $clientListData[] = ['label' => $clientSubData['LastName'] . ', ' . $clientSubData['FirstName'] . ' | ' . $clientSubData['ClinicianID'] . ' | ' . $clientSubData['EmploymentStatus'] . ' | ' . $clientSubData['BranchName'],
                    'value' => $clientSubData['FirstName'] . ' ' . $clientSubData['LastName'], 'email' => $clientSubData['External_Email'], 'phone' => $clientSubData['Mobile'], 'title' => $clientSubData['DisciplineName'], 'id' => $clientSubData['id'], 'nthScheduleMsg' => $nthScheduleMsg, 'nthSchedulesIds' => implode(',', $nthSchedulesIds), 'isIneligibleRehire' => isset($clientSubData['CF_NotEligibleForRehire']) ? $clientSubData['CF_NotEligibleForRehire'] : '', 'ClinicianID' => isset($clientSubData['ClinicianID']) ? $clientSubData['ClinicianID'] : ''];
            } else {
                $clientListData[] = ['label' => $clientSubData['LastName'] . ', ' . $clientSubData['FirstName'] . ' | ' . $clientSubData['ClinicianID'] . ' | ' . $clientSubData['EmploymentStatus'] . ' | ' . $clientSubData['BranchName'],
                    'value' => $clientSubData['FirstName'] . ' ' . $clientSubData['LastName'], 'email' => $clientSubData['External_Email'], 'phone' => $clientSubData['Mobile'], 'title' => $clientSubData['DisciplineName'], 'id' => $clientSubData['id'], 'isIneligibleRehire' => isset($clientSubData['CF_NotEligibleForRehire']) ? $clientSubData['CF_NotEligibleForRehire'] : '', 'ClinicianID' => isset($clientSubData['ClinicianID']) ? $clientSubData['ClinicianID'] : ''];
            }

            //TODO we probably need to do this in one go for all providers
            if (!empty($request->aoc_patient_id)) {
                $date = Carbon::today()->subDays(30);
                $today = Carbon::today();
                $lastScheduleData = DB::table('mssql_schedulemaster');
                $lastScheduleData->leftJoin('mssql_patients', function ($query) {
                    $query->on('mssql_patients.PatientID', '=', 'mssql_schedulemaster.PatientID');
                });
                $lastScheduleData->leftJoin('mssql_providers', function ($query) {
                    $query->on('mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID');
                });
                $lastScheduleData->where('mssql_patients.id', $request->aoc_patient_id);
                $lastScheduleData->where('mssql_providers.id', $clientSubData['id']);
                $lastScheduleData->whereBetween('mssql_schedulemaster.ScheduleDate', [$date, $today]);
                $clientListData[count($clientListData) - 1]['scheduled'] = $lastScheduleData->count();
            } else {
                $clientListData[count($clientListData) - 1]['scheduled'] = -1;
            }
        }

        return response()->json($clientListData);
    }
}
