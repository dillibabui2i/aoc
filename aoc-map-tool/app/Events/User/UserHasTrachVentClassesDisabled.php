<?php

namespace App\Events\User;

use App\User;

final readonly class UserHasTrachVentClassesDisabled
{
    /**
     * @param array<array> $futureClasses
     */
    public function __construct(public User $user, public array $futureClasses)
    {
    }
}
