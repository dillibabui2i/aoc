<?php

namespace Database\Seeders;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Helpers\H;
use App\Models\Adjustment;
use App\Models\ClaimAdjustmentHistory;
use App\Models\ClaimsMaster;
use App\Services\Adjustment\AdjustmentWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChangeAdjustmentCategories extends Seeder
{

    public function __construct(
        private readonly AdjustmentWorkflowService $workflowService,
    ) {}
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mappedData = [
            'Timely Follow Up – RCM' => [
                '100279-01', '105225-02', '93611-58', '105779-16', '96556-51', '96556-52', '99944-03', '97058-01', '110924-26', '94373-22',
                '94373-23', '105204-00', '105204-01', '94373-24', '105204-06', '98065-10', '98065-11', '98065-12', '98065-13', '98065-14',
                '90430-17', '90430-19', '105460-17', '97804-49', '103824-00', '97227-54', '97227-56', '97227-60', '97227-62', '97227-63',
                '97227-64', '94549-19', '94483-23', '97391-17', '95276-46', '95276-50', '95276-51', '95276-52', '95276-59', '95276-60',
                '95276-61', '95276-63', '95276-69', '100076-17', '100076-20', '100076-21', '100076-22', '100076-28', '100076-35', '100076-37',
                '100076-38', '112795-02', '112658-20', '97382-25', '97382-25', '94225-06', '94225-08', '98312-00', '98312-01', '98312-02',
                '100148-00', '100148-01', '100148-02', '100148-03', '104296-00', '104296-01', '104296-02', '104296-03', '104296-04', '102470-04',
                '97930-19', '97930-21', '97930-24', '97930-26', '97930-32', '97930-34', '97930-36', '106971-00', '106971-12', '97930-39', '103104-04',
                '101960-02', '92537-09', '92537-09', '92537-13', '92537-13', '92872-12', '92391-30', '105564-12', '115364-00', '115364-00', '98612-19',
                '100452-03', '112557-00', '112557-00', '115365-00', '115365-00', '96320-34', '31146-05', '101565-59', '95763-10', '95763-11', '95433-18',
                '95431-15', '97501-11', '97883-18', '97883-18', '97883-19', '97883-19', '117483-00', '87720-64', '98425-01', '98056-07', '98056-08',
                '87964-23', '103158-22', '95138-07', '87027-21', '87027-22', '98557-42', '97522-12', '102237-03', '95098-08', '95098-09', '95098-10',
                '95098-21', '95099-17', '95864-18', '95864-19', '95864-20', '95864-21', '94356-19', '94356-21', '91984-12', '117321-00', '110394-00',
                '99588-02', '93090-32', '94560-12', '94560-13', '94560-14', '94560-15', '97742-11', '97742-15', '94560-16', '97742-19', '97742-21',
                '93975-20', '93095-11', '93095-12', '96716-18', '93466-09', '97562-12', '102309-04', '95450-18', '95450-19', '93432-11', '106385-19',
                '106385-19', '105452-04', '105452-09', '105452-16', '105452-22', '114121-35', '114862-18', '114862-26', '114862-35', '88909-28',
                '88909-29', '88909-37', '88909-38', '88909-39', '112073-01', '112073-02', '112073-03', '98821-02', '92446-12', '96592-16', '103767-13',
                '103767-13', '103767-14', '103767-14', '99585-14', '99585-14', '99585-15', '99585-15', '93949-27', '97735-12', '106328-20', '106328-21',
                '96868-11', '95433-42', '95433-43', '95433-44', '95433-45', '102771-43', '99723-07', '95431-24', '95431-25', '95431-26', '99191-01',
                '97883-20', '97883-20', '97883-21', '97883-21', '103911-16', '93495-31', '97538-14', '105081-10', '110229-13', '110229-14', '97199-07',
                '98294-10', '89840-42', '93907-14', '99760-15', '100475-05', '105798-17', '97200-06', '99633-03', '99633-04', '93889-02', '98293-08',
                '97452-15', '102632-15', '95764-13', '95764-15', '95764-17', '100207-04', '92445-20', '93788-11', '93788-12', '109685-00', '95558-14',
                '95558-15', '97730-00', '98586-01', '102757-01', '97086-09', '97742-18', '108746-16', '99310-18', '103704-49', '102938-11', '102938-12',
                '99171-02', '93533-15', '87701-37', '93375-11', '96603-12', '100943-01', '85906-58', '85906-59', '95289-00', '101849-13', '94630-05',
                '98824-04', '77519-76', '100217-18', '94200-11', '102414-17', '102414-17', '89645-23','97955-59','115751-03','115751-03','87270-56',
                '96825-02', '98850-33', '98850-34', '95108-18', '107345-16', '96538-17', '101849-17', '101849-18', '103247-27', '93982-08'

            ],
            'Overutilized Authorization' => [
                '98297-13', '88461-30', '115664-01', '116890-39', '116890-42', '116890-45', '116890-48', '116890-51', '95396-113', '95452-03', '95452-04',
                '109916-90', '119878-15', '125818-04', '125818-05', '125818-08', '125818-09', '115864-72', '125818-13', '112967-04', '112967-10', '112967-16',
                '118075-03', '118075-05', '118075-07', '118075-14', '65188-13', '113512-00', '113512-00', '100485-04', '95437-05', '102945-02', '106787-41',
                '106787-44', '105851-14', '111409-26', '110079-13', '102917-00', '115765-23', '94917-04', '103314-04', '92276-28', '103081-15', '102119-02',
                '102119-02', '117243-14', '90671-36', '118714-18', '118714-27', '118714-31', '102757-07', '106040-04', '113176-48', '96405-03', '108742-12',
                '108742-13', '108742-15', '118243-10', '120038-05', '93092-11', '93092-14', '96853-02', '96853-04', '96853-06', '100802-06', '105256-03',
                '110043-10', '116149-01', '116149-09', '116149-12', '96892-57', '112424-12', '95359-52', '99012-15', '115717-13', '119136-10', '96453-28',
                '106116-28'
            ],
            'No Auth SOC' => [
                '113590-03', '113590-04', '105226-00'
            ],
            'No Authorization' => [
                '117981-03', '108214-13', '106389-25', '108214-27', '106389-47', '108214-35', '124775-00', '112347-06', '112347-07', '112347-08', '112347-09',
                '94491-03', '97326-04', '97486-09', '113604-16', '113604-19', '92206-12', '117439-02', '113600-09', '101722-95', '113558-00', '113558-00', '91684-35',
                '99935-01', '99935-02'
            ],
            'Eligibility Issue' => [
                '98907-16', '98907-17'
            ]
        ];

        $adjustmentsByCategory = Adjustment::query()
            ->get(['id', 'category'])
            ->keyBy('category');

        $adjustmentsById = Adjustment::query()
            ->get(['id', 'category'])
            ->keyBy('id');

        $missingCategoryNames = [];
        foreach (array_unique(array_keys($mappedData)) as $categoryName) {
            if (!isset($adjustmentsByCategory[$categoryName])) {
                $missingCategoryNames[] = $categoryName;
            }
        }

        if (!empty($missingCategoryNames)) {
            dump('These category names were not found in adjustments:');
            foreach ($missingCategoryNames as $name) {
                dump($name);
            }

            die();
        }

        $totalProcessed = 0;
        foreach ($mappedData as $currentCategoryName => $ids) {
            $invoices = ClaimsMaster::select('id', 'adjustment_category_id', 'invoice_number')
            ->whereIn('invoice_number', $ids)->get();

            foreach ($invoices as $row) {
                $totalProcessed++;

                $claimId = (int) $row->id;

                try {
                    DB::transaction(function () use (
                        $row,
                        $currentCategoryName,
                        $adjustmentsByCategory,
                    ) {
                        $claim = ClaimsMaster::query()->findOrFail((int) $row->id);

                        $adjustment = $adjustmentsByCategory[$currentCategoryName] ?? null;

                        if (!$adjustment) {
                            throw new \RuntimeException(
                                "Adjustment not found for mapped category [{$currentCategoryName}]"
                            );
                        }

                        $adjustmentId = (int) $adjustment->id;
                        $categoryName = $adjustment->category;

                        //update adjustment_category_id
                        ClaimsMaster::query()
                            ->whereKey($claim->id)
                            ->update([
                                'adjustment_category_id' => $adjustmentId,
                            ]);

                        //clear history
                        ClaimAdjustmentHistory::query()
                            ->where('claim_master_id', $claim->id)
                            ->delete();

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

                    });
                } catch (Throwable $e) {

                    Log::error($e->getMessage());

                    dump("Claim {$claimId} failed: {$e->getMessage()}");
                    H::ExcelLog(
                        'migrate:categories failed for claim ' . $claimId . ' => ' . $e->getMessage(),
                        'changeAdjustmentCategories.log'
                    );
                }
            }
        }
        dump("Total processed: {$totalProcessed}");
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
