<?php

namespace App\Services\Notification\NTH;

use App\Enums\NotificationTypeEnum;
use App\Events\NTH\NTHScheduleChanged;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\UserPermissionResolver;
use App\MssqlPatients;
use App\NeedToHireAccounts;
use App\Repositories\NeedToHireAccounts\NeedToHireAccountsReadRepository;
use App\Repositories\Notifications\NotificationWriteRepository;
use App\Services\Patient\PatientService;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NTHScheduleChangeNotificationService
{
    private const MESSAGE_PATTERN = 'NTH hours %s for Client %s';

    private UserPermissionResolver $permissionResolver;
    private NotificationWriteRepository $notificationWriteRepository;
    private NeedToHireAccountsReadRepository $needToHireAccountsReadRepository;

    public function __construct(
        UserPermissionResolver $permissionResolver,
        NotificationWriteRepository $notificationWriteRepository,
        NeedToHireAccountsReadRepository $needToHireAccountsReadRepository,
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->notificationWriteRepository = $notificationWriteRepository;
        $this->needToHireAccountsReadRepository = $needToHireAccountsReadRepository;
    }

    public function process(NTHScheduleChanged $event, Collection $careTeam): void
    {
        $schedule = $this->needToHireAccountsReadRepository->getAccountsFilteredByNthAccountID(
            $event->getAocNthAccountsID()
        );
        $permissionID = $this->permissionResolver->getPermissionIdByPermissionName('notification.list');
        $filterRecipients = $this->filterRecipients($careTeam, $permissionID);
        $message = $this->getMessage($event, $schedule->full_name);
        $this->notificationWriteRepository->createNotification(
            $this->buildNotificationPayload($filterRecipients, $schedule->profileID, $message)
        );
    }

    private function getMessage(NTHScheduleChanged $event, string $fullName): string
    {
        $mode = 'added';

        if ($event->isEditMode()) {
            $mode = 'updated';
        }

        if ($event->isDeleteMode()) {
            $mode = 'removed';
        }

        return sprintf(self::MESSAGE_PATTERN, $mode, $fullName);
    }

    private function filterRecipients(Collection $recipients, int $permissionID): array
    {
        $filteredRecipients = [];
        $recipients = $recipients->groupBy('role');

        foreach ($recipients as $users) {
            foreach ($users as $user) {
                if ($this->isValidRecipient($permissionID, (int)$user['id'])) {
                    $filteredRecipients[] = $user['id'];
                };
            }
        }

        return $filteredRecipients;
    }

    private function isValidRecipient(int $permissionID, int $userID): bool
    {
        if ($userID !== 0) {
            return $this->permissionResolver->isUserHasPermission($userID, $permissionID)
                || $this->permissionResolver->isRoleHasPermissionNew('notification.list', $userID);
        }

        return false;
    }

    private function buildNotificationPayload(array $usersIds, int $profileID, string $message): array
    {
        $notifications = [];

        foreach ($usersIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'type' => NotificationTypeEnum::NTH_SCHEDULE_CHANGE_TYPE->value,
                'related_item' => $profileID,
                'title' => 'NTH Schedule changed',
                'notes' => $message,
                'created_at' => now(),
            ];
        }

        return $notifications;
    }
}
