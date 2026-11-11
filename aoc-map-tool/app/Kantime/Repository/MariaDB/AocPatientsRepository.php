<?php

namespace App\Kantime\Repository\MariaDB;

use App\Enums\PayerEnum;
use App\Enums\PayerGroupEnum;
use Illuminate\Support\Facades\DB;

final class AocPatientsRepository
{
    private const TABLE_NAME = 'mssql_patients';

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function findByIntakeIdAndSequence(int $intakeId, int $sequenceNo): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('IntakeID', $intakeId)
            ->where('CLIENT_INTAKE_SEQUENCE_NO', $sequenceNo)
            ->orderByDesc('id')
            ->first();
    }

    public function findByIntakeIdWithoutSequence(int $intakeId): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('IntakeID', $intakeId)
            ->whereNull('CLIENT_INTAKE_SEQUENCE_NO')
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function findByPatientAccountNoIntake(string $patientAccount): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('PatientAccount', $patientAccount)
            ->where(DB::raw('ifnull(IntakeID, 0)'), 0)
            ->where(DB::raw('ifnull(PatientID, 0)'), 0)
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function findByMedicaidNumber(string $medicaidNumber): ?object
    {
        return DB::table(self::TABLE_NAME)
            ->where('MedicaidNumber', $medicaidNumber)
            ->where('not_in_kantime', 1)
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function insert(array $row): int
    {
        return (int) DB::table(self::TABLE_NAME)->insertGetId($row);
    }

    public function update(int $id, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('id', $id)
            ->update($row);
    }

    public function softDelete(int $id): void
    {
        DB::table(self::TABLE_NAME)
            ->where('id', $id)
            ->update([
                'is_deleted' => 1,
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'is_updated' => 1,
            ]);
    }

    public function recalculateLatestAdmits(): void
    {
        DB::statement(
            "UPDATE mssql_patients mp
            JOIN (
                SELECT p.PatientAccount,
                       (SELECT p2.id FROM mssql_patients p2
                        WHERE p2.PatientAccount = p.PatientAccount
                          AND p2.is_deleted = 0
                          AND p2.not_in_kantime <> 1
                        ORDER BY
                            CASE
                                WHEN p2.Patient_CurrentStatusID IN (1,6,7,8,10) THEN 1
                                WHEN p2.Patient_CurrentStatusID = 4 AND p2.IsNonAdmit = 0 THEN 2
                                WHEN p2.Patient_CurrentStatusID = 7 THEN 3
                                ELSE 4
                            END ASC,
                            p2.is_intake DESC,
                            p2.IsNonAdmit ASC,
                            CASE WHEN p2.IntakeID IS NULL THEN 1 ELSE 0 END ASC,
                            p2.IntakeID DESC,
                            p2.CLIENT_INTAKE_SEQUENCE_NO DESC
                        LIMIT 1) as winner_id
                FROM mssql_patients p
                WHERE p.is_deleted = 0
                  AND p.PatientAccount <> ''
                  AND p.not_in_kantime <> 1
                GROUP BY p.PatientAccount
            ) winners ON mp.PatientAccount = winners.PatientAccount
            SET mp.IsLatestAdmit = CASE WHEN mp.id = winners.winner_id THEN 1 ELSE 0 END
            WHERE mp.is_deleted = 0
              AND mp.PatientAccount <> ''
              AND mp.not_in_kantime <> 1"
        );
    }

    public function updatePayerFromClientPaymentSource(): void
    {
        DB::statement(
            "UPDATE mssql_patients
            SET Payer =
            (
                SELECT p.name
                FROM client_payment_source cps
                JOIN payment_source p
                    ON p.payersource_id =
                        CASE
                            WHEN IFNULL(cps.additional_payer_id, 0) = 0 THEN cps.primary_payer_id
                            ELSE cps.additional_payer_id
                        END
                WHERE cps.intake_id = mssql_patients.IntakeID
                  AND (start_date <= CURRENT_DATE OR IFNULL(start_date, '0000-00-00 00:00:00') = '0000-00-00 00:00:00')
                  AND (end_date >= CURRENT_DATE OR IFNULL(end_date, '0000-00-00 00:00:00') = '0000-00-00 00:00:00')
                  AND p.name NOT LIKE '%RESPITE%'
                ORDER BY cps.PayerType DESC, cps.start_date DESC, cps.is_primary DESC, cps.id DESC
                LIMIT 1
            )
            WHERE not_in_kantime <> 1"
        );
    }

    public function updateMolinaPayerGroup(): void
    {
        DB::table(self::TABLE_NAME)
            ->whereIn('Payer', [PayerEnum::MOLINA_CMS_TITLE_XIX->value, PayerEnum::MOLINA_CMS_TITLE_XXI->value])
            ->where('not_in_kantime', '<>', 1)
            ->where(function ($query) {
                $query->whereNull('payer_group')
                    ->orWhere('payer_group', '<>', PayerGroupEnum::MOLINA_CMS->value);
            })
            ->update(['payer_group' => PayerGroupEnum::MOLINA_CMS->value]);

        // Clear the derived group for clients that left Molina (or lost their payer).
        DB::table(self::TABLE_NAME)
            ->where('payer_group', PayerGroupEnum::MOLINA_CMS->value)
            ->where(function ($query) {
                $query->whereNull('Payer')
                    ->orWhereNotIn('Payer', [
                        PayerEnum::MOLINA_CMS_TITLE_XIX->value,
                        PayerEnum::MOLINA_CMS_TITLE_XXI->value,
                    ]);
            })
            ->update(['payer_group' => null]);
    }

    public function updateRespitePayerFromClientPaymentSource(): void
    {
        DB::statement(
            "UPDATE mssql_patients
            SET RespitePayer =
            (
                SELECT p.name
                FROM client_payment_source cps
                JOIN payment_source p
                    ON p.payersource_id =
                        CASE
                            WHEN IFNULL(cps.additional_payer_id, 0) = 0 THEN cps.primary_payer_id
                            ELSE cps.additional_payer_id
                        END
                WHERE cps.intake_id = mssql_patients.IntakeID
                  AND (start_date <= CURRENT_DATE OR IFNULL(start_date, '0000-00-00 00:00:00') = '0000-00-00 00:00:00')
                  AND (end_date >= CURRENT_DATE OR IFNULL(end_date, '0000-00-00 00:00:00') = '0000-00-00 00:00:00')
                  AND p.name LIKE '%RESPITE%'
                ORDER BY cps.PayerType DESC, cps.start_date DESC, cps.is_primary DESC, cps.id DESC
                LIMIT 1
            )
            WHERE not_in_kantime <> 1"
        );
    }
}
