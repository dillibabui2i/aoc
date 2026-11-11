<?php

namespace App\DTO;

class CreateOtpLogDTO
{
    /**
     * @param string|null $mobileNumber
     * @param string|null $email
     * @param string $otp
     * @param string $sendAt
     * @param string $expiresAt
     * @param int $resendCount
     * @param int $attemptCount
     * @param int $limitEventsCount
     * @param string|null $invalidatedAt
     * @param string|null $invalidatedReason
     */
    public function __construct(
        public ?string $mobileNumber,
        public ?string $email,
        public string $otp,
        public string $sendAt,
        public string $expiresAt,
        public int    $resendCount,
        public int    $attemptCount = 0,
        public int    $limitEventsCount = 0,
        public ?string $invalidatedAt = null,
        public ?string $invalidatedReason = null,
    )
    {
    }

    /**
     * @return string|null
     */
    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getOtp(): string
    {
        return $this->otp;
    }

    /**
     * @return string
     */
    public function getSendAt(): string
    {
        return $this->sendAt;
    }

    /**
     * @return string
     */
    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    /**
     * @return int
     */
    public function getResendCount(): int
    {
        return $this->resendCount;
    }

    /**
     * @return int
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * @return int
     */
    public function getLimitEventsCount(): int
    {
        return $this->limitEventsCount;
    }

    /**
     * @return string|null
     */
    public function getInvalidatedAt(): ?string
    {
        return $this->invalidatedAt;
    }

    /**
     * @return string|null
     */
    public function getInvalidatedReason(): ?string
    {
        return $this->invalidatedReason;
    }
}
