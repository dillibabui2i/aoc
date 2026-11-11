<?php

namespace App\Repositories\UserConsent;

use App\Models\ConsentTemplate;
use App\Models\ExternalUsers;
use App\Models\UserConsent;

class UserConsentWriteRepository
{
    /**
     * @param ExternalUsers $externalUser
     * @param ConsentTemplate $template
     * @return void
     */
    public function accept(ExternalUsers $externalUser, ConsentTemplate $template): void
    {
        UserConsent::firstOrCreate(
            [
                'external_user_id' => $externalUser->id,
                'consent_template_id' => $template->id,
                'consent_version' => $template->version,
            ],
            [
                'accepted_at' => now(),
                'source' => 'mobile',
            ]
        );
    }
}
