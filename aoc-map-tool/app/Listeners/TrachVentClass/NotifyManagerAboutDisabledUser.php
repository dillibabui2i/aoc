<?php

namespace App\Listeners\TrachVentClass;

use App\Enums\RolesEnum;
use App\Events\User\UserHasTrachVentClassesDisabled;
use App\Helpers\Notifications;
use App\Repositories\User\UserReadRepository;

final readonly class NotifyManagerAboutDisabledUser
{
    public function __construct(private UserReadRepository $userReadRepository)
    {
    }

    public function handle(UserHasTrachVentClassesDisabled $event): void
    {
        $notifierIds = [];
        if ($event->user->recruiting_manager) {
            $notifierIds[] = $event->user->recruiting_manager;
        }
        $userFacilities = explode(',', $event->user->facilityid ?? '');

        if (empty($notifierIds) && !empty($userFacilities)) {
            $notifierIds = $this->userReadRepository->getByRoleAndFacility(
                RolesEnum::DIRECTOR_OF_BRANCH_OPERATIONS->value,
                $userFacilities,
            )->pluck('id')->toArray();
        }

        foreach ($notifierIds as $notifierId) {
            Notifications::addNotification([
                'user_id' => $notifierId,
                'title' => sprintf(
                    'User %s was disabled and their assigned classes should be reassigned to another instructior.',
                    $event->user->name
                ),
                'notes' => view('user.userTrachVentClasses', ['classes' => $event->futureClasses])->render(),
                'type' => 'disabledUserHasTrachVentClasses',
            ]);
        }
    }
}
