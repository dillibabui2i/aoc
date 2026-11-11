<?php

namespace App\Services\Adjustment;

use App\Enums\Adjustments\AdjustmentTypeEnum;
use App\Helpers\H;
use App\Helpers\Notifications;
use App\Models\ClaimsMaster;
use App\MssqlPatients;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdjustmentNotificationService
{

    public function __construct(private AdjustmentHistoryService $adjustmentHistoryService)
    {}

    public function notifyNewAssignee(ClaimsMaster $claim, array $nextStep): void
    {
        $title = 'New adjustment assigned to you for approval. Please review in the adjustment bucket';

        foreach (
            $this->resolveNotificationUserIds(
                $claim,
                $nextStep['assignee_type'],
                (int)$nextStep['assignee_id']
            ) as $userId
        ) {
            Notifications::addNotification([
                'user_id' => $userId,
                'title' => $title,
                'type' => 'invoice',
                'related_item' => $claim->id,
                'notes' => $title,
            ]);
        }
    }

    private function resolveNotificationUserIds(ClaimsMaster $claim, string $assigneeType, int $assigneeId): array
    {
        if ($assigneeType === AdjustmentTypeEnum::USER->value) {
            return [$assigneeId];
        }

        if ($assigneeType === AdjustmentTypeEnum::ROLE->value) {
            $facilityId = MssqlPatients::query()
                ->where('PatientID', $claim->client_id)
                ->value('aoc_facility_id');

            if (!$facilityId) {
                return [];
            }

            return User::query()
                ->join('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                ->where('users.status', 1)
                ->where('users_has_roles.role_id', $assigneeId)
                ->whereRaw('find_in_set(?, users.facilityid)', [$facilityId])
                ->pluck('users.id')
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    private function getRejectNotificationUserIds(ClaimsMaster $claim, ?int $anchorId = null): array
    {
        $historyApproverIds = $this->adjustmentHistoryService
            ->getApprovedActorIdsFromCurrentCycle($claim->id, $anchorId);

        return collect($historyApproverIds)
            ->push($claim->biller)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function notifyRejectedUsers(ClaimsMaster $claim, int $anchorId): void
    {
        $patientDetails = DB::table('mssql_patients')
            ->where('PatientID', $claim->client_id)
            ->first();

        $clientName = $patientDetails
            ? trim(($patientDetails->Patient_FirstName ?? '') . ' ' . ($patientDetails->Patient_LastName ?? ''))
            : 'client';

        $title = Auth::user()->name . ' has rejected adjustment request for ' . $clientName . '. Please review details on invoice.';

        foreach ($this->getRejectNotificationUserIds($claim, $anchorId) as $userId) {
            Notifications::addNotification([
                'user_id' => $userId,
                'title' => $title,
                'type' => 'invoice',
                'related_item' => $claim->id,
                'notes' => $title,
            ]);
        }
    }

    public function addCompletedActivityNote(array $reqData): void
    {
        $logData = [];
        $logData['entity'] = 'invoice';
        $logData['entity_id'] = $reqData['selectedId'];
        $logData['notes'] = Auth::user()->name
            . ' has completed the adjustment request for invoice '
            . $reqData['invoiceNumber']
            . '. Comments: '
            . ($reqData['adjustmentsInvoiceComments'] ?? '');

        H::getHelper('ActivityNotes')->setActivityNotes($logData);
    }

    public function addApproveActivityNote(array $reqData): void
    {
        $logData = [];
        $logData['entity'] = 'invoice';
        $logData['entity_id'] = $reqData['selectedId'];
        $logData['notes'] = Auth::user()->name
            . ' has approved the adjustment request for invoice '
            . $reqData['invoiceNumber']
            . '. Comments: '
            . ($reqData['adjustmentsInvoiceComments'] ?? '');

        H::getHelper('ActivityNotes')->setActivityNotes($logData);
    }

    public function addRejectActivityNote(array $reqData): void
    {
        $logData = [];
        $logData['entity'] = 'invoice';
        $logData['entity_id'] = $reqData['selectedId'];
        $logData['notes'] = Auth::user()->name
            . ' has rejected the adjustment request for invoice '
            . $reqData['invoiceNumber']
            . '. Comments: '
            . ($reqData['adjustmentsInvoiceComments'] ?? '');

        H::getHelper('ActivityNotes')->setActivityNotes($logData);
    }

    public function addRequestOpsReviewActivityNote(array $reqData): void
    {
        $logData = [];
        $logData['entity'] = 'invoice';
        $logData['entity_id'] = $reqData['selectedId'];
        $logData['notes'] = Auth::user()->name
            . ' has requested Ops Review for invoice '
            . $reqData['invoiceNumber']
            . '. Comments: '
            . ($reqData['adjustmentsInvoiceComments'] ?? '');

        H::getHelper('ActivityNotes')->setActivityNotes($logData);
    }

}
