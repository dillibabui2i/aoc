<?php

namespace App\Services\Adjustment;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\Adjustments\AdjustmentStageEnum;
use App\Enums\Adjustments\AdjustmentTypeEnum;
use App\Exceptions\MissingArSupervisorException;
use App\Helpers\H;
use App\Models\Adjustment;
use App\Models\ClaimsMaster;
use App\MssqlPatients;
use App\Roles;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class AdjustmentWorkflowService
{
    public const VP_OF_RCM_ROLE = 'VP of Revenue Cycle Management';
    public const REGIONAL_VP_ROLE = 'Regional VP';
    public const STATE_VP_ROLE = 'State VP';

    public function __construct(
        public AdjustmentHistoryService $adjustmentHistoryService,
        public AdjustmentNotificationService $notificationService
    ) {}

    public function resolveFacilityId(ClaimsMaster $claim): ?int
    {
        if (!empty($claim->client_id)) {
            return MssqlPatients::query()
                ->where('PatientID', $claim->client_id)
                ->value('aoc_facility_id');
        }

        return null;
    }

    public function resolveNextStep(ClaimsMaster $claim, AdjustmentStageEnum $currentStage): ?array
    {
        $adjustment = Adjustment::query()->find($claim->adjustment_category_id);

        if (!$adjustment) {
            throw new RuntimeException("Adjustment config not found for claim [{$claim->id}]");
        }

        $stages = $this->buildOrderedStages($adjustment);

        $currentIndex = array_search($currentStage, $stages, true);

        if ($currentIndex === false) {
            throw new RuntimeException(
                "Current stage [{$currentStage->value}] is not configured for claim [{$claim->id}]"
            );
        }

        $nextStages = array_slice($stages, $currentIndex + 1);

        foreach ($nextStages as $stage) {
            $assignee = $this->resolveStageAssignee($adjustment, $stage, $claim);

            if ($assignee) {
                return [
                    'stage' => $stage,
                    'assignee_type' => $assignee['assignee_type'],
                    'assignee_id' => $assignee['assignee_id'],
                ];
            }

            if ($stage === AdjustmentStageEnum::AR_SUPERVISOR) {
                throw new MissingArSupervisorException();
            }
        }

        return null;
    }

    private function buildOrderedStages(Adjustment $adjustment): array
    {
        $stages = [];

        if ($adjustment->level_1_type && $adjustment->level_1_id) {
            $stages[] = AdjustmentStageEnum::LEVEL_1;
        }

        if ($adjustment->level_2_type && $adjustment->level_2_id) {
            $stages[] = AdjustmentStageEnum::LEVEL_2;
        }

        if ($adjustment->level_3_type && $adjustment->level_3_id) {
            $stages[] = AdjustmentStageEnum::LEVEL_3;
        }

        if ($adjustment->requires_ops_review) {
            $stages[] = AdjustmentStageEnum::OPERATIONS;
        }

        $stages[] = AdjustmentStageEnum::AR_SUPERVISOR;

        return $stages;
    }

    private function resolveStageAssignee(
        Adjustment $adjustment,
        AdjustmentStageEnum $stage,
        ClaimsMaster $claim
    ): ?array {
        return match ($stage) {
            AdjustmentStageEnum::LEVEL_1 => $this->resolveConfiguredStageAssignee(
                $claim,
                $adjustment->level_1_type,
                $adjustment->level_1_id
            ),
            AdjustmentStageEnum::LEVEL_2 => $this->resolveConfiguredStageAssignee(
                $claim,
                $adjustment->level_2_type,
                $adjustment->level_2_id
            ),
            AdjustmentStageEnum::LEVEL_3 => $this->resolveConfiguredStageAssignee(
                $claim,
                $adjustment->level_3_type,
                $adjustment->level_3_id
            ),
            AdjustmentStageEnum::OPERATIONS => $this->resolveOperationsAssignee($claim),
            AdjustmentStageEnum::AR_SUPERVISOR => $this->resolveArSupervisorAssignee($claim),
        };
    }

    private function makeAssigneeData(?string $type, $id): ?array
    {
        if (!$type || !$id) {
            return null;
        }

        return [
            'assignee_type' => $type,
            'assignee_id' => (int)$id,
        ];
    }

    private function resolveOperationsAssignee(ClaimsMaster $claim): ?array
    {
        $facilityId = $this->resolveFacilityId($claim);

        $regionalVpRoleId = $this->getRoleIdByName(self::REGIONAL_VP_ROLE);
        if ($regionalVpRoleId && $this->hasActiveUsersForRoleAndFacility($facilityId, $regionalVpRoleId)) {
            return $this->makeAssigneeData(AdjustmentTypeEnum::ROLE->value, $regionalVpRoleId);
        }

        $stateVpRoleId = $this->getRoleIdByName(self::STATE_VP_ROLE);
        if ($stateVpRoleId && $this->hasActiveUsersForRoleAndFacility($facilityId, $stateVpRoleId)) {
            return $this->makeAssigneeData(AdjustmentTypeEnum::ROLE->value, $stateVpRoleId);
        }

        return null;
    }

    public function resolveStartHistoryStep(ClaimsMaster $claim, int $adjustmentId): array
    {
        $adjustment = Adjustment::query()->find($adjustmentId);

        if (!$adjustment) {
            throw new RuntimeException("Adjustment config not found. ID: {$adjustmentId}");
        }

        foreach ($this->buildOrderedStages($adjustment) as $stage) {
            $assignee = $this->resolveStageAssignee($adjustment, $stage, $claim);

            if ($assignee) {
                return [
                    'stage' => $stage,
                    'assignee_type' => $assignee['assignee_type'],
                    'assignee_id' => $assignee['assignee_id'],
                    'adjustment_category_id' => $adjustment->id,
                ];
            }

            if ($stage === AdjustmentStageEnum::AR_SUPERVISOR) {
                throw new MissingArSupervisorException();
            }
        }

        throw new RuntimeException(
            "Unable to resolve first applicable approval stage for adjustment config ID: {$adjustmentId}"
        );
    }

    private function resolveConfiguredStageAssignee(ClaimsMaster $claim, ?string $type, ?int $id): ?array
    {
        if (!$type || !$id) {
            return null;
        }

        if ($type === AdjustmentTypeEnum::USER->value) {
            return $this->makeAssigneeData($type, $id);
        }

        if ($type === AdjustmentTypeEnum::ROLE->value) {
            return $this->hasActiveUsersForRoleAndFacility(
                $this->resolveFacilityId($claim),
                $id
            ) ? $this->makeAssigneeData($type, $id) : null;
        }

        return null;
    }

    private function hasActiveUsersForRoleAndFacility(?int $facilityId, int $roleId, ?int $userId = null): bool
    {
        if (!$facilityId) {
            return false;
        }

        $query = User::query()
            ->join('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->where('users.status', 1)
            ->where('users_has_roles.role_id', $roleId)
            ->whereRaw('find_in_set(?, users.facilityid)', [$facilityId]);

        if ($userId !== null) {
            $query->where('users.id', $userId);
        }

        return $query->exists();
    }

    private function resolveArSupervisorAssignee(ClaimsMaster $claim): ?array
    {
        $facilityId = $this->resolveFacilityId($claim);

        $arSupervisorRoleId = $this->getRoleIdByName('AR Supervisor');

        if (!$arSupervisorRoleId) {
            return null;
        }

        return $this->hasActiveUsersForRoleAndFacility($facilityId, $arSupervisorRoleId)
            ? $this->makeAssigneeData(AdjustmentTypeEnum::ROLE->value, $arSupervisorRoleId)
            : null;
    }

    private function getRoleIdByName(string $roleName): ?int
    {
        return (int)Roles::query()
            ->where('name', $roleName)
            ->value('id') ?: null;
    }

    /**
     * @throws Throwable
     */
    public function approve(array $reqData): string
    {
        return DB::transaction(function () use ($reqData) {
            $claim = ClaimsMaster::query()->findOrFail($reqData['selectedId']);
            $userId = Auth::id();

            $error = $this->ensureAuthorizedForNewWorkflowAction($claim, $userId);
            if ($error) {
                throw ValidationException::withMessages([
                    'adjustment' => $error,
                ]);
            }

            $currentHistory = $this->adjustmentHistoryService->getCurrentHistory($claim->id);

            if (!$currentHistory) {
                throw ValidationException::withMessages([
                    'adjustment' => 'No active workflow history found.',
                ]);
            }

            if ($currentHistory->stage === AdjustmentStageEnum::AR_SUPERVISOR) {
                $this->adjustmentHistoryService->create(
                    claimId: $claim->id,
                    assigneeType: $currentHistory->assignee_type,
                    assigneeId: (int)$currentHistory->assignee_id,
                    stage: $currentHistory->stage,
                    eventType: AdjustmentEventTypeEnum::COMPLETED,
                    actedByUserId: $userId,
                );

                $this->notificationService->addCompletedActivityNote($reqData);

                return 'Adjustment completed successfully';
            }

            $this->adjustmentHistoryService->create(
                claimId: $claim->id,
                assigneeType: $currentHistory->assignee_type,
                assigneeId: (int)$currentHistory->assignee_id,
                stage: $currentHistory->stage,
                eventType: AdjustmentEventTypeEnum::APPROVED,
                actedByUserId: $userId,
            );

            $nextStep = $this->resolveNextStep($claim, $currentHistory->stage);

            if ($nextStep) {
                $this->adjustmentHistoryService->create(
                    claimId: $claim->id,
                    assigneeType: $nextStep['assignee_type'],
                    assigneeId: (int)$nextStep['assignee_id'],
                    stage: $nextStep['stage'],
                    eventType: AdjustmentEventTypeEnum::ASSIGNED,
                );

                $this->notificationService->notifyNewAssignee($claim, $nextStep);
            }

            $this->notificationService->addApproveActivityNote($reqData);

            return 'Outcome updated successfully';
        });
    }

    private function ensureAuthorizedForNewWorkflowAction(ClaimsMaster $claim, int $userId): ?string
    {
        $currentHistory = $this->adjustmentHistoryService->getCurrentHistory($claim->id);

        if (!$currentHistory) {
            return 'No workflow history found.';
        }

        if ($currentHistory->event_type !== AdjustmentEventTypeEnum::ASSIGNED) {
            return 'This adjustment is no longer available for action.';
        }

        if ($currentHistory->assignee_type === AdjustmentTypeEnum::USER->value) {
            if ((int)$currentHistory->assignee_id !== $userId) {
                return 'This adjustment is assigned to another user.';
            }

            return null;
        }

        if ($currentHistory->assignee_type === AdjustmentTypeEnum::ROLE->value) {
            $roleId = (int)$currentHistory->assignee_id;
            $facilityId = $this->resolveFacilityId($claim);

            if (!$this->userHasRole($userId, $roleId)) {
                return 'You do not have the required role for this adjustment.';
            }

            if (!$this->hasActiveUsersForRoleAndFacility($facilityId, $roleId, $userId)) {
                return 'You have the required role, but you are not assigned to the branch.';
            }

            return null;
        }

        return 'You are not authorized to perform this action.';
    }

    private function userHasRole(int $userId, int $roleId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereHas('roles', function ($query) use ($roleId) {
                $query->where('roles.id', $roleId);
            })
            ->exists();
    }

    /**
     * @throws Throwable
     */
    public function reject(array $reqData): string
    {
        return DB::transaction(function () use ($reqData) {
            $claim = ClaimsMaster::query()->findOrFail($reqData['selectedId']);
            $userId = Auth::id();

            $error = $this->ensureAuthorizedForNewWorkflowAction($claim, $userId);
            if ($error) {
                throw ValidationException::withMessages([
                    'adjustment' => $error,
                ]);
            }

            $currentHistory = $this->adjustmentHistoryService->getCurrentHistory($claim->id);

            if (!$currentHistory) {
                throw ValidationException::withMessages([
                    'adjustment' => 'No active workflow history found.',
                ]);
            }

            $rejectedHistory = $this->adjustmentHistoryService->create(
                claimId: $claim->id,
                assigneeType: $currentHistory->assignee_type,
                assigneeId: (int)$currentHistory->assignee_id,
                stage: $currentHistory->stage,
                eventType: AdjustmentEventTypeEnum::REJECTED,
                actedByUserId: $userId,
            );

            ClaimsMaster::query()
                ->whereKey($claim->id)
                ->update([
                    'followedup_at' => null,
                    'followedup_by' => null,
                ]);

            $this->notificationService->notifyRejectedUsers($claim, $rejectedHistory->id);
            $this->notificationService->addRejectActivityNote($reqData);

            return 'Adjustment rejected successfully';
        });
    }

    /**
     * @throws Throwable
     */
    public function requestOpsReview(array $reqData): string
    {

        return DB::transaction(function () use ($reqData) {
            $claim = ClaimsMaster::query()
                ->with('adjustment')
                ->findOrFail($reqData['selectedId']);

            $user = Auth::user();
            $userId = (int)$user->id;

            $error = $this->ensureCanRequestOpsReviewFromOutcome($claim, $user);

            if ($error) {
                throw ValidationException::withMessages([
                    'adjustment' => $error,
                ]);
            }

            $nextStep = $this->resolveOpsReviewStep($claim);

            $this->adjustmentHistoryService->create(
                claimId: $claim->id,
                assigneeType: $nextStep['assignee_type'],
                assigneeId: (int)$nextStep['assignee_id'],
                stage: $nextStep['stage'],
                eventType: AdjustmentEventTypeEnum::ASSIGNED,
                actedByUserId: $userId,
            );

            $this->notificationService->notifyNewAssignee($claim, $nextStep);
            $this->notificationService->addRequestOpsReviewActivityNote($reqData);

            return $nextStep['stage'] === AdjustmentStageEnum::OPERATIONS
                ? 'Adjustment routed to Ops Review.'
                : 'No Ops reviewer was found for this invoice branch. Adjustment routed to AR Supervisor.';
        });
    }

    public function canRequestOpsReviewFromOutcome(int $claimMasterId, User $user): bool
    {
        if (!$this->userHasVpOfRcmRole($user)) {
            return false;
        }

        return !$this->categoryRequiresOpsReview($claimMasterId);
    }

    public function categoryRequiresOpsReview(int $claimMasterId): bool
    {
        $claim = ClaimsMaster::query()
            ->select(['id', 'adjustment_category_id'])
            ->with(['adjustment:id,requires_ops_review'])
            ->find($claimMasterId);

        return (bool) $claim?->adjustment?->requires_ops_review;
    }

    private function userHasVpOfRcmRole(User $user): bool
    {
        $currentRoles = H::getHelper('ConstantHelper')
            ->getCurrentRolesNameByUserId($user->id);

        return in_array(self::VP_OF_RCM_ROLE, $currentRoles, true);
    }


    private function resolveOpsReviewStep(ClaimsMaster $claim): array
    {
        $opsAssignee = $this->resolveOperationsAssignee($claim);

        if ($opsAssignee) {
            return [
                'stage' => AdjustmentStageEnum::OPERATIONS,
                'assignee_type' => $opsAssignee['assignee_type'],
                'assignee_id' => $opsAssignee['assignee_id'],
            ];
        }

        $arSupervisorAssignee = $this->resolveArSupervisorAssignee($claim);

        if ($arSupervisorAssignee) {
            return [
                'stage' => AdjustmentStageEnum::AR_SUPERVISOR,
                'assignee_type' => $arSupervisorAssignee['assignee_type'],
                'assignee_id' => $arSupervisorAssignee['assignee_id'],
            ];
        }

        throw new MissingArSupervisorException();
    }

    private function ensureCanRequestOpsReviewFromOutcome(ClaimsMaster $claim, User $user): ?string
    {
        if (! $this->userHasVpOfRcmRole($user)) {
            return 'You are not allowed to request Ops Review for this adjustment.';
        }

        if ($this->categoryRequiresOpsReview($claim->id)) {
            return 'Ops Review is already required for this adjustment category.';
        }

        $currentHistory = $this->adjustmentHistoryService->getCurrentHistory($claim->id);

        if (! $currentHistory) {
            return 'No workflow history found.';
        }

        if ($currentHistory->event_type !== AdjustmentEventTypeEnum::ASSIGNED) {
            return 'This adjustment is no longer available for action.';
        }

        if ($currentHistory->stage === AdjustmentStageEnum::OPERATIONS) {
            return 'This adjustment is already routed to Ops Review.';
        }

        return null;
    }

}
