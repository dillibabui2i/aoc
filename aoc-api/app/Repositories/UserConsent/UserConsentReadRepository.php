<?php

namespace App\Repositories\UserConsent;

use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Collection;

class UserConsentReadRepository
{
    /**
     * @param int $userId
     * @return Collection
     */
    public function getByUser(int $userId): Collection
    {
        return UserConsent::query()
            ->where('external_user_id', $userId)
            ->with('template:id,type,version')
            ->get()
            ->groupBy(fn ($consent) => $consent->template->type);
    }
}
