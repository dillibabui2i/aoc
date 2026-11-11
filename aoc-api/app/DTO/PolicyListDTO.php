<?php

namespace App\DTO;

class PolicyListDTO
{
    /**
     * @param string $language
     */
    public function __construct(public string $language)
    {
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
}
