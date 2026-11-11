<?php

namespace App\DTO\Dashboard\Buckets\Jobvite;

/**
 * Class CandidateApplicationsDTO
 *
 * @package App\DTO\Dashboard\Buckets\Jobvite
 */
class CandidateApplicationsDTO
{
    /**
     * @param bool $hasJobviteAddCandidateNoteFlag
     * @param bool $hasAddPetFilterToJobvitePetBucket
     * @param int $authUserId
     * @param string $authUserEmail
     * @param array $filters
     * @param string $status
     * @param string $parentBucket
     */
    public function __construct(
        public bool $hasJobviteAddCandidateNoteFlag,
        public bool $hasAddPetFilterToJobvitePetBucket,
        public int $authUserId,
        public string $authUserEmail,
        public array $filters,
        public string $status,
        public bool $isTherapyClinicalManager = false,
    ) {
    }

    /**
     * @return bool
     */
    public function hasJobviteAddCandidateNoteFlag(): bool
    {
        return $this->hasJobviteAddCandidateNoteFlag;
    }

    /**
     * @return bool
     */
    public function hasAddPetFilterToJobvitePetBucketFlag(): bool
    {
        return $this->hasAddPetFilterToJobvitePetBucket;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return bool
     */
    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    /**
     * @return bool
     */
    public function hasAuthUserFilter(): bool
    {
        return !empty($this->filters[0]['value']) && $this->filters[0]['name'] == 'default';
    }

    /**
     * @return string
     */
    public function getAuthUserEmail(): string
    {
        return $this->authUserEmail;
    }

    /**
     * @return int
     */
    public function getAuthUserId(): int
    {
        return $this->authUserId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getParentBucket(): string
    {
        return $this->status;
    }

    public function isNursingParentBucket(): bool
    {
        return !$this->isTherapyParentBucket();
    }

    public function isTherapyParentBucket(): bool
    {
        return str_contains($this->getParentBucket(), 'therap');
    }

    public function isTherapyCareManagerRole(): bool
    {
        return $this->isTherapyClinicalManager;
    }
}
