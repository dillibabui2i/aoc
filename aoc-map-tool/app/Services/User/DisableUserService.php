<?php

namespace App\Services\User;

use App\Enums\UserStatus;
use App\Events\User\UserHasTrachVentClassesDisabled;
use App\Repositories\TrachVentClass\TrachVentClassReadRepository;
use App\TrachVentClass;
use App\TrachVentClassOccurrence;
use App\User;
use Illuminate\Support\Facades\DB;

final readonly class DisableUserService
{
    public function __construct(private readonly TrachVentClassReadRepository $trachVentClassReadRepository)
    {
    }

    public function execute(User $user): bool
    {
        $futureClasses = $this->trachVentClassReadRepository->getInstructorFutureClasses($user->id)->toArray();

        if (empty($futureClasses)) {
            $user->update(['status' => UserStatus::DISABLED->value]);

            return true;
        }

        DB::transaction(function () use ($user, $futureClasses) {
            $user->update(['status' => UserStatus::DISABLED->value]);

            $occurrenceIds = array_column($futureClasses, 'id');
            $classIds = array_unique(array_column($futureClasses, 'class_id'));

            TrachVentClassOccurrence::whereIn('id', $occurrenceIds)
                ->where('instructor_id', $user->id)
                ->update(['instructor_id' => null]);
            TrachVentClass::whereIn('id', $classIds)
                ->where('instructor_id', $user->id)
                ->update(['instructor_id' => null]);
        });

        event(new UserHasTrachVentClassesDisabled($user, $futureClasses));

        return true;
    }
}
