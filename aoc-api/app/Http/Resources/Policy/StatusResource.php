<?php

namespace App\Http\Resources\Policy;

use App\Enums\ConsentTemplates\ConsentTypeEnum;
use App\Models\ConsentTemplate;
use App\Models\UserConsent;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray($request): array
    {
        $map = [
            'terms' => ConsentTypeEnum::TERMS_AND_CONDITIONS->value,
            'privacy' => ConsentTypeEnum::PRIVACY_POLICY->value,
        ];

        foreach ($map as $key => $type) {
            $activeTemplate = $this->resource['templates']->keyBy('type')->get($type);

            /** @var UserConsent|null $lastAccepted */
            $lastAccepted = $this->resource['userConsents']
                ->get($type)
                ?->sortByDesc('accepted_at')
                ->first();

            $response[$key] = $this->map(
                $activeTemplate,
                $lastAccepted
            );
        }

        return $response;
    }

    public function map(
        ?ConsentTemplate $activeTemplate,
        ?UserConsent $acceptedConsent
    ): array {
        if (! $activeTemplate || ! $acceptedConsent) {
            return [
                'accepted' => false,
                'accepted_version' => null,
                'is_latest' => false,
            ];
        }

        return [
            'accepted' => true,
            'accepted_version' => $acceptedConsent->consent_version,
            'is_latest' => $acceptedConsent->consent_version === $activeTemplate->version,
        ];
    }
}
