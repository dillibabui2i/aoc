<?php

namespace App\Services\Billing;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\Adjustments\AdjustmentStageEnum;
use App\Exceptions\MissingArSupervisorException;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\Models\Adjustment;
use App\Models\ClaimsMaster;
use App\MssqlPatients;
use App\Services\Adjustment\AdjustmentHistoryService;
use App\Services\Adjustment\AdjustmentWorkflowService;
use App\User;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class BillingService
{

    public function __construct(
        private AdjustmentWorkflowService $workflowService,
        private AdjustmentHistoryService $adjustmentHistoryService
    ) {}

    public function saveInvoiceOutcome(array $data, User $user): array
    {
        $validationError = $this->validateInvoiceOutcomePayload($data);

        if ($validationError !== null) {
            return [
                'status' => false,
                'message' => $validationError,
            ];
        }

        $ids = $this->parseSelectedIds($data['selectedIds']);
        $activityData = $this->buildInvoiceOutcomeActivity($data, $user->name);

        if ($data['billingInvoiceReason'] === 'Write off request') {
            $this->processWriteOffRequest($ids, $data, $activityData, $user->id);
        } else {
            $this->processRegularOutcome($ids, $data, $activityData, $user->id);
        }

        $this->writeInvoiceActivityNotes($ids, $activityData['activity_str']);

        return [
            'status' => true,
            'message' => 'Outcome updated successfully',
        ];
    }

    private function validateInvoiceOutcomePayload(array $data): ?string
    {
        if (
            empty($data['selectedIds']) ||
            empty($data['billingInvoiceReason']) ||
            empty($data['billingInvoiceComments'])
        ) {
            return 'Reason not selected';
        }

        if (
            $data['billingInvoiceReason'] === 'Write off request' &&
            LD::checkFeatureFlag('update-adjustment-workflow') &&
            empty($data['billingInvoiceCategory'])
        ) {
            return 'Category not selected';
        }

        return null;
    }

    private function parseSelectedIds(string $selectedIds): array
    {
        return collect(explode(',', $selectedIds))
            ->map(fn($id) => (int)trim($id))
            ->filter(fn($id) => $id > 0)
            ->values()
            ->toArray();
    }

    private function buildInvoiceOutcomeActivity(array $data, string $userName): array
    {
        $activityStr = '';
        $notePrefix = now()->format('m/d') . ' ' . $userName . ' commented ';

        $reason = $data['billingInvoiceReason'];
        $activityStr .= 'Reason: ' . $reason . '<br>';

        $billingInvoiceSubReason = '';
        if (!empty($data['billingInvoiceSubReason'])) {
            $subReason = config('constant.invoice_outcome_subreasons');
            $billingInvoiceSubReason = $subReason[$data['billingInvoiceSubReason']] ?? '';
        }

        if ($billingInvoiceSubReason !== '') {
            $activityStr .= 'Sub Reason: ' . $billingInvoiceSubReason . '<br>';
        }

        if (!empty($data['billingInvoiceSubReasonDetails'])) {
            $activityStr .= 'Details: ' . $data['billingInvoiceSubReasonDetails'] . '<br>';
        }

        if (
            LD::checkFeatureFlag('add-category-option-in-billing-outcome') &&
            !empty($data['billingInvoiceCategory'])
        ) {
            $categoryLabel = $this->resolveCategoryLabelForActivity($data['billingInvoiceCategory']);

            if ($categoryLabel !== '') {
                $activityStr .= 'Category: ' . $categoryLabel . '<br>';
            }
        }

        $activityStr .= 'Comments: ' . $data['billingInvoiceComments'];

        return [
            'activity_str' => $activityStr,
            'final_note_str' => $notePrefix . $activityStr,
            'sub_reason' => $billingInvoiceSubReason,
        ];
    }

    private function resolveCategoryLabelForActivity(string|int $rawCategoryValue): string
    {
        if (LD::checkFeatureFlag('update-adjustment-workflow')) {
            return (string)Adjustment::query()
                ->whereKey((int)$rawCategoryValue)
                ->value('category');
        }

        return (string)$rawCategoryValue;
    }

    /**
     * @throws Throwable
     */
    private function processWriteOffRequest(array $ids, array $data, array $activityData, int $userId): void
    {
        foreach ($ids as $id) {
            if (LD::checkFeatureFlag('update-adjustment-workflow')) {
                DB::transaction(function () use ($id, $data, $activityData, $userId) {
                    $claim = ClaimsMaster::query()->findOrFail($id);

                    if (!$this->adjustmentHistoryService->canStartNewCycle($claim->id)) {
                        throw ValidationException::withMessages([
                            'adjustment' => 'This adjustment workflow is still active and cannot be restarted.',
                        ]);
                    }

                    $historyStep = $this->workflowService->resolveStartHistoryStep(
                        $claim,
                        (int)$data['billingInvoiceCategory']
                    );

                    $updateData = [
                        'reason' => $data['billingInvoiceReason'],
                        'notes' => $activityData['final_note_str'],
                        'followedup_at' => now(),
                        'followedup_by' => $userId,
                        'comment' => $data['billingInvoiceComments'],
                        'adjustment_category_id' => (int)$historyStep['adjustment_category_id'],
                        'biller' => $userId,
                    ];

                    ClaimsMaster::query()
                        ->whereKey($id)
                        ->update($updateData);

                    $this->adjustmentHistoryService->create(
                        claimId: $claim->id,
                        assigneeType: $historyStep['assignee_type'],
                        assigneeId: (int)$historyStep['assignee_id'],
                        stage: $historyStep['stage'],
                        eventType: AdjustmentEventTypeEnum::ASSIGNED,
                        actedByUserId: $userId,
                    );

                    $this->workflowService->notificationService->notifyNewAssignee($claim, $historyStep);
                });
            } else {
                $assignedToDetails = $this->resolveLegacyWriteOffAssignment($id, $userId);

                $updateData = [
                    'reason' => $data['billingInvoiceReason'],
                    'adjustment_status' => $assignedToDetails['adjustment_status'],
                    'assigned_to' => $assignedToDetails['assigned_to'],
                    'notes' => $activityData['final_note_str'],
                    'followedup_at' => now(),
                    'followedup_by' => $userId,
                    'comment' => $data['billingInvoiceComments'],
                    'category' => $data['billingInvoiceCategory'] ?? '',
                    'biller' => $userId,
                ];

                ClaimsMaster::query()
                    ->where('id', $id)
                    ->update($updateData);
            }
        }
    }

    private function processRegularOutcome(array $ids, array $data, array $activityData, int $userId): void
    {
        ClaimsMaster::query()
            ->whereIn('id', $ids)
            ->update([
                'reason' => $data['billingInvoiceReason'],
                'subreason' => $activityData['sub_reason'],
                'notes' => $activityData['final_note_str'],
                'followedup_at' => now(),
                'followedup_by' => $userId,
                'comment' => $data['billingInvoiceComments'],
                'subreason_details' => $data['billingInvoiceSubReasonDetails'] ?? '',
            ]);
    }

    private function resolveLegacyWriteOffAssignment(int $claimMasterId, int $billerId): array
    {
        $billerDetails = User::query()
            ->select('billing_team_lead', 'billing_team_manager')
            ->where('id', $billerId)
            ->first();

        if (!$billerDetails) {
            throw new RuntimeException("Biller not found. ID: $billerId");
        }

        if (!empty($billerDetails->billing_team_lead)) {
            return [
                'assigned_to' => (int)$billerDetails->billing_team_lead,
                'adjustment_status' => 'Pending Team Lead',
            ];
        }

        if (!empty($billerDetails->billing_team_manager)) {
            return [
                'assigned_to' => (int)$billerDetails->billing_team_manager,
                'adjustment_status' => 'Pending Manager',
            ];
        }

        $patientId = ClaimsMaster::query()
            ->where('id', $claimMasterId)
            ->value('client_id');

        $facilityId = MssqlPatients::query()
            ->where('PatientID', $patientId)
            ->value('aoc_facility_id');

        $directorDetail = H::getHelper('UserPermissionResolver')
            ->getUserIdByFacilityAndRole($facilityId, 'Director of Revenue Cycle Management');

        if (!empty($directorDetail[0])) {
            return [
                'assigned_to' => (int)$directorDetail[0],
                'adjustment_status' => 'Pending Director',
            ];
        }

        $vpDetail = H::getHelper('UserPermissionResolver')
            ->getUserIdByFacilityAndRole($facilityId, 'VP of Revenue Cycle Management');

        if (!empty($vpDetail[0])) {
            return [
                'assigned_to' => (int)$vpDetail[0],
                'adjustment_status' => 'Pending VP',
            ];
        }

        throw new RuntimeException("Unable to resolve legacy assignee for claim_master_id: $claimMasterId");
    }

    private function writeInvoiceActivityNotes(array $ids, string $activityStr): void
    {
        foreach ($ids as $id) {
            $invoiceNumber = $this->getInvoiceNumberById($id);

            $logData = [
                'entity' => 'invoice',
                'entity_id' => $id,
                'notes' => 'Invoice#: ' . ($invoiceNumber ?? '') . ' <br>' . $activityStr,
            ];

            H::getHelper('ActivityNotes')->setActivityNotes($logData);
        }
    }

    public function getInvoiceNumberById(int $id): ?string
    {
        return ClaimsMaster::query()
            ->where('id', $id)
            ->value('invoice_number');
    }

    public function getAdjustmentStatusSelectSql(): Expression
    {
        $assigned = AdjustmentEventTypeEnum::ASSIGNED->value;

        $whenAssigned = implode(
            "\n",
            array_map(
                fn(AdjustmentStageEnum $stage
                ) => "WHEN current_history.event_type = '{$assigned}' AND current_history.stage = '{$stage->value}' THEN '{$stage->label()}'",
                AdjustmentStageEnum::cases()
            )
        );

        $whenEvents = implode(
            "\n",
            array_map(
                fn(AdjustmentEventTypeEnum $event
                ) => "WHEN current_history.event_type = '{$event->value}' THEN '{$event->label()}'",
                AdjustmentEventTypeEnum::casesWithoutAssigned()
            )
        );

        return DB::raw(
            "
                CASE
                    {$whenAssigned}
                    {$whenEvents}
                    ELSE claims_master.adjustment_status
                END as adjustment_status
            "
        );
    }

    public function getAssignedToSelectSql(): Expression
    {
        return DB::raw(
            "
                CASE
                     WHEN current_history.assignee_type = 'user' THEN current_history.assignee_id
                END as assigned_to
            "
        );
    }

    public function getAssignedUserNameSelectSql(): Expression
    {
        return DB::raw(
            "
           CASE
               WHEN current_history.assignee_type = 'user' THEN history_assigned_users.name
               WHEN current_history.assignee_type = 'role' THEN COALESCE(
                   (
                       SELECT GROUP_CONCAT(DISTINCT resolved_users.name ORDER BY resolved_users.name SEPARATOR ', ')
                       FROM users as resolved_users
                       JOIN users_has_roles
                           ON users_has_roles.user_id = resolved_users.id
                       WHERE resolved_users.status = 1
                         AND users_has_roles.role_id = current_history.assignee_id
                         AND find_in_set(mssql_patients.aoc_facility_id, resolved_users.facilityid)
                   ),
                   'Unassigned'
               )
               ELSE assigned_users.name
           END as assigned_user_name
        "
        );
    }

    /**
     *
     * @deprecated
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *  TODO Delete after release Adjustments
     */
    public function saveAdjustmentsInvoiceOutcomeLegacy(Request $request): JsonResponse
    {
        try {
            $reqData = $request->all();
            $invoiceDetails = DB::table('claims_master')->where('id', $reqData['selectedId'])->first();
            $patientDetails = DB::table('mssql_patients')->where('PatientID', $invoiceDetails->client_id)->first();
            $facility_id = $patientDetails->aoc_facility_id;
            $clientName = $patientDetails->Patient_FirstName . ' ' . $patientDetails->Patient_LastName;
            $currentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId(Auth::user()->id);

            $actionAllowed = 1;
            if ($invoiceDetails->adjustment_status == 'Approved') {
                $canCompleteUsers = H::getHelper("UserPermissionResolver")->getUserListByPermission(
                    'can_complete_adjustments'
                );
                $allowedUsers = array_column($canCompleteUsers, 'id');
                if (empty($canCompleteUsers) || !in_array(Auth::user()->id, $allowedUsers)) {
                    $actionAllowed = 0;
                }
            } else {
                if ($invoiceDetails->assigned_to != Auth::user()->id) {
                    $actionAllowed = 0;
                }
            }
            if ($actionAllowed == 0) {
                $message = 'You are not authorized to perform this action';
                return response()->json(['status' => false, 'message' => $message]);
            }
            H::ExcelLog('facility_id for claim master id: ' . $facility_id, 'saveAdjustmentsInvoiceOutcome.log');
            if (!empty($reqData['adjustmentsInvoiceComplete'])) {
                $updateData = [
                    'adjustment_completed_at' => date('Y-m-d H:i:s'),
                    'adjustment_completed_by' => Auth::user()->id,
                    'assigned_to' => 0,
                    'adjustment_status' => 'Completed',
                ];
                H::ExcelLog(
                    'Approve completed for claim master id: ' . $reqData['selectedId'],
                    'saveAdjustmentsInvoiceOutcome.log'
                );
                H::ExcelLog(print_r($updateData, true), 'saveAdjustmentsInvoiceOutcome.log');
                DB::table('claims_master')->where('id', $reqData['selectedId'])->update($updateData);
            } elseif ($reqData['adjustmentsOutcome'] == 'Approve') {
                $notificationUserId = 0;
                $paymentPostNotification = 0;
                if (LD::checkFeatureFlag('billing-dashboard-adjustments-make-ops-review-mandatory')) {
                    $billerDetails = DB::table('users')->select('billing_team_lead', 'billing_team_manager')->where(
                        'id',
                        $invoiceDetails->biller
                    )->first();
                    if (in_array('Billing Team Lead', $currentRole)) {
                        if ($billerDetails->billing_team_manager) {
                            $assignedToDetails = [
                                'assigned_to' => $billerDetails->billing_team_manager,
                                'adjustment_status' => 'Pending Manager',
                            ];
                        } else {
                            $directorDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                $facility_id,
                                'Director of Revenue Cycle Management'
                            );
                            if (!empty($directorDetail[0])) {
                                $assignedToDetails = [
                                    'assigned_to' => $directorDetail[0],
                                    'adjustment_status' => 'Pending Director',
                                ];
                            } else {
                                $vpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                    $facility_id,
                                    'VP of Revenue Cycle Management'
                                );
                                $assignedToDetails = [
                                    'assigned_to' => $vpDetail[0],
                                    'adjustment_status' => 'Pending VP',
                                ];
                            }
                        }
                        $notificationUserId = $assignedToDetails['assigned_to'];
                        $updateData = [
                            'teamlead_approved_at' => date('Y-m-d H:i:s'),
                            'teamlead_approved_by' => Auth::user()->id,
                            'assigned_to' => $assignedToDetails['assigned_to'],
                            'adjustment_status' => $assignedToDetails['adjustment_status'],
                        ];
                    } elseif (in_array('Billing Manager', $currentRole)) {
                        $directorDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                            $facility_id,
                            'Director of Revenue Cycle Management'
                        );
                        if (!empty($directorDetail[0])) {
                            $assignedToDetails = [
                                'assigned_to' => $directorDetail[0],
                                'adjustment_status' => 'Pending Director',
                            ];
                        } else {
                            $vpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                $facility_id,
                                'VP of Revenue Cycle Management'
                            );
                            $assignedToDetails = ['assigned_to' => $vpDetail[0], 'adjustment_status' => 'Pending VP'];
                        }
                        $notificationUserId = $assignedToDetails['assigned_to'];
                        $updateData = [
                            'manager_approved_at' => date('Y-m-d H:i:s'),
                            'manager_approved_by' => Auth::user()->id,
                            'assigned_to' => $assignedToDetails['assigned_to'],
                            'adjustment_status' => $assignedToDetails['adjustment_status'],
                        ];
                    } elseif (in_array('Director of Revenue Cycle Management', $currentRole)) {
                        $vpId = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                            $facility_id,
                            'VP of Revenue Cycle Management'
                        );
                        $notificationUserId = $vpId[0];
                        $updateData = [
                            'director_approved_at' => date('Y-m-d H:i:s'),
                            'director_approved_by' => Auth::user()->id,
                            'assigned_to' => $vpId[0],
                            'adjustment_status' => 'Pending VP',
                        ];
                    } elseif (in_array('VP of Revenue Cycle Management', $currentRole)) {
                        $stateVpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                            $facility_id,
                            'State VP'
                        );
                        if (!empty($stateVpDetail[0])) {
                            $assignedTo = $stateVpDetail[0];
                        } else {
                            $regionalVpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                $facility_id,
                                'Regional VP'
                            );
                            $assignedTo = $regionalVpDetail[0];
                        }
                        $notificationUserId = $assignedTo;
                        $updateData = [
                            'vp_approved_at' => date('Y-m-d H:i:s'),
                            'vp_approved_by' => Auth::user()->id,
                            'assigned_to' => $assignedTo,
                            'adjustment_status' => 'Pending Ops Review',
                        ];
                    } elseif (in_array('State VP', $currentRole) || in_array('Regional VP', $currentRole)) {
                        $paymentPostNotification = 1;
                        $updateData = [
                            'operations_approved_at' => date('Y-m-d H:i:s'),
                            'operations_approved_by' => Auth::user()->id,
                            'assigned_to' => 0,
                            'adjustment_status' => 'Approved',
                        ];
                    }
                } else {
                    if ($invoiceDetails->adjustment_status == 'Pending Ops Review') {
                        $paymentPostNotification = 1;
                        $updateData = [
                            'operations_approved_at' => date('Y-m-d H:i:s'),
                            'operations_approved_by' => Auth::user()->id,
                            'assigned_to' => 0,
                            'adjustment_status' => 'Approved',
                        ];
                    } else {
                        $billerDetails = DB::table('users')->select('billing_team_lead', 'billing_team_manager')->where(
                            'id',
                            $invoiceDetails->biller
                        )->first();
                        if (in_array('Billing Team Lead', $currentRole)) {
                            if ($billerDetails->billing_team_manager) {
                                $assignedToDetails = [
                                    'assigned_to' => $billerDetails->billing_team_manager,
                                    'adjustment_status' => 'Pending Manager',
                                ];
                            } else {
                                $directorDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                    $facility_id,
                                    'Director of Revenue Cycle Management'
                                );
                                if (!empty($directorDetail[0])) {
                                    $assignedToDetails = [
                                        'assigned_to' => $directorDetail[0],
                                        'adjustment_status' => 'Pending Director',
                                    ];
                                } else {
                                    $vpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                        $facility_id,
                                        'VP of Revenue Cycle Management'
                                    );
                                    $assignedToDetails = [
                                        'assigned_to' => $vpDetail[0],
                                        'adjustment_status' => 'Pending VP',
                                    ];
                                }
                            }
                            $notificationUserId = $assignedToDetails['assigned_to'];
                            $updateData = [
                                'teamlead_approved_at' => date('Y-m-d H:i:s'),
                                'teamlead_approved_by' => Auth::user()->id,
                                'assigned_to' => $assignedToDetails['assigned_to'],
                                'adjustment_status' => $assignedToDetails['adjustment_status'],
                            ];
                        } elseif (in_array('Billing Manager', $currentRole)) {
                            $directorDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                $facility_id,
                                'Director of Revenue Cycle Management'
                            );
                            if (!empty($directorDetail[0])) {
                                $assignedToDetails = [
                                    'assigned_to' => $directorDetail[0],
                                    'adjustment_status' => 'Pending Director',
                                ];
                            } else {
                                $vpDetail = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                    $facility_id,
                                    'VP of Revenue Cycle Management'
                                );
                                $assignedToDetails = [
                                    'assigned_to' => $vpDetail[0],
                                    'adjustment_status' => 'Pending VP',
                                ];
                            }
                            $notificationUserId = $assignedToDetails['assigned_to'];
                            $updateData = [
                                'manager_approved_at' => date('Y-m-d H:i:s'),
                                'manager_approved_by' => Auth::user()->id,
                                'assigned_to' => $assignedToDetails['assigned_to'],
                                'adjustment_status' => $assignedToDetails['adjustment_status'],
                            ];
                        } elseif (in_array('Director of Revenue Cycle Management', $currentRole)) {
                            $vpId = H::getHelper("UserPermissionResolver")->getUserIdByFacilityAndRole(
                                $facility_id,
                                'VP of Revenue Cycle Management'
                            );
                            $notificationUserId = $vpId[0];
                            $updateData = [
                                'director_approved_at' => date('Y-m-d H:i:s'),
                                'director_approved_by' => Auth::user()->id,
                                'assigned_to' => $vpId[0],
                                'adjustment_status' => 'Pending VP',
                            ];
                        } elseif (in_array('VP of Revenue Cycle Management', $currentRole)) {
                            $paymentPostNotification = 1;
                            $updateData = [
                                'vp_approved_at' => date('Y-m-d H:i:s'),
                                'vp_approved_by' => Auth::user()->id,
                                'assigned_to' => 0,
                                'adjustment_status' => 'Approved',
                            ];
                        }
                    }
                }
                H::ExcelLog(
                    'Approve Update for claim master id: ' . $reqData['selectedId'],
                    'saveAdjustmentsInvoiceOutcome.log'
                );
                H::ExcelLog(print_r($updateData, true), 'saveAdjustmentsInvoiceOutcome.log');
                DB::table('claims_master')->where('id', $reqData['selectedId'])->update($updateData);
                if ($notificationUserId != 0) {
                    H::ExcelLog(
                        'notificationUserId for user : ' . $notificationUserId,
                        'saveAdjustmentsInvoiceOutcome.log'
                    );
                    $title = 'New adjustment assigned to you for approval. Please review in the adjustment bucket';
                    $notificationData = [
                        'user_id' => $notificationUserId,
                        'title' => $title,
                        'type' => 'invoice',
                        'related_item' => $reqData['selectedId'],
                        'notes' => $title,
                    ];
                    H::ExcelLog(
                        'Notifications for claim master id: ' . $reqData['selectedId'],
                        'saveAdjustmentsInvoiceOutcome.log'
                    );
                    H::ExcelLog(print_r($notificationData, true), 'saveAdjustmentsInvoiceOutcome.log');
                    Notifications::addNotification($notificationData);
                }
                if ($paymentPostNotification == 1) {
                    H::ExcelLog('paymentPostNotification', 'saveAdjustmentsInvoiceOutcome.log');
                    $title = 'New adjustment assigned to you for approval. Please review in the adjustment bucket';
                    $paymentPosterUsers = H::getHelper('ConstantHelper')->getUsersByRoles(['Payment Poster']);
                    $notificationUser = array_column($paymentPosterUsers, 'value');
                    H::ExcelLog('notificationUser ', 'saveAdjustmentsInvoiceOutcome.log');
                    H::ExcelLog(print_r($notificationUser, true), 'saveAdjustmentsInvoiceOutcome.log');
                    foreach ($notificationUser as $entityId) {
                        $notificationData = [
                            'user_id' => $entityId,
                            'title' => $title,
                            'type' => 'invoice',
                            'related_item' => $reqData['selectedId'],
                            'notes' => $title,
                        ];
                        Notifications::addNotification($notificationData);
                    }
                }
                $logData = [];
                $logData['entity'] = 'invoice';
                $logData['entity_id'] = $reqData['selectedId'];
                $logData['notes'] = Auth::user(
                    )->name . ' has approved the adjustment request for invoice ' . $reqData['invoiceNumber'] . '. Comments: ' . $reqData['adjustmentsInvoiceComments'];
                H::getHelper("ActivityNotes")->setActivityNotes($logData);
            } elseif ($reqData['adjustmentsOutcome'] == 'Reject') {
                if (in_array('Billing Team Lead', $currentRole)) {
                    $rejectedLevel = 'Team Lead';
                } elseif (in_array('Billing Manager', $currentRole)) {
                    $rejectedLevel = 'Manager';
                } elseif (in_array('Director of Revenue Cycle Management', $currentRole)) {
                    $rejectedLevel = 'Director';
                } elseif (in_array('VP of Revenue Cycle Management', $currentRole)) {
                    $rejectedLevel = 'VP';
                } else {
                    $rejectedLevel = 'Operations';
                }
                $updateData = [
                    'rejected_at' => date('Y-m-d H:i:s'),
                    'rejected_by' => Auth::user()->id,
                    'adjustment_status' => 'Rejected',
                    'assigned_to' => 0,
                    'rejected_level' => $rejectedLevel,
                    'followedup_at' => null,
                    'followedup_by' => null,
                ];
                DB::table('claims_master')->where('id', $reqData['selectedId'])->update($updateData);
                $notificationUser = [];
                if ($invoiceDetails->teamlead_approved_by != null) {
                    $notificationUser[] = $invoiceDetails->teamlead_approved_by;
                }
                if ($invoiceDetails->manager_approved_by != null) {
                    $notificationUser[] = $invoiceDetails->manager_approved_by;
                }
                if ($invoiceDetails->director_approved_by != null) {
                    $notificationUser[] = $invoiceDetails->director_approved_by;
                }
                if ($invoiceDetails->vp_approved_by != null) {
                    $notificationUser[] = $invoiceDetails->vp_approved_by;
                }
                if ($invoiceDetails->operations_approved_by != null) {
                    $notificationUser[] = $invoiceDetails->operations_approved_by;
                }
                $notificationUser[] = $invoiceDetails->biller;
                $title = Auth::user(
                    )->name . ' has rejected adjustment request for ' . $clientName . '. Please review details on invoice.';
                foreach ($notificationUser as $entityId) {
                    $notificationData = [
                        'user_id' => $entityId,
                        'title' => $title,
                        'type' => 'invoice',
                        'related_item' => $reqData['selectedId'],
                        'notes' => $title,
                    ];
                    Notifications::addNotification($notificationData);
                }
                $logData = [];
                $logData['entity'] = 'invoice';
                $logData['entity_id'] = $reqData['selectedId'];
                $logData['notes'] = Auth::user(
                    )->name . ' has rejected the adjustment request for invoice ' . $reqData['invoiceNumber'] . '. Comments: ' . $reqData['adjustmentsInvoiceComments'];
                H::getHelper("ActivityNotes")->setActivityNotes($logData);
            } elseif ($reqData['adjustmentsOutcome'] == 'Request Ops Review') {
                $updateData = [
                    'adjustment_status' => 'Pending Ops Review',
                    'assigned_to' => $reqData['requestOpsReview'],
                ];
                if (LD::checkFeatureFlag('add-adjustments-details-on-invoice-details-page')) {
                    $updateData['requested_ops_approval_date'] = date('Y-m-d');
                }
                DB::table('claims_master')->where('id', $reqData['selectedId'])->update($updateData);
                $title = 'New adjustment assigned to you for approval. Please review in the adjustment bucket';
                $notificationData = [
                    'user_id' => $reqData['requestOpsReview'],
                    'title' => $title,
                    'type' => 'invoice',
                    'related_item' => $reqData['selectedId'],
                    'notes' => $title,
                ];
                Notifications::addNotification($notificationData);
                $opsReviewUserDetails = DB::table('users')->where('id', $reqData['requestOpsReview'])->first();
                $logData = [];
                $logData['entity'] = 'invoice';
                $logData['entity_id'] = $reqData['selectedId'];
                $logData['notes'] = Auth::user(
                    )->name . ' has requested an operations review from ' . $opsReviewUserDetails->name . ' regarding the adjustment of invoice ' . $reqData['invoiceNumber'] . '. Comments: ' . $reqData['adjustmentsInvoiceComments'];
                H::getHelper("ActivityNotes")->setActivityNotes($logData);
            }
            return response()->json(['status' => true, 'message' => 'Outcome updated successfully']);
        } catch (\Exception $e) {
            H::ExcelLog('saveAdjustmentsInvoiceOutcome: ==' . $e->getMessage(), 'saveAdjustmentsInvoiceOutcome.log');
            return response()->json(['status' => false, 'message' => "Something went wrong, please try again."]);
        }
    }

    public function saveAdjustmentsInvoiceOutcome(Request $request): JsonResponse
    {
        $reqData = $request->all();

        try {
            if (($reqData['adjustmentsOutcome'] ?? null) === 'Approve') {
                $message = $this->workflowService->approve($reqData);

                return response()->json([
                    'status' => true,
                    'message' => $message,
                ]);
            }

            if (($reqData['adjustmentsOutcome'] ?? null) === 'Reject') {
                $message = $this->workflowService->reject($reqData);

                return response()->json([
                    'status' => true,
                    'message' => $message,
                ]);
            }

            if (($reqData['adjustmentsOutcome'] ?? null) === 'Request Ops Review') {
                $message = $this->workflowService->requestOpsReview($reqData);

                return response()->json([
                    'status' => true,
                    'message' => $message,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Unsupported adjustment action for new workflow',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        catch (MissingArSupervisorException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        catch (Throwable $e) {
            H::ExcelLog(
                'saveAdjustmentsInvoiceOutcome new flow: ' . $e->getMessage(),
                'saveAdjustmentsInvoiceOutcome.log'
            );

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
