<?php

namespace App\Http\Resources\Policy;

use App\Enums\ConsentTemplates\ConsentTypeEnum;
use App\Models\ConsentTemplate;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PolicyIndexResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Collection $templates */
        $templates = $this->resource->keyBy('type');

        return [
            'terms' => $this->mapConsent(
                $templates->get(ConsentTypeEnum::TERMS_AND_CONDITIONS->value)
            ),
            'privacy' => $this->mapConsent(
                $templates->get(ConsentTypeEnum::PRIVACY_POLICY->value)
            ),
        ];
    }

    private function mapConsent(?ConsentTemplate $template): array
    {
        if (! $template) {
            return [
                'version' => null,
                'content' => null,
            ];
        }

        return [
            'version' => $template->version,
            'content' => $template->content,
        ];
    }
}
