<?php

namespace App\DTO\NeedToHire;

readonly class NeedToHireScheduleByClientIdDTO
{
    /**
     * @param string $clientId
     */
    public function __construct(private readonly string $clientId)
    {}

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }
}
