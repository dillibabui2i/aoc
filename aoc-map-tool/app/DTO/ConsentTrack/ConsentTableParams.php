<?php

namespace App\DTO\ConsentTrack;

readonly class ConsentTableParams
{
    public function __construct(
        public readonly ?string $draw,
        public readonly array   $search = [],
        public readonly array   $order = [],
        public readonly int     $start = 0,
        public readonly int     $length = 10,
        public readonly array   $columns = [],
        public readonly array   $consent_version = [],
        public readonly array   $consent_type = [],
        public readonly array   $consent_status = [],
    )
    {
    }

    /**
     * @return string|null
     */
    public function getDraw(): ?string
    {
        return $this->draw;
    }

    /**
     * @return array
     */
    public function getSearch(): array
    {
        return $this->search;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getConsentVersion(): array
    {
        return $this->consent_version;
    }

    /**
     * @return array
     */
    public function getConsentType(): array
    {
        return $this->consent_type;
    }

    /**
     * @return array
     */
    public function getConsentStatus(): array
    {
        return $this->consent_status;
    }
}
