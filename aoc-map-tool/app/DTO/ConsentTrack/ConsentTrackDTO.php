<?php

namespace App\DTO\ConsentTrack;

readonly class ConsentTrackDTO
{
    /**
     * @param string $type
     * @param string $language
     * @param string $content
     * @param string $status
     * @param string|null $id
     */
    public function __construct(
        private readonly string $type,
        private readonly string $language,
        private readonly string $content,
        private readonly string $status,
        private readonly ?string $id,
    )
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
