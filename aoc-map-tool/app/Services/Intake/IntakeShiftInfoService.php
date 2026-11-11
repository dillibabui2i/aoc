<?php

namespace App\Services\Intake;

use App\Enums\Intake\AdmissionServiceNameEnum;
use App\Enums\LOB;
use App\Enums\MssqlScheduleMasterStatus;
use Illuminate\Support\Facades\DB;

/**
 * Calculates intake.intake_admission_visit, intake.intake_first_billable_visit and intake.intake_completed
 * from mssql_schedulemaster. Relies on service_codes_daas.is_admissions / is_respite (see App\Models\ServiceCodeDaas).
 *
 * Clients are split into three segments; each has its own admission visit rule and first billable visit rule:
 *  - regular:  non Colorado patient, intake LOB is not a respite LOB
 *  - respite:  non Colorado patient, intake LOB is a respite LOB
 *  - colorado: Colorado patient
 */
class IntakeShiftInfoService
{
    private const FIRST_BILLABLE_MIN_DATE = '2024-01-01';
    private const NO_ADMISSION_DATE_FALLBACK = '2001-01-01';

    /**
     * Runs every step in order (admission visit -> first billable visit -> completed).
     * The caller is responsible for the transaction.
     *
     * @param int[]|null $intakeIds limit to these intake rows, null = all rows
     * @param bool $onlyNotCompleted skip rows with intake_completed <> 0 (the scheduled job behaviour)
     * @return array<string, int> step label => affected rows
     */
    public function recalculate(?array $intakeIds = null, bool $onlyNotCompleted = true): array
    {
        $result = [];

        foreach ($this->segments() as $segment => $rules) {
            $result["admission visit ({$segment})"] = $this->updateAdmissionVisit($rules, $intakeIds, $onlyNotCompleted);
        }

        foreach ($this->segments() as $segment => $rules) {
            $result["first billable visit ({$segment})"] = $this->updateFirstBillableVisit($rules, $intakeIds, $onlyNotCompleted);
        }

        $result['intake completed'] = $this->updateCompleted($intakeIds);

        return $result;
    }

    /**
     * @return array<string, array{where: string, admission: string, admission_order: string, admission_null_is_zero: bool, billable: string}>
     */
    private function segments(): array
    {
        $colorado = LOB::COLORADO->value;
        $respiteLobs = implode(', ', [LOB::RESPITE_NURSING->value, LOB::RESPITE_ATTENDANT->value]);
        $rnAdmission = AdmissionServiceNameEnum::RN_ADMISSION->value;

        return [
            'regular' => [
                'where' => "mp.LOBID <> {$colorado} AND intake.LOBID NOT IN ({$respiteLobs})",
                // RN admission wins, otherwise the earliest admission visit since the day before the intake date
                'admission' => 'sm.ScheduleDate >= DATE(intake.IntakeDate) - INTERVAL 1 DAY',
                'admission_order' => "CASE WHEN sc.name = '{$rnAdmission}' THEN 0 ELSE 1 END ASC, sm.ScheduleDate ASC",
                'admission_null_is_zero' => true,
                'billable' => 'sc.is_respite = 0',
            ],
            'respite' => [
                'where' => "mp.LOBID <> {$colorado} AND intake.LOBID IN ({$respiteLobs})",
                // the latest admission visit on the admission visit date set by the IC
                'admission' => 'sm.ScheduleDate = DATE(intake.admission_visit_date)',
                'admission_order' => 'sm.CGTaskID DESC',
                'admission_null_is_zero' => true,
                'billable' => 'sc.is_respite = 1',
            ],
            'colorado' => [
                'where' => "mp.LOBID = {$colorado}",
                // the latest admission visit of the intake LOB since the referral date
                'admission' => 'sc.LOBID = intake.LOBID AND sm.ScheduleDate >= DATE(intake.ReferralDate)',
                'admission_order' => 'sm.CGTaskID DESC',
                // NULL is overwritten with 0 when there is no admission visit
                'admission_null_is_zero' => false,
                'billable' => 'sc.LOBID = intake.LOBID',
            ],
        ];
    }

    private function updateAdmissionVisit(array $rules, ?array $intakeIds, bool $onlyNotCompleted): int
    {
        [$scopeSql, $bindings] = $this->scope($intakeIds, $onlyNotCompleted);
        $changed = $rules['admission_null_is_zero']
            ? 'IFNULL(intake.intake_admission_visit, 0) <> sub.sm_admission_visit'
            : 'NOT (intake.intake_admission_visit <=> sub.sm_admission_visit)';
        return DB::update(
            "UPDATE intake
            JOIN (
                SELECT
                    intake.id AS intake_id,
                    IFNULL((
                        SELECT sm.CGTaskID
                        FROM mssql_schedulemaster sm
                        JOIN service_codes_daas sc ON sm.ServiceCodeID = sc.service_id
                        WHERE sm.PatientID = mp.PatientID
                          AND sc.is_admissions = 1
                          AND {$rules['admission']}
                        ORDER BY {$rules['admission_order']}
                        LIMIT 1
                    ), 0) AS sm_admission_visit
                FROM intake
                JOIN mssql_patients mp ON intake.aoc_patient_id = mp.id
                WHERE {$rules['where']}
                  AND IFNULL(mp.PatientID, 0) <> 0
                  {$scopeSql}
            ) AS sub ON intake.id = sub.intake_id
            SET intake.intake_admission_visit = sub.sm_admission_visit
            WHERE {$changed}",
            $bindings
        );
    }

    private function updateFirstBillableVisit(array $rules, ?array $intakeIds, bool $onlyNotCompleted): int
    {
        [$scopeSql, $bindings] = $this->scope($intakeIds, $onlyNotCompleted);
        $statuses = $this->statusList([
            MssqlScheduleMasterStatus::APPROVED,
            MssqlScheduleMasterStatus::COMPLETED,
            MssqlScheduleMasterStatus::IN_PROGRESS,
            MssqlScheduleMasterStatus::PLANNED,
        ]);
        $minDate = self::FIRST_BILLABLE_MIN_DATE;
        $fallbackDate = self::NO_ADMISSION_DATE_FALLBACK;

        // the earliest billable visit on or after the admission visit
        return DB::update(
            "UPDATE intake
            JOIN mssql_patients mp ON intake.aoc_patient_id = mp.id
            LEFT JOIN mssql_schedulemaster sm ON intake.intake_admission_visit = sm.CGTaskID
            SET intake.intake_first_billable_visit = IFNULL((
                SELECT sm2.CGTaskID
                FROM mssql_schedulemaster sm2
                JOIN service_codes_daas sc ON sm2.ServiceCodeID = sc.service_id
                WHERE sm2.PatientID = mp.PatientID
                  AND sm2.ScheduleStatusID IN ({$statuses})
                  AND sm2.IsBillable = 1
                  AND {$rules['billable']}
                  AND sm2.ScheduleDate > '{$minDate}'
                  AND sm2.ScheduleDate >= IFNULL(sm.ScheduleDate, '{$fallbackDate}')
                ORDER BY sm2.ScheduleDate ASC
                LIMIT 1
            ), 0)
            WHERE {$rules['where']}
              AND intake.intake_admission_visit <> 0
              {$scopeSql}",
            $bindings
        );
    }

    private function updateCompleted(?array $intakeIds): int
    {
        [$scopeSql, $bindings] = $this->scope($intakeIds, false);
        $statuses = $this->statusList([
            MssqlScheduleMasterStatus::APPROVED,
            MssqlScheduleMasterStatus::COMPLETED,
            MssqlScheduleMasterStatus::IN_PROGRESS,
        ]);

        return DB::update(
            "UPDATE intake
            JOIN mssql_schedulemaster sm ON intake.intake_first_billable_visit = sm.CGTaskID
            SET intake.intake_completed = 1
            WHERE COALESCE(intake.intake_completed, 0) = 0
              AND sm.ScheduleStatusID IN ({$statuses})
              {$scopeSql}",
            $bindings
        );
    }

    /**
     * @return array{0: string, 1: array} extra "AND ..." conditions on the `intake` alias and their bindings
     */
    private function scope(?array $intakeIds, bool $onlyNotCompleted): array
    {
        $sql = $onlyNotCompleted ? 'AND intake.intake_completed = 0' : '';
        $bindings = [];

        if ($intakeIds !== null) {
            $intakeIds = array_values(array_map('intval', $intakeIds));
            // empty scope must match nothing, not everything
            $sql .= $intakeIds === []
                ? ' AND 1 = 0'
                : ' AND intake.id IN (' . implode(', ', array_fill(0, count($intakeIds), '?')) . ')';
            $bindings = $intakeIds;
        }

        return [$sql, $bindings];
    }

    /**
     * @param MssqlScheduleMasterStatus[] $statuses
     */
    private function statusList(array $statuses): string
    {
        return implode(', ', array_map(fn (MssqlScheduleMasterStatus $status) => $status->value, $statuses));
    }
}
