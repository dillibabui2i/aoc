<?php

namespace App\VBC\Repositories\SickPlan;

use App\VBC\Models\SickPlan\SickPlanCatalogModel;
use App\VBC\Models\SickPlan\SickPlanModel;
use Illuminate\Database\Eloquent\Collection;

class SickPlanReadRepository
{
    /** Relations the Sick Plan tab needs to render without N+1 queries. */
    private const PLAN_RELATIONS = ['items.catalog', 'items.completedBy', 'items.author'];

    public function __construct(
        private SickPlanModel $sickPlanModel,
        private SickPlanCatalogModel $sickPlanCatalogModel,
    ) {
    }

    /** The client's Sick Plan — one per client, reused across every hospitalization tracker (AC5/AC12). */
    public function findForPatient(int $patientId): ?SickPlanModel
    {
        /** @var SickPlanModel|null $plan */
        $plan = $this->sickPlanModel->newQuery()
            ->with(self::PLAN_RELATIONS)
            ->where('patient_id', $patientId)
            ->first();

        return $plan;
    }

    public function find(int $planId): ?SickPlanModel
    {
        /** @var SickPlanModel|null $plan */
        $plan = $this->sickPlanModel->newQuery()
            ->with(self::PLAN_RELATIONS)
            ->find($planId);

        return $plan;
    }

    /** The Sick Plan Type catalog, seeded reference data (2026_09_15_100001_seed_sick_plan_catalog.php). */
    public function getCatalog(): Collection
    {
        return $this->sickPlanCatalogModel->newQuery()->orderBy('id')->get();
    }
}
