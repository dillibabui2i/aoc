<?php

namespace App\DTO\GpCalculator;

readonly class RequestPayerRateDTO
{
    public function __construct(
        private readonly object $clientInfo,
        private readonly string $paymentSourceName,
        private readonly string $serviceCodeName,
        private readonly string $discipline,
        private readonly bool $isSpecialized
    ) {
    }

    public function getPaymentSourceName() : string
    {
        return $this->paymentSourceName;
    }

    public function getClientInfo() : object
    {
        return $this->clientInfo;
    }

    public function getServiceCodeName() : string
    {
        return $this->serviceCodeName;
    }

    public function getDiscipline() : string
    {
        return $this->discipline;
    }

    public function isSpecialized() : bool
    {
        return $this->isSpecialized;
    }
}
