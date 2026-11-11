<?php

namespace App\DTO\Offer;

class OfferAttachmentDTO
{
    public function __construct(private string $path, private string $name)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Name the recipient sees in the email, the file on disk is prefixed to keep it unique.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
