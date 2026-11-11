<?php

namespace App\Console\Commands;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Helpers\H;
use App\Models\Adjustment;
use App\Models\ClaimAdjustmentHistory;
use App\Models\ClaimsMaster;
use App\Services\Adjustment\AdjustmentWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateClaimCategories extends Command
{
    protected $signature = 'migrate:categories {--chunk=5000}';
    protected $description = 'Migrate categories';

    public function __construct(
        private readonly AdjustmentWorkflowService $workflowService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        $mappedData = [
            'W/O Compliance' => 'Compliance',
            'W/O No Eligibility' => 'Eligibility Issue',
            'W/O No Auth SOC' => 'No Auth SOC',
            'W/O No Authorization' => 'No Authorization',
            'W/O Patient Bad Debt' => 'Patient Bad Debt',
            'W/O Timely Appeal' => 'Timely Follow Up – RCM',
            'W/O EVV Did Not Validate' => 'Timely Follow Up – RCM',
            'W/O Timely Filing' => 'Timely Follow Up – RCM',
        ];

        $adjustmentsByCategory = Adjustment::query()
            ->get(['id', 'category'])
            ->keyBy('category');

        $adjustmentsById = Adjustment::query()
            ->get(['id', 'category'])
            ->keyBy('id');

        $missingCategoryNames = [];
        foreach (array_unique(array_values($mappedData)) as $categoryName) {
            if (!isset($adjustmentsByCategory[$categoryName])) {
                $missingCategoryNames[] = $categoryName;
            }
        }

        if (!empty($missingCategoryNames)) {
            $this->error('These category names were not found in adjustments:');
            foreach ($missingCategoryNames as $name) {
                $this->line('- ' . $name);
            }

            return self::FAILURE;
        }

        $this->info('Starting update...');

        $totalProcessed = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalNotified = 0;

        DB::table('claims_master')
            ->select('id', 'category', 'adjustment_category_id', 'invoice_number')
            ->whereIn('category', array_keys($mappedData))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
                $mappedData,
                $adjustmentsByCategory,
                $adjustmentsById,
                &$totalProcessed,
                &$totalUpdated,
                &$totalSkipped,
                &$totalNotified
            ) {
                foreach ($rows as $row) {
                    $totalProcessed++;

                    $claimId = (int) $row->id;

                    try {
                        DB::transaction(function () use (
                            $row,
                            $mappedData,
                            $adjustmentsByCategory,
                            $adjustmentsById,
                            &$totalUpdated,
                            &$totalNotified
                        ) {
                            /** @var ClaimsMaster $claim */
                            $claim = ClaimsMaster::query()->findOrFail((int) $row->id);

                            $adjustmentId = null;
                            $categoryName = null;

                            if (!empty($row->adjustment_category_id)) {
                                $adjustmentId = (int) $row->adjustment_category_id;
                                $categoryName = $adjustmentsById[$adjustmentId]->category ?? null;
                            } else {
                                $newCategoryName = $mappedData[$row->category] ?? null;

                                if (!$newCategoryName) {
                                    throw new \RuntimeException(
                                        "No mapped category found for legacy category [{$row->category}]"
                                    );
                                }

                                /** @var Adjustment|null $adjustment */
                                $adjustment = $adjustmentsByCategory[$newCategoryName] ?? null;

                                if (!$adjustment) {
                                    throw new \RuntimeException(
                                        "Adjustment not found for mapped category [{$newCategoryName}]"
                                    );
                                }

                                $adjustmentId = (int) $adjustment->id;
                                $categoryName = $adjustment->category;

                                ClaimsMaster::query()
                                    ->whereKey($claim->id)
                                    ->update([
                                        'adjustment_category_id' => $adjustmentId,
                                    ]);
                            }

                            $existingHistory = ClaimAdjustmentHistory::query()
                                ->where('claim_master_id', $claim->id)
                                ->exists();

                            if ($existingHistory) {
                                return;
                            }

                            $historyStep = $this->workflowService->resolveStartHistoryStep($claim, $adjustmentId);

                            $this->workflowService->adjustmentHistoryService->create(
                                claimId: $claim->id,
                                assigneeType: $historyStep['assignee_type'],
                                assigneeId: (int) $historyStep['assignee_id'],
                                stage: $historyStep['stage'],
                                eventType: AdjustmentEventTypeEnum::ASSIGNED,
                                actedByUserId: null,
                            );

                            $this->workflowService->notificationService->notifyNewAssignee($claim, $historyStep);

                            $this->addMigrationActivityNote(
                                claimId: $claim->id,
                                invoiceNumber: (string) $claim->invoice_number,
                                categoryName: (string) $categoryName,
                                stageLabel: $historyStep['stage']->label(),
                            );

                            $totalUpdated++;
                            $totalNotified++;
                        });
                    } catch (Throwable $e) {
                        $totalSkipped++;

                        Log::error($e->getMessage());

                        $this->error("Claim {$claimId} failed: {$e->getMessage()}");
                        H::ExcelLog(
                            'migrate:categories failed for claim ' . $claimId . ' => ' . $e->getMessage(),
                            'migrateClaimCategories.log'
                        );
                    }
                }

                $this->info(
                    "Processed: {$totalProcessed}, Updated: {$totalUpdated}, " .
                    "Notified: {$totalNotified}, Skipped: {$totalSkipped}"
                );
            });

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function addMigrationActivityNote(
        int $claimId,
        string $invoiceNumber,
        string $categoryName,
        string $stageLabel
    ): void {

        $now = now();

        DB::table('notes')->insert([
            'entity' => 'invoice',
            'entity_id' => $claimId,
            'notes' => 'Adjustment workflow was initialized during migration for invoice '
                . $invoiceNumber
                . '. Category: '
                . $categoryName
                . '. Assigned to: '
                . $stageLabel,
            'created_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
