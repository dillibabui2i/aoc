<?php

declare(strict_types=1);

namespace App\Services\GpCalculator;

use App\DTO\GpCalculator\RequestPayerRateDTO;
use App\Enums\GpCalculationTypes;
use App\Helpers\LD;
use Illuminate\Support\Facades\DB;

final class GpCalculatorService
{
    const INSURANCE_PAY_UNIT = 40;
    public const ESCALATION_GUIDE_FEATURE_FLAG = 'add-GM-table-to-gp-calc';

    private float $otHoursRateMultiplier;

    /** @var array<string,float> */
    private array $adminPctByState;

    /** @var array<string,float> */
    private array $insuranceByState;

    /** @var float[] */
    private array $gpFixedPercentage;

    public function __construct(
        private readonly PayerRatesService $payerRatesService,
        private readonly GpCalculatorEscalationGuideService $escalationGuideService,
        array $config = []
    ) {
        $this->otHoursRateMultiplier = (float) ($config['otHoursRateMultiplier'] ?? config('constant.gpOtHoursRateMultiplier', 1.5));
        $this->adminPctByState = (array) ($config['gpAdminPct'] ?? config('constant.gpAdminPct', []));
        $this->insuranceByState = (array) ($config['gpInsuranceCost'] ?? config('constant.gpInsuranceCost', []));
        $this->gpFixedPercentage = (array) ($config['gpFixedPercentage'] ?? config('constant.gpFixedPercentage', [10, 15, 20, 25, 30]));
    }

    /**
     * Calculates and returns a payload JS can hydrate easily:
     * - keeps the SAME shape as incoming payload (status/id/data/persisted)
     * - augments data.nurses[] with computed fields
     * - adds data.totals, data.ideal_table, data.client_data
     *
     * @param array $payload decoded JSON
     * @return array
     */
    public function calculate(array $payload): array
    {
        [$calculatedDataRaw, $clientData, $totals, $finals, $idealTable, $grossMarginGuide] = $this->calculateInternal($payload);

        // If validation failed inside, keep payload but ensure status false if you want.
        if ($calculatedDataRaw === null) {
            return $payload;
        }

        return $this->mergeComputedIntoPayload(
            payload: $payload,
            calculatedDataRaw: $calculatedDataRaw,
            clientData: $clientData,
            totals: $totals,
            finals: $finals,
            idealTable: $idealTable,
            grossMarginGuide: $grossMarginGuide
        );
    }

    private function guessCalculationType(array $clients, array $nurses) : GpCalculationTypes
    {
        $clientsCount = count(array_unique(array_column($clients, 'patientId')));
        $nursesCount = count(array_unique(array_column($nurses, 'nurseId')));
        if ($clientsCount === 1 || $nursesCount > 1) {
            return GpCalculationTypes::SINGLE_CLIENT_MULTIPLE_NURSES;
        }

        if ($clientsCount > 1 || $nursesCount === 1) {
            return GpCalculationTypes::MULTIPLE_CLIENTS_SINGLE_NURSE;
        }

        return GpCalculationTypes::SINGLE_CLIENT_SINGLE_NURSE;
    }

    /**
     * @return array{
     *   0: (array<string,array<int,array<string,mixed>>>|null),
     *   1: array<string,array{client_name:string,payer:string}>,
     *   2: array<string,mixed>,
     *   3: array<string,mixed>,
     *   4: array<int,array<string,mixed>>,
     *   5: (array<string,mixed>|null)
     * }
     */
    private function calculateInternal(array $data): array
    {
        $clients = $data['clients'] ?? null;
        $nurses = $data['nurses'] ?? null;
        $links = $data['links'] ?? null;

        $calculationType = GpCalculationTypes::tryFrom($data['calculationType']);

        if (!$calculationType) {
            $calculationType = $this->guessCalculationType($clients, $nurses);
        }

        if (!is_array($clients) || !is_array($nurses) || !is_array($links)) {
            return [null, [], [], [], [], []];
        }

        $clientMap = $this->indexClients($clients);
        $nurseMap = $this->indexNurses($nurses);
        $grouped = $this->groupNursesByClientSlot($links, $clientMap, $nurseMap);
        $primaryClient = $this->resolvePrimaryClient($clients, $links, $clientMap);

        $clientData = $this->buildClientDataForDisplay($clientMap);
        $calculatedDataRaw = [];

        foreach ($grouped as $clientSlotKey => $nurseRows) {
            $client = $clientMap[$clientSlotKey] ?? null;
            if (!$client) {
                continue;
            }

            // Old behavior: fetch patient+facility state when patientId>0; else synthetic
            $clientList = $this->resolveClientList($client);
            $adminPercent = $this->getAdminPctForState((string) ($clientList->State ?? ''));
            $insurance = $this->getInsuranceForState((string) ($clientList->State ?? ''));

            $cnt = 0;
            foreach ($nurseRows as $row) {
                $selectedPayer = (string) ($row['selectedPayer'] ?? ($client['clientPayer'] ?? ''));
                $discipline = (string) ($row['discipline'] ?? '');

                // PD-5729: the SPECIALIZED suffix is no longer appended automatically, because not every payer/state
                // has specialized services. A user picks a specialized service from the service dropdown instead.
                // https://angelsofcare-team.atlassian.net/browse/PD-5729
                $requestPayerRate = new RequestPayerRateDTO(
                    $clientList,
                    $selectedPayer,
                    $discipline,
                    $discipline,
                    false
                );

                $rate = (float) $this->payerRatesService->getPayerRate($requestPayerRate);
                $hours = (float) ($row['hours'] ?? 0);
                $prevHours = (float) ($row['prevHours'] ?? $row['preHours'] ?? 0);

                [$regularHours, $overtimeHours] = $this->splitRegularOvertimeHours($hours, $prevHours);
                $desiredRate = (float) ($row['desiredRate'] ?? 0);
                $regularPay = $this->calcRegularPay($regularHours, $desiredRate);
                $overtimePay = $this->calcOvertimePay($overtimeHours, $desiredRate);

                $finalHours = $regularHours + $overtimeHours;
                $finalPay = $regularPay + $overtimePay;
                $totalReimbursement = $this->calcTotalReimbursement($rate, $finalHours);

                [$totalPaid, $insuranceApplied] = $this->calcTotalPaid(
                    finalPay: $finalPay,
                    adminPct: $adminPercent,
                    insurance: $insurance,
                    finalHours: $finalHours
                );

                $totalProfitability = $this->calcProfitability($totalReimbursement, $totalPaid);
                $totalGpPct = $this->calcGpPercent($totalProfitability, $totalReimbursement);
                //OT calculated per nurse, which applies only for whole home
                $row['regular_hours'] = $regularHours;
                $row['overtime_hours'] = $overtimeHours;
                $row['regular_pay'] = $regularPay;
                $row['overtime_pay'] = $overtimePay;
                $row['reimbursement_rate'] = $rate;
                $row['insurance'] = $insuranceApplied;
                $row['total_paid'] = $totalPaid;
                $row['total_reimbursement'] = $totalReimbursement;
                $row['total_profitability'] = $totalProfitability;
                $row['total_gp'] = $totalGpPct;
                $calculatedDataRaw[$clientSlotKey][$cnt] = $row;

                $cnt++;
            }
        }
        //aggregate totals + calculations based on calculation type
        $totals = $this->aggregateTotals($calculatedDataRaw, $calculationType);
        $finals = $this->buildFinalSummary($totals);

        $idealTable = $this->buildIdealTable(
            finalReimbursement: (float) $finals['final_reimbursement_raw'],
            finalInsurance: (float) $finals['final_insurance_raw'],
            totalRegularHours: (float) $totals['total_regular_hours'],
            totalOvertimeHours: (float) $totals['total_overtime_hours'],
            totalAllHours: (float) $totals['total_all_hours']
        );

        $grossMarginGuide = $this->shouldAddEscalationGuide()
            ? $this->escalationGuideService->buildForClient($primaryClient)
            : null;

        return [$calculatedDataRaw, $clientData, $totals, $finals, $idealTable, $grossMarginGuide];
    }

    private function resolveClientList(array $client): object
    {
        $patientId = (int) ($client['patientId'] ?? $client['clientKey'] ?? 0);

        if ($patientId > 0) {
            $clientList = DB::table('mssql_patients')
                ->select(
                    'mssql_patients.id',
                    'mssql_patients.Payer',
                    'mssql_patients.CF_Skills',
                    'mssql_facilities.State',
                    'mssql_patients.PatientAccount'
                )
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->where('mssql_patients.id', $patientId)
                ->first();

            if ($clientList) {
                return $clientList;
            }
        }

        $patient = new \stdClass();
        $patient->id = $patientId;
        $patient->State = '';
        $patient->PatientAccount = '';
        $patient->Payer = '';
        $patient->CF_Skills = '';

        return $patient;
    }

    // ---------------------------------------------------------------------
    // Mapping helpers (NEW JSON => group rows like old $dataRaw)
    // ---------------------------------------------------------------------

    /** @param array<int,array<string,mixed>> $clients */
    private function indexClients(array $clients): array
    {
        $map = [];
        foreach ($clients as $c) {
            $clientKey = (string) ($c['clientKey'] ?? '');
            $slotIndex = (int) ($c['slotIndex'] ?? 0);
            if ($clientKey === '') {
                continue;
            }
            $map[$clientKey . ':' . $slotIndex] = $c;
        }

        return $map;
    }

    /** @param array<int,array<string,mixed>> $nurses */
    private function indexNurses(array $nurses): array
    {
        $map = [];
        foreach ($nurses as $n) {
            $clientId = (string) ($n['clientId'] ?? '');
            $nurseId = (string) ($n['nurseId'] ?? $n['aoc_clinician_id'] ?? '');
            $nurseIndex = (int) ($n['index'] ?? 0);

            if ($clientId === '' || $nurseId === '') {
                continue;
            }

            $key = $clientId . ':' . $nurseId . ':' . $nurseIndex;
            $map[$key] = $n;
        }

        return $map;
    }

    /**
     * Uses links[] to create grouping by client slot. Also supports parsing link['nurseKey'] fallback.
     */
    private function groupNursesByClientSlot(array $links, array $clientMap, array $nurseMap): array
    {
        $grouped = [];

        foreach ($links as $link) {
            $clientKey = (string) ($link['clientKey'] ?? '');
            $slotIndex = (int) ($link['slotIndex'] ?? 0);
            $slotKey = $clientKey . ':' . $slotIndex;

            if ($clientKey === '' || !isset($clientMap[$slotKey])) {
                continue;
            }

            $clientId = (string) ($link['nurseClientId'] ?? $link['clientKey'] ?? '');
            $nurseId = (string) ($link['nurseId'] ?? '');
            $idx = (int) ($link['nurseIndex'] ?? 0);

            $nurseKey = $clientId . ':' . $nurseId . ':' . $idx;

            if (!isset($nurseMap[$nurseKey])) {
                // fallback: nurseKey already present in link (like "224494:382943:0")
                $nk = (string) ($link['nurseKey'] ?? '');
                if ($nk !== '' && isset($nurseMap[$nk])) {
                    $nurseKey = $nk;
                } else {
                    continue;
                }
            }

            $grouped[$slotKey][] = $nurseMap[$nurseKey];
        }

        foreach ($clientMap as $slotKey => $slotValue) {
            $grouped[$slotKey] = $grouped[$slotKey] ?? [];
        }

        return $grouped;
    }

    /**
     * For UI display; keeps prior intent (name + payer).
     * If you want old “real MSSQL full name when patientId>0”, you can re-enable it here.
     *
     * @param array<string,array<string,mixed>> $clientMap
     * @return array<string,array{client_name:string,payer:string}>
     */
    private function buildClientDataForDisplay(array $clientMap): array
    {
        $out = [];
        foreach ($clientMap as $slotKey => $c) {
            $out[$slotKey] = [
                'client_name' => (string) ($c['clientSelection'] ?? ''),
                'payer' => (string) ($c['clientPayer'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Multi-client handling is intentionally out of scope for this guide.
     * We resolve the base client from the first link when possible and fall back to the first client row.
     *
     * @param array<int,array<string,mixed>> $clients
     * @param array<int,array<string,mixed>> $links
     * @param array<string,array<string,mixed>> $clientMap
     * @return array<string,mixed>|null
     */
    private function resolvePrimaryClient(array $clients, array $links, array $clientMap): ?array
    {
        $firstLink = $links[0] ?? null;
        if (is_array($firstLink)) {
            $slotKey = (string) ($firstLink['clientKey'] ?? '') . ':' . (int) ($firstLink['slotIndex'] ?? 0);
            if (isset($clientMap[$slotKey])) {
                return $clientMap[$slotKey];
            }
        }

        $firstClient = $clients[0] ?? null;
        return is_array($firstClient) ? $firstClient : null;
    }

    // ---------------------------------------------------------------------
    // Formulas
    // ---------------------------------------------------------------------

    /** @return array{0:float,1:float} [regularHours, overtimeHours] */
    private function splitRegularOvertimeHours(float $hours, float $prevHours): array
    {
        $regular = 0.0;
        $ot = 0.0;

        if ($hours + $prevHours > 40) {
            if ($prevHours > 40) {
                $regular = 0.0;
                $ot = $hours;
            } else {
                $regular = 40 - $prevHours;
                $ot = $hours - $regular;
            }
        } else {
            if ($hours > 40) {
                $regular = 40;
                $ot = $hours - 40;
            } else {
                $regular = $hours;
                $ot = 0.0;
            }
        }

        return [(float) $regular, (float) $ot];
    }

    private function calcRegularPay(float $regularHours, float $desiredRate): float
    {
        return $regularHours * $desiredRate;
    }

    private function calcOvertimePay(float $overtimeHours, float $desiredRate): float
    {
        return $overtimeHours * $desiredRate * $this->otHoursRateMultiplier;
    }

    private function calcTotalReimbursement(float $rate, float $finalHours): float
    {
        return $rate * $finalHours;
    }

    /**
     * Rule:
     * - always add adminPct on finalPay
     * - if finalHours >= 30, add insurance
     *
     * @return array{0:float,1:float} [totalPaid, insuranceApplied]
     */
    private function calcTotalPaid(float $finalPay, float $adminPct, float $insurance, float $finalHours): array
    {
        $insuranceApplied = 0.0;
        $totalPaid = $finalPay + ($finalPay * $adminPct);

        if ($finalHours >= 30) {
            $insuranceApplied = $this->calculateInsuranceForNurse($finalHours, $insurance);
            $totalPaid += $insuranceApplied;
        }

        return [(float) $totalPaid, (float) $insuranceApplied];
    }

    private function calcProfitability(float $totalReimbursement, float $totalPaid): float
    {
        return $totalReimbursement - $totalPaid;
    }

    private function calcGpPercent(float $profitability, float $reimbursement): float
    {
        if ($reimbursement <= 0) {
            return 0.0;
        }

        return (float) number_format(($profitability / $reimbursement) * 100, 2, '.', '');
    }

    private function getAdminPctForState(?string $state = null): float
    {
        if ($state === null) {
            return 0.10;
        }

        return (float) ($this->adminPctByState[$state] ?? 0.10);
    }

    private function getInsuranceForState(?string $state = null): float
    {
        if ($state === null) {
            return 40.0;
        }

        return (float) ($this->insuranceByState[$state] ?? 40.0);
    }

    private function calculateInsuranceForNurse(float $hours, $insuranceRate): float
    {
        return $hours >= 30 ? $insuranceRate : 0.0;
    }

    private function calculateInsurancePerNurse(array $dataRaw): float
    {
        $hoursByNurse = [];

        foreach ($dataRaw as $rows) {
            foreach ($rows as $row) {
                $nurseId = $row['nurseId'] ?? $row['aoc_clinician_id'] ?? null;
                $nurseName = trim((string) ($row['nurseName'] ?? ''));

                if ($nurseId && $nurseId !== '-1') {
                    $key = 'id:' . $nurseId;
                } elseif ($nurseName !== '') {
                    $key = 'name:' . mb_strtolower($nurseName);
                } else {
                    continue;
                }

                $hours = (float) ($row['regular_hours'] ?? 0)
                    + (float) ($row['overtime_hours'] ?? 0);

                $hoursByNurse[$key] = ($hoursByNurse[$key] ?? 0) + $hours;
            }
        }

        $insurance = 0.0;
        $rate = $this->getInsuranceForState();

        foreach ($hoursByNurse as $totalHours) {
            if ($totalHours >= 30) {
                $insurance += $rate;
            }
        }

        return $insurance;
    }

    private function aggregateTotals(array $dataRaw, GpCalculationTypes $calculationType): array
    {
        // When the SAME nurse is split across multiple clients, OT must be computed on the TOTAL
        // hours for that nurse (not per-client row), otherwise we double-apply the 40h threshold.
        if ($calculationType === GpCalculationTypes::MULTIPLE_CLIENTS_SINGLE_NURSE) {
            return $this->aggregateWholeNurseTotals($dataRaw);
        }

        return $this->aggregateWholeHomeTotals($dataRaw);
    }

    private function aggregateWholeHomeTotals(array $dataRaw) : array
    {
        $totalRegularHours = 0.0;
        $totalOvertimeHours = 0.0;
        $totalRegularPay = 0.0;
        $totalOvertimePay = 0.0;
        $totalReimbursementRateSum = 0.0;

        foreach ($dataRaw as $rows) {
            foreach ($rows as $row) {
                $totalRegularHours += (float) ($row['regular_hours'] ?? 0);
                $totalOvertimeHours += (float) ($row['overtime_hours'] ?? 0);
                $totalRegularPay += (float) ($row['regular_pay'] ?? 0);
                $totalOvertimePay += (float) ($row['overtime_pay'] ?? 0);
                $totalReimbursementRateSum += (float) ($row['total_reimbursement'] ?? 0);
            }
        }

        $totalAllHours = $totalRegularHours + $totalOvertimeHours;
        $totalAllPay = $totalRegularPay + $totalOvertimePay;

        $regularBlendedRate = $totalRegularHours > 0 ? $totalRegularPay / $totalRegularHours : 0.0;
        $otBlendedRate = $totalOvertimeHours > 0 ? $totalOvertimePay / $totalOvertimeHours : 0.0;
        $allBlendedRate = $totalAllHours > 0 ? $totalAllPay / $totalAllHours : 0.0;
        $totalReimbursementRate = $totalAllHours > 0 ? $totalReimbursementRateSum / $totalAllHours : 0.0;
        $finalInsurance = $this->calculateInsurancePerNurse($dataRaw);
        $finalPaid = $totalAllPay + ($totalAllPay * $this->getAdminPctForState()) + $finalInsurance;

        return [
            'total_regular_hours' => $totalRegularHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_all_hours' => $totalAllHours,
            'total_regular_pay' => $totalRegularPay,
            'total_overtime_pay' => $totalOvertimePay,
            'total_all_pay' => $totalAllPay,
            'final_paid' => $finalPaid,
            'final_insurance' => $finalInsurance,
            'total_reimbursement_rate' => $totalReimbursementRate,
            'regular_blended_rate' => $regularBlendedRate,
            'ot_blended_rate' => $otBlendedRate,
            'all_blended_rate' => $allBlendedRate,
        ];
    }

    private function aggregateWholeNurseTotals(array $dataRaw) : array
    {
        $flatRows = [];
        $totalInputHours = 0.0;
        $regularRate = 0.0;
        $totalReimbursementSum = 0;

        foreach ($dataRaw as $rows) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $flatRows[] = $row;
                }
            }
        }

        foreach ($flatRows as $row) {
            $h = ($row['hours'] ?? 0) + ($row['prevHours'] ?? 0);
            $totalInputHours += $h;
            $regularRate += ($h * ($row['desiredRate'] ?? 0));
            $totalReimbursementSum += (float) ($row['total_reimbursement'] ?? 0);
        }

        $totalRegularHours = min($totalInputHours, 40);
        $totalAllHours = $totalInputHours;
        $totalOvertimeHours = $totalInputHours > 40 ? $totalInputHours - 40 : 0;
        $regularBlendedRate = $totalInputHours > 0 ? round($regularRate / $totalInputHours, 2) : 0;
        $totalRegularPay = round($regularBlendedRate * $totalRegularHours, 2);
        $otBlendedRate = round($regularBlendedRate * $this->otHoursRateMultiplier, 2);
        $totalOvertimePay = round($otBlendedRate * $totalOvertimeHours, 2);
        $allBlendedRate = $totalInputHours > 0 ? round(($totalRegularPay + $totalOvertimePay) / $totalInputHours, 2) : 0;
        $totalAllPay = $totalRegularPay + $totalOvertimePay;

        $finalInsurance = $totalAllHours >= 30 ? $this->getInsuranceForState() : 0.0;
        $finalPaid = $totalAllPay + ($totalAllPay * $this->getAdminPctForState()) + $finalInsurance;

        return [
            'total_regular_hours' => $totalRegularHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_all_hours' => $totalAllHours,
            'total_regular_pay' => $totalRegularPay,
            'total_overtime_pay' => $totalOvertimePay,
            'total_all_pay' => $totalAllPay,
            'final_paid' => $finalPaid,
            'final_insurance' => $finalInsurance,
            'total_reimbursement_rate' => $totalReimbursementSum / $totalAllHours,
            'regular_blended_rate' => $regularBlendedRate,
            'ot_blended_rate' => $totalAllHours > 40 ? $otBlendedRate : 0,
            'all_blended_rate' => $allBlendedRate,
            'total_nurses_calculated' => count($flatRows),
        ];
    }

    private function buildFinalSummary(array $totals): array
    {
        $totalAllHours = (float) $totals['total_all_hours'];
        $finalInsurance = (float) $totals['final_insurance'];
        $finalReimbursementRate = (float) $totals['total_reimbursement_rate'];
        $finalPaid = $totals['final_paid'] ?? 0;
        $finalReimbursement = $finalReimbursementRate * $totalAllHours;
        $finalProfitability = $finalReimbursement - $finalPaid;

        $finalGp = ($finalReimbursement > 0)
            ? number_format(($finalProfitability / $finalReimbursement) * 100, 2)
            : 0;

        return [
            'final_reimbursement_rate' => $finalReimbursementRate,
            'final_reimbursement' => $finalReimbursement,
            'final_paid' => $finalPaid,
            'final_profitability' => $finalProfitability,
            'final_gp' => $finalGp,
            'final_insurance' => $finalInsurance,
            'final_reimbursement_raw' => $finalReimbursement,
            'final_insurance_raw' => $finalInsurance,
        ];
    }

    private function buildIdealTable(
        float $finalReimbursement,
        float $finalInsurance,
        float $totalRegularHours,
        float $totalOvertimeHours,
        float $totalAllHours
    ): array {
        $out = [];

        foreach ($this->gpFixedPercentage as $gpPercent) {
            $gpPercent = (float) $gpPercent;

            $totalProfitability = ($finalReimbursement * $gpPercent) / 100.0;
            $totalPaid = $finalReimbursement - $totalProfitability;

            $weeklyPay = ($totalPaid - $finalInsurance) / 1.1;

            if ($totalOvertimeHours > 0) {
                $calculatedHours = $totalRegularHours + ($totalOvertimeHours * $this->otHoursRateMultiplier);
                $baseRate = $calculatedHours > 0 ? ($weeklyPay / $calculatedHours) : 0.0;
            } else {
                $baseRate = $totalAllHours > 0 ? ($weeklyPay / $totalAllHours) : 0.0;
            }

            $out[] = [
                'ideal_profitability' => number_format($totalProfitability, 2),
                'ideal_paid' => number_format($totalPaid, 2),
                'ideal_base_rate' => number_format($baseRate, 2),
                'ideal_gp' => $gpPercent,
            ];
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Merge computed results back into the SAME payload shape
    // ---------------------------------------------------------------------

    /**
     * @param array<string,array<int,array<string,mixed>>> $calculatedDataRaw
     * @return array<string,array<string,mixed>> key "clientId:nurseId:index"
     */
    private function buildComputedNurseLookup(array $calculatedDataRaw): array
    {
        $out = [];

        foreach ($calculatedDataRaw as $rows) {
            foreach ($rows as $row) {
                $clientId = (string) ($row['clientId'] ?? '');
                $nurseId = (string) ($row['nurseId'] ?? $row['aoc_clinician_id'] ?? '');
                $index = (int) ($row['index'] ?? 0);

                if ($clientId === '' || $nurseId === '') {
                    continue;
                }

                $key = $clientId . ':' . $nurseId . ':' . $index;
                $out[$key] = $row;
            }
        }

        return $out;
    }

    private function mergeComputedIntoPayload(
        array $payload,
        array $calculatedDataRaw,
        array $clientData,
        array $totals,
        array $finals,
        array $idealTable,
        ?array $grossMarginGuide
    ): array {
        $lookup = $this->buildComputedNurseLookup($calculatedDataRaw);

        /*
         * We support two payload shapes:
         *  A) { status, data: { clients, nurses, links, data: { ...computed } } }
         *  B) { clients, nurses, links, data: { ...computed } }  (service called with inner `data`)
         *
         * Pick the container that actually holds nurses/clients/links.
         */
        if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['nurses'])) {
            $container = &$payload['data'];
        } else {
            $container = &$payload;
        }

        // Ensure computed sub-bucket exists (keeps your current "data.data.*" behavior when container is inner data)
        if (!isset($container['data']) || !is_array($container['data'])) {
            $container['data'] = [];
        }

        // Computed display + totals
        $container['data']['client_data'] = $clientData;
        if ($grossMarginGuide !== null) {
            $container['data']['gross_margin_guide'] = $grossMarginGuide;
        }

        // Map computed per-nurse fields back into the nurses array
        $nurses = $container['nurses'] ?? [];
        if (is_array($nurses)) {
            foreach ($nurses as $i => $nurse) {
                if (!is_array($nurse)) {
                    continue;
                }

                $clientId = (string) ($nurse['clientId'] ?? '');
                $nurseId = (string) ($nurse['nurseId'] ?? $nurse['aoc_clinician_id'] ?? '');
                $index = (int) ($nurse['index'] ?? 0);

                $key = $clientId . ':' . $nurseId . ':' . $index;

                if (!isset($lookup[$key])) {
                    continue;
                }

                $computed = $lookup[$key];
                $totalGp = $computed['total_gp'] ?? 0;

                $container['nurses'][$i] = array_merge($nurse, [
                    'regular_hours' => $computed['regular_hours'] ?? 0,
                    'overtime_hours' => $computed['overtime_hours'] ?? 0,
                    'regular_pay' => $computed['regular_pay'] ?? 0,
                    'overtime_pay' => $computed['overtime_pay'] ?? 0,
                    'reimbursement_rate' => $computed['reimbursement_rate'] ?? 0,
                    'insurance' => $computed['insurance'] ?? 0,
                    'total_paid' => $computed['total_paid'] ?? 0,
                    'total_reimbursement' => $computed['total_reimbursement'] ?? 0,
                    'total_profitability' => $computed['total_profitability'] ?? 0,

                    // Important: ensure both fields are updated for backward compatibility with the UI
                    'total_gp' => $totalGp,
                    'gp' => is_numeric($totalGp) ? number_format((float) $totalGp, 2, '.', '') : (string) $totalGp,
                ]);
            }
        }

        $container['data']['totals'] = [
            'regular_final_hours' => $totals['total_regular_hours'],
            'overtime_final_hours' => max($totals['total_overtime_hours'], 0),
            'all_final_hours' => $totals['total_all_hours'],

            'regular_final_pay' => number_format($totals['total_regular_pay'], 2),
            'overtime_final_pay' => number_format($totals['total_overtime_pay'], 2),
            'all_final_pay' => number_format($totals['total_all_pay'], 2),

            'regular_blended_rate' => number_format($totals['regular_blended_rate'], 2),
            'overtime_blended_rate' => number_format($totals['ot_blended_rate'], 2),
            'all_blended_rate' => number_format($totals['all_blended_rate'], 2),

            'final_reimbursement_rate' => number_format($finals['final_reimbursement_rate'], 2),
            'final_reimbursement' => number_format($finals['final_reimbursement'], 2),
            'final_paid' => number_format($finals['final_paid'], 2),
            'final_profitability' => number_format($finals['final_profitability'], 2),
            'final_gp' => $finals['final_gp'],
            'final_insurance' => $finals['final_insurance'],
            'final_weekly_pay_pre_tax' => number_format($totals['total_all_pay'], 2),
        ];

        $container['data']['ideal_table'] = $idealTable;

        return $payload;
    }

    private function shouldAddEscalationGuide(): bool
    {
        return LD::checkFeatureFlag(self::ESCALATION_GUIDE_FEATURE_FLAG);
    }
}
