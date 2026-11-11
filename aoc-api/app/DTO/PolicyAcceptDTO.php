<?php

namespace App\DTO;

class PolicyAcceptDTO
{
    /**
     * @param bool $termsAccepted
     * @param bool $privacyAccepted
     * @param int $userId
     * @param string $language
     */
    public function __construct(
       public bool $termsAccepted,
       public bool $privacyAccepted,
       public int $userId,
       public string $language
    )
    {
    }

    /**
     * @return bool
     */
    public function isPrivacyAccepted(): bool
    {
        return $this->privacyAccepted;
    }

    /**
     * @return bool
     */
    public function isTermsAccepted(): bool
    {
        return $this->termsAccepted;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
}
