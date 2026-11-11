<?php

namespace App\Repositories\ExternalUser;

use App\Models\ExternalUsers;

class ExternalUserReadRepository
{
    /**
     * @param int $id
     * @return ExternalUsers
     */
    public function getById(int $id): ExternalUsers
    {
        return ExternalUsers::find($id);
    }
}
