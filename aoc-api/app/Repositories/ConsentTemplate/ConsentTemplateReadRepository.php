<?php

namespace App\Repositories\ConsentTemplate;

use App\Enums\ConsentTemplates\ConsentStatusEnum;
use App\Enums\ConsentTemplates\ConsentTypeEnum;
use App\Models\ConsentTemplate;
use Illuminate\Database\Eloquent\Collection;

class ConsentTemplateReadRepository
{

    /**
     * @param string $language
     * @return Collection
     */
    public function getActiveTemplates(string $language): Collection
    {
        return ConsentTemplate::whereIn('type', [
            ConsentTypeEnum::PRIVACY_POLICY->value,
            ConsentTypeEnum::TERMS_AND_CONDITIONS->value
        ])->where('status', ConsentStatusEnum::PUBLISHED->value)
            ->where('language', $language)->get();
    }
}
