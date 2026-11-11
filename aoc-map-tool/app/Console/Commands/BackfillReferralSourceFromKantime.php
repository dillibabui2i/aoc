<?php

namespace App\Console\Commands;

use App\Kantime\Repository\BigQuery\ClientMasterRepository;
use App\Kantime\Repository\MariaDB\AocIntakeRepository;
use App\Kantime\Repository\MariaDB\AocPatientsRepository;
use Illuminate\Console\Command;
use Throwable;

/**
 * PD-4538 | One-off backfill.
 *
 * The BigQuery client sync used to map CLIENT_REFERRAL_SOURCETYPE (a 0-3 category code)
 * into ReferralSourceID instead of CLIENT_REFERRAL_FACILITY_ID, so mssql_patients.ReferralSourceID
 * and intake.ReferralSourceID never resolved against referralsource_master. The incremental sync
 * cursor will not revisit old rows, so this command re-reads the facility id for every valid
 * KanTime client and writes it to both tables using the same patient lookup as the sync.
 */
class BackfillReferralSourceFromKantime extends Command
{
    protected $signature = 'kantime:backfill-referral-source
                            {--dry-run : Only report what would change, write nothing}
                            {--limit=0 : Process at most N BigQuery rows (0 = all)}';

    protected $description = 'PD-4538: backfill ReferralSourceID in mssql_patients and intake from KanTime CLIENT_REFERRAL_FACILITY_ID';

    public function handle(
        ClientMasterRepository $repository,
        AocPatientsRepository $patientsRepository,
        AocIntakeRepository $intakeRepository
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $stats = [
            'rows' => 0,
            'patient_not_found' => 0,
            'patients_updated' => 0,
            'intakes_updated' => 0,
            'unchanged' => 0,
            'errors' => 0,
        ];

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Backfilling ReferralSourceID from KanTime...');

        foreach ($repository->getReferralFacilityRows($limit) as $row) {
            $stats['rows']++;

            try {
                $patient = $this->findPatient($patientsRepository, $row);

                if ($patient === null) {
                    $stats['patient_not_found']++;
                    continue;
                }

                $newValue = $this->normalizeId($row['ReferralSourceID'] ?? null);
                $intake = $intakeRepository->findByPatientAndLob((int) $patient->id, $row['LOBID'] ?? null);

                $patientChanged = $this->normalizeId($patient->ReferralSourceID ?? null) !== $newValue;
                $intakeChanged = $intake !== null && $this->normalizeId($intake->ReferralSourceID ?? null) !== $newValue;

                if (!$patientChanged && !$intakeChanged) {
                    $stats['unchanged']++;
                    continue;
                }

                if ($patientChanged) {
                    $stats['patients_updated']++;
                    $dryRun || $patientsRepository->update((int) $patient->id, [
                        'ReferralSourceID' => $newValue,
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                    ]);
                }

                if ($intakeChanged) {
                    $stats['intakes_updated']++;
                    $dryRun || $intakeRepository->update((int) $intake->id, [
                        'ReferralSourceID' => $newValue,
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                    ]);
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                logger()->error('PD-4538 referral source backfill failed for row', [
                    'row' => $row,
                    'message' => $e->getMessage(),
                ]);
                $this->error(sprintf(
                    'Row IntakeID=%s seq=%s failed: %s',
                    $row['IntakeID'] ?? '?',
                    $row['CLIENT_INTAKE_SEQUENCE_NO'] ?? '?',
                    $e->getMessage()
                ));
            }

            if ($stats['rows'] % 5000 === 0) {
                $this->line(sprintf('  processed %d rows...', $stats['rows']));
            }
        }

        $this->table(array_keys($stats), [array_values($stats)]);

        return $stats['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Mirrors ClientMasterSyncService::findExistingRecord (without the feature-flagged
     * Medicaid merge, which only matters for not_in_kantime placeholders).
     */
    private function findPatient(AocPatientsRepository $patientsRepository, array $row): ?object
    {
        $intakeId = (int) ($row['IntakeID'] ?? 0);
        $sequenceNo = (int) ($row['CLIENT_INTAKE_SEQUENCE_NO'] ?? 1);
        $patientAccount = trim((string) ($row['PatientAccount'] ?? ''));

        if ($intakeId > 0 && $sequenceNo > 0) {
            $record = $patientsRepository->findByIntakeIdAndSequence($intakeId, $sequenceNo);
            if ($record) {
                return $record;
            }
        }

        if ($intakeId > 0) {
            $record = $patientsRepository->findByIntakeIdWithoutSequence($intakeId);
            if ($record) {
                return $record;
            }
        }

        if ($patientAccount !== '') {
            return $patientsRepository->findByPatientAccountNoIntake($patientAccount);
        }

        return null;
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
