<?php

namespace App\DTO;

class VerifyOtpDTO
{
    /**
     * @param string|null $mobileNumber
     * @param string|null $email
     * @param string|null $otp
     */
    public function __construct(
        public ?string $mobileNumber,
        public ?string $email,
        public ?string $otp,
    )
    {
    }
}
