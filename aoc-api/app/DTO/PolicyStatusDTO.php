<?php

namespace App\DTO;

class PolicyStatusDTO
{
    /**
     * @param int $userId
     * @param string $language
     */
    public function __construct(public int $userId, public string $language)
    {
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
