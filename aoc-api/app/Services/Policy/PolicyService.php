<?php

namespace App\Services\Policy;

use App\DTO\PolicyAcceptDTO;
use App\DTO\PolicyListDTO;
use App\DTO\PolicyStatusDTO;
use App\Enums\ConsentTemplates\ConsentTypeEnum;
use App\Models\ConsentTemplate;
use App\Repositories\ConsentTemplate\ConsentTemplateReadRepository;
use App\Repositories\ExternalUser\ExternalUserReadRepository;
use App\Repositories\UserConsent\UserConsentReadRepository;
use App\Repositories\UserConsent\UserConsentWriteRepository;
use Illuminate\Database\Eloquent\Collection;

class PolicyService
{
    /**
     * @param ConsentTemplateReadRepository $consentReadRepository
     * @param ExternalUserReadRepository $externalUserReadRepository
     * @param UserConsentReadRepository $userConsentReadRepository
     * @param UserConsentWriteRepository $userConsentWriteRepository
     */
    public function __construct(
        protected ConsentTemplateReadRepository $consentReadRepository,
        protected ExternalUserReadRepository $externalUserReadRepository,
        protected UserConsentReadRepository $userConsentReadRepository,
        protected UserConsentWriteRepository $userConsentWriteRepository,
    )
    {
    }

    /**
     * @param PolicyListDTO $params
     * @return Collection
     */
    public function getLatestPolicies(PolicyListDTO $params): Collection
    {
        return $this->consentReadRepository->getActiveTemplates($params->getLanguage());
    }

    /**
     * @param PolicyAcceptDTO $data
     * @return void
     */
    public function acceptPolicy(PolicyAcceptDTO $data): void
    {
        $externalUser = $this->externalUserReadRepository->getById($data->getUserId());
        $templates = $this->consentReadRepository->getActiveTemplates($data->getLanguage());

        $map = [
            'acceptedTC' => ConsentTypeEnum::TERMS_AND_CONDITIONS->value,
            'acceptedPP' => ConsentTypeEnum::PRIVACY_POLICY->value,
        ];

        foreach ($map as $type) {
            if (!$data->isPrivacyAccepted()) {
                continue;
            }

            if (!$data->isTermsAccepted()) {
                continue;
            }

            /** @var ConsentTemplate|null $template */
            $template = $templates->keyBy('type')->get($type);

            if (!$template) {
                continue;
            }

            $this->userConsentWriteRepository->accept($externalUser, $template);
        }
    }

    /**
     * @param PolicyStatusDTO $data
     * @return array
     */
    public function checkStatus(PolicyStatusDTO $data): array
    {
        $templates = $this->consentReadRepository->getActiveTemplates($data->getLanguage());;
        $userConsents = $this->userConsentReadRepository->getByUser($data->getUserId());

        return [
            'templates' => $templates,
            'userConsents' => $userConsents,
        ];
    }
}
