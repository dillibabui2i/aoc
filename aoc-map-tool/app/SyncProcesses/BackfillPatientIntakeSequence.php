<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;

/**
 * @temporary migration class
 */
class BackfillPatientIntakeSequence
{
    protected string $syncType = 'backfill_patient_intake_sequence';
    protected string $logFile = 'backfillPatientIntakeSequence.log';
    protected string $tableName = 'mssql_patients';

    public function handle(int $patientAccount = 0): void
    {
        echo "Backfill CLIENT_INTAKE_SEQUENCE_NO started\n";

        $limit = 1000;
        $cursorKey = '';
        $processed = 0;
        $updated = 0;
        $notFound = 0;

        do {
            $query = $this->buildSnowFlakeQuery($limit, $cursorKey, $patientAccount);
            echo $query . "\n";

            $rawData = DB::connection('daas')->select(DB::raw($query));
            $rows = json_decode(json_encode($rawData), true);

            $count = count($rows);
            echo "Fetched rows: {$count}\n";

            if ($count === 0) {
                break;
            }

            foreach ($rows as $row) {
                $processed++;

                $result = $this->processRow($row);

                if ($result === 'updated') {
                    $updated++;
                } elseif ($result === 'not_found') {
                    $notFound++;
                }
            }

            $lastRow = end($rows);
            $cursorKey = $lastRow['CURSORKEY'] ?? '';

            if (!empty($cursorKey)) {
                H::getHelper('Sync')->update_last_updated(
                    $this->syncType,
                    $lastRow['LASTMODIFIED'] ?? '',
                    $cursorKey
                );
            }
        } while (!empty($rows) && !empty($cursorKey));

        echo "Backfill finished\n";
        echo "Processed: {$processed}\n";
        echo "Updated: {$updated}\n";
        echo "Not found: {$notFound}\n";

        H::getHelper('Sync')->stopBatch($this->syncType);
    }

    protected function processRow(array $row): string
    {
        $patientAccount = (string)($row['PATIENTACCOUNT'] ?? '');
        $intakeId = (int)($row['INTAKEID'] ?? 0);
        $patientId = (int)($row['CLIENTID'] ?? 0);
        $sequenceNo = (int)($row['CLIENT_INTAKE_SEQUENCE_NO'] ?? 1);
        $isIntake = (int)($row['IS_INTAKE'] ?? 0);

        if ($sequenceNo <= 0) {
            H::ExcelLog(
                "Skip row: empty CLIENT_INTAKE_SEQUENCE_NO. IntakeID={$intakeId}, PatientID={$patientId}, PatientAccount={$patientAccount}",
                $this->logFile
            );
            return 'skip';
        }

        $record = $this->findExistingRecord($patientAccount, $intakeId, $patientId, $isIntake);

        if (empty($record)) {
            H::ExcelLog(
                "Not found. IntakeID={$intakeId}, PatientID={$patientId}, PatientAccount={$patientAccount}, Seq={$sequenceNo}",
                $this->logFile
            );
            return 'not_found';
        }

        DB::table($this->tableName)
            ->where('id', $record->id)
            ->update([
                'CLIENT_INTAKE_SEQUENCE_NO' => $sequenceNo,
                'updated_at' => now(),
                'is_updated' => 1,
            ]);

        H::ExcelLog(
            "Updated mssql_patients.id={$record->id} with CLIENT_INTAKE_SEQUENCE_NO={$sequenceNo}",
            $this->logFile
        );

        return 'updated';
    }

    protected function findExistingRecord(string $patientAccount, int $intakeId, int $patientId, int $isIntake): ?object
    {
        if ($intakeId > 0) {
            $record = DB::table($this->tableName)
                ->where('IntakeID', $intakeId)
                ->orderByDesc('id')
                ->first();

            if (!empty($record)) {
                return $record;
            }
        }

        if ($patientId > 0) {
            $query = DB::table($this->tableName)
                ->where('PatientID', $patientId);

            if ($isIntake === 1) {
                $query->where('is_intake', 1);
            }

            $record = $query->orderByDesc('id')->first();

            if (!empty($record)) {
                return $record;
            }
        }

        if ($patientAccount !== '') {
            $query = DB::table($this->tableName)
                ->where('PatientAccount', $patientAccount);

            if ($isIntake === 1) {
                $query->where('is_intake', 1);
            }

            $record = $query
                ->orderByDesc('is_intake')
                ->orderByDesc('id')
                ->first();

            if (!empty($record)) {
                return $record;
            }
        }

        return null;
    }

    protected function buildSnowFlakeQuery(int $limit, string $cursorKey, int $patientAccount = 0): string
    {
        $where = ["1=1"];

        if ($patientAccount !== 0) {
            $where[] = "CLIENT_PATIENT_ID = '" . addslashes((string)$patientAccount) . "'";
        }

        if ($cursorKey !== '') {
            $where[] = "CONCAT(
                TO_VARCHAR(CLIENTMASTER_SVW.ETL_UPDATED_TIMESTAMP, 'YYYY-MM-DD HH24:MI:SS.FF9'),
                '|',
                LPAD(COALESCE(TO_VARCHAR(CLIENT_INTAKE_ID), '0'), 20, '0'),
                '|',
                LPAD(COALESCE(TO_VARCHAR(CLIENT_ID), '0'), 20, '0'),
                '|',
                LPAD(COALESCE(TO_VARCHAR(CLIENT_INTAKE_SEQUENCE_NO), '1'), 10, '0')
            ) > '" . addslashes($cursorKey) . "'";
        }

        $whereSql = implode("\n AND ", $where);

        return "
            SELECT
                TO_VARCHAR(CLIENTMASTER_SVW.ETL_UPDATED_TIMESTAMP, 'YYYY-MM-DD HH24:MI:SS.FF9') AS LastModified,
                CONCAT(
                    TO_VARCHAR(CLIENTMASTER_SVW.ETL_UPDATED_TIMESTAMP, 'YYYY-MM-DD HH24:MI:SS.FF9'),
                    '|',
                    LPAD(COALESCE(TO_VARCHAR(CLIENT_INTAKE_ID), '0'), 20, '0'),
                    '|',
                    LPAD(COALESCE(TO_VARCHAR(CLIENT_ID), '0'), 20, '0'),
                    '|',
                    LPAD(COALESCE(TO_VARCHAR(CLIENT_INTAKE_SEQUENCE_NO), '1'), 10, '0')
                ) AS CursorKey,

                CAST(CLIENT_PATIENT_ID AS VARCHAR(100)) AS PatientAccount,
                CLIENT_ID AS ClientID,
                CLIENT_INTAKE_ID AS IntakeID,
                CLIENT_IS_INTAKE AS IS_INTAKE,
                CLIENT_INTAKE_SEQUENCE_NO,
                CLIENTMASTER_SVW.ISVALIDRECORD

            FROM CLIENTMASTER_SVW
            WHERE {$whereSql}
            ORDER BY CursorKey ASC
            LIMIT {$limit}
        ";
    }
}
