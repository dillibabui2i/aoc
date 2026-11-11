<?php

namespace App\Repositories\User;

use App\Enums\UserStatus;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserReadRepository
{
    public function __construct(private User $user)
    {
    }

    /** @param array<int> $facilityIds */
    public function getByRoleAndFacility(int $role, array $facilityIds): Collection
    {
        return $this->user->newQuery()->where(function ($query) use ($facilityIds) {
            foreach ($facilityIds as $facilityId) {
                $query->orWhereRaw('FIND_IN_SET(?, facilityid)', [$facilityId]);
            }
        })
            ->whereHas('roles', fn (Builder $q) => $q->where('roles.id', $role))
            ->get();
    }

    public function getActiveInstructors(): Collection
    {
        return $this->user->newQuery()->select('id', 'name')
            ->where('status', UserStatus::ACTIVE)
            ->where('is_instructor', 1)
            ->get();
    }

    public function getInstructorsByIds(array $ids): Collection
    {
        return $this->user->newQuery()->select('id', 'name')
            ->whereIn('id', $ids)
            ->get();
    }

    /** @param array<int> $roleIds */
    public function getActiveByRoles(array $roleIds): Collection
    {
        return $this->user->newQuery()->select('id', 'name')
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('roles.id', $roleIds))
            ->orderBy('name')
            ->get();
    }

    /** @param array<int> $roleIds */
    public function userHasAnyRole(int $userId, array $roleIds): bool
    {
        return $this->user->newQuery()
            ->where('id', $userId)
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('roles.id', $roleIds))
            ->exists();
    }
}
