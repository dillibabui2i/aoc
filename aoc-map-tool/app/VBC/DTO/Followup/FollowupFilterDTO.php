<?php

namespace App\VBC\DTO\Followup;

final readonly class FollowupFilterDTO
{
    public const SORT_FOLLOWUP_DATE = 'followup_date';
    public const SORT_PATIENT_NAME = 'patient_name';
    public const SORT_PATIENT_ID = 'patient_account';
    public const SORT_FACILITY_NAME = 'facility_name';
    public const SORT_ASSIGNEE_NAME = 'assignee_name';

    public const ALL_SORT_COLUMNS = [
        self::SORT_FOLLOWUP_DATE,
        self::SORT_PATIENT_NAME,
        self::SORT_PATIENT_ID,
        self::SORT_FACILITY_NAME,
        self::SORT_ASSIGNEE_NAME,
    ];

    public function __construct(
        public array $state = [],
        public array $branchIds = [],
        public array $regionIds = [],
        public array $assigneeIds = [],
        public array $statuses = [],
        public array $encounterTypes = [],
        public ?string $search = null,
        public bool $meMode = false,
        public int $page = 1,
        public int $perPage = 10,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
    ) {
    }
}
