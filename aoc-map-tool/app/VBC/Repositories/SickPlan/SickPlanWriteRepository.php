<?php

namespace App\VBC\Repositories\SickPlan;

use App\VBC\Enum\SickPlan\SickPlanStatusEnum;
use App\VBC\Models\SickPlan\SickPlanCatalogModel;
use App\VBC\Models\SickPlan\SickPlanItemModel;
use App\VBC\Models\SickPlan\SickPlanModel;
use RuntimeException;

class SickPlanWriteRepository
{
    public function __construct(
        private SickPlanModel $sickPlanModel,
        private SickPlanItemModel $sickPlanItemModel,
        private SickPlanCatalogModel $sickPlanCatalogModel,
    ) {
    }

    /** A blank Sick Plan: the plan row plus one blank item per catalog entry (AC3). */
    public function createBlankForPatient(int $patientId, int $authorId): SickPlanModel
    {
        /** @var SickPlanModel $plan */
        $plan = $this->sickPlanModel->newQuery()->create([
            'patient_id' => $patientId,
            'status' => SickPlanStatusEnum::OPEN,
            'activated_at' => now(),
        ]);

        /** @var \Illuminate\Support\Collection<int, SickPlanCatalogModel> $catalogEntries */
        $catalogEntries = $this->sickPlanCatalogModel->newQuery()->orderBy('id')->get();

        foreach ($catalogEntries as $catalogEntry) {
            $this->sickPlanItemModel->newQuery()->create([
                'sick_plan_id' => $plan->id,
                'catalog_id' => $catalogEntry->id,
                'completed' => false,
                'author_id' => $authorId,
            ]);
        }

        return $plan;
    }

    public function updateItem(SickPlanItemModel $item, int $catalogId, bool $completed, ?string $notes, int $currentUserId): SickPlanItemModel
    {
        $item->update([
            'catalog_id' => $catalogId,
            'completed' => $completed,
            'completed_by_id' => $completed ? $currentUserId : null,
            'notes' => $notes,
        ]);

        return $item;
    }

    /** Resolves the catalog id for a Sick Plan Type value, or throws — the request already validated it exists. */
    public function catalogIdForType(string $sickPlanType): int
    {
        /** @var SickPlanCatalogModel|null $catalogEntry */
        $catalogEntry = $this->sickPlanCatalogModel->newQuery()->where('domain', $sickPlanType)->first();

        if (empty($catalogEntry)) {
            throw new RuntimeException("Sick Plan Type [{$sickPlanType}] is not in the catalog.");
        }

        return $catalogEntry->id;
    }
}
