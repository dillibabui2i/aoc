<?php

namespace App\DTO;

class SendLoginOtpDTO
{
    /**
     * @param string|null $mobileNumber
     * @param string|null $email
     */
    public function __construct(
        public ?string $mobileNumber,
        public ?string $email,
    )
    {
    }
}
