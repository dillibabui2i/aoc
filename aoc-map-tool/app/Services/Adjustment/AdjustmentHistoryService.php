<?php

namespace App\Services\Adjustment;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\Adjustments\AdjustmentStageEnum;
use App\Enums\Adjustments\AdjustmentTypeEnum;
use App\Models\ClaimAdjustmentHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdjustmentHistoryService
{

    public function __construct(private ClaimAdjustmentHistory $claimAdjustmentHistory) {}

    public function getAllAssignedTo(): Collection
    {
        $latestHistorySub = $this->claimAdjustmentHistory
            ->newQuery()
            ->selectRaw('MAX(claim_adjustment_histories.id) as id, claim_adjustment_histories.claim_master_id')
            ->groupBy('claim_adjustment_histories.claim_master_id');

        $directUserAssignments = $this->claimAdjustmentHistory
            ->newQuery()
            ->from('claim_adjustment_histories as current_history')
            ->joinSub($latestHistorySub, 'latest_history', function ($join) {
                $join->on('latest_history.id', '=', 'current_history.id');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'current_history.assignee_id')
                    ->where('current_history.assignee_type', '=', AdjustmentTypeEnum::USER->value);
            })
            ->where('current_history.event_type', AdjustmentEventTypeEnum::ASSIGNED->value)
            ->where('users.status', 1)
            ->selectRaw('DISTINCT users.id as assigned_to')
            ->selectRaw("'" . AdjustmentTypeEnum::USER->value . "' as assignee_type")
            ->selectRaw('users.name as name');

        $resolvedRoleAssignments = $this->claimAdjustmentHistory
            ->newQuery()
            ->from('claim_adjustment_histories as current_history')
            ->joinSub($latestHistorySub, 'latest_history', function ($join) {
                $join->on('latest_history.id', '=', 'current_history.id');
            })
            ->join('claims_master', 'claims_master.id', '=', 'current_history.claim_master_id')
            ->join('mssql_patients', 'mssql_patients.PatientID', '=', 'claims_master.client_id')
            ->join('users_has_roles', 'users_has_roles.role_id', '=', 'current_history.assignee_id')
            ->join('users', 'users.id', '=', 'users_has_roles.user_id')
            ->where('current_history.assignee_type', AdjustmentTypeEnum::ROLE->value)
            ->where('current_history.event_type', AdjustmentEventTypeEnum::ASSIGNED->value)
            ->where('users.status', 1)
            ->whereRaw('find_in_set(mssql_patients.aoc_facility_id, users.facilityid)')
            ->selectRaw('DISTINCT users.id as assigned_to')
            ->selectRaw("'" . AdjustmentTypeEnum::USER->value . "' as assignee_type")
            ->selectRaw('users.name as name');

        return DB::query()
            ->fromSub($directUserAssignments->union($resolvedRoleAssignments), 'resolved_assignees')
            ->select('assigned_to', 'assignee_type', 'name')
            ->groupBy('assigned_to', 'assignee_type', 'name')
            ->orderBy('name')
            ->get();
    }

    public function create(
        int $claimId,
        string $assigneeType,
        int $assigneeId,
        AdjustmentStageEnum $stage,
        AdjustmentEventTypeEnum $eventType,
        ?int $actedByUserId = null,
    ): ClaimAdjustmentHistory {
        return $this->claimAdjustmentHistory->newQuery()->create([
            'claim_master_id' => $claimId,
            'assignee_type' => $assigneeType,
            'assignee_id' => $assigneeId,
            'stage' => $stage->value,
            'event_type' => $eventType->value,
            'acted_by_user_id' => $actedByUserId,
        ]);
    }

    public function getCurrentHistory(int $claimId): ?ClaimAdjustmentHistory
    {
        return $this->claimAdjustmentHistory->newQuery()
            ->where('claim_master_id', $claimId)
            ->latest('id')
            ->first();
    }

    public function getPreviousTerminalHistoryId(int $claimId, int $anchorId): ?int
    {
        return $this->claimAdjustmentHistory
            ->newQuery()
            ->where('claim_master_id', $claimId)
            ->where('id', '<', $anchorId)
            ->whereIn('event_type', [
                AdjustmentEventTypeEnum::REJECTED->value,
                AdjustmentEventTypeEnum::COMPLETED->value,
            ])
            ->max('id') ?: null;
    }

    public function getCurrentCycleHistories(int $claimId, ?int $anchorId = null): \Illuminate\Support\Collection
    {
        $anchorId = $anchorId ?: $this->getCurrentHistory($claimId)?->id;

        if (!$anchorId) {
            return collect();
        }

        $previousTerminalId = $this->getPreviousTerminalHistoryId($claimId, $anchorId);

        return $this->claimAdjustmentHistory
            ->newQuery()
            ->where('claim_master_id', $claimId)
            ->when($previousTerminalId, function ($query) use ($previousTerminalId) {
                $query->where('id', '>', $previousTerminalId);
            })
            ->where('id', '<=', $anchorId)
            ->orderBy('id')
            ->get();
    }

    public function getApprovedActorIdsFromCurrentCycle(int $claimId, ?int $anchorId = null): array
    {
        return $this->getCurrentCycleHistories($claimId, $anchorId)
            ->filter(function (ClaimAdjustmentHistory $history) {
                return $history->event_type === AdjustmentEventTypeEnum::APPROVED;
            })
            ->pluck('acted_by_user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function canStartNewCycle(int $claimId): bool
    {
        $currentHistory = $this->getCurrentHistory($claimId);

        if (!$currentHistory) {
            return true;
        }

        return in_array($currentHistory->event_type, [
            AdjustmentEventTypeEnum::REJECTED,
            AdjustmentEventTypeEnum::COMPLETED,
        ], true);
    }

}
