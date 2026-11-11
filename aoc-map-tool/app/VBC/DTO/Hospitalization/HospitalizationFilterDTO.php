<?php

namespace App\VBC\DTO\Hospitalization;

final readonly class HospitalizationFilterDTO
{
    public const SORT_ADMISSION_DATE = 'admission_date';
    public const SORT_DISCHARGE_DATE = 'discharge_date';
    public const SORT_STATUS = 'status';
    public const SORT_LEVEL = 'level';
    public const SORT_TYPE = 'type';
    public const SORT_PATIENT_NAME = 'patient_name';
    public const SORT_PATIENT_ACCOUNT = 'patient_account';
    public const SORT_TRACKER_ID = 'tracker_id';
    public const SORT_ASSIGNEE_NAME = 'assignee_name';
    public const SORT_BRANCH = 'branch';
    public const SORT_LAST_FOLLOW_UP = 'last_follow_up';

    public const ALL_SORT_COLUMNS = [
        self::SORT_ADMISSION_DATE,
        self::SORT_DISCHARGE_DATE,
        self::SORT_STATUS,
        self::SORT_LEVEL,
        self::SORT_TYPE,
        self::SORT_PATIENT_NAME,
        self::SORT_PATIENT_ACCOUNT,
        self::SORT_TRACKER_ID,
        self::SORT_ASSIGNEE_NAME,
        self::SORT_BRANCH,
        self::SORT_LAST_FOLLOW_UP,
    ];

    public function __construct(
        public ?array $state = [],
        public array $branchIds = [],
        public array $regionIds = [],
        public array $assigneeIds = [],
        public array $statuses = [],
        public array $levels = [],
        public array $facilityNames = [],
        public ?string $exportType = null,
        public ?string $search = null,
        public bool $meMode = false,
        public int $page = 1,
        public int $perPage = 10,
        public string $sortBy = 'admission_date',
        public string $sortDir = 'desc',
        public ?string $admissionDateFrom = null,
        public ?string $admissionDateTo = null,
        // EncounterTypeEnum values (IP/ED) — empty means "any type". The Triage "ED Visits" bucket
        // always sends ED; every other caller leaves it empty.
        public array $encounterTypes = [],
    ) {
    }
}
