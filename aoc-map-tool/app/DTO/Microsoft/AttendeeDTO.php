<?php

namespace App\DTO\Microsoft;

class AttendeeDTO
{
    public function __construct(private string $email, private string $fullName, private string $type = 'required')
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
