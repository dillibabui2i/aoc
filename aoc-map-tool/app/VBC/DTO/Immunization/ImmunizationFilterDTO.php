<?php

namespace App\VBC\DTO\Immunization;

final readonly class ImmunizationFilterDTO
{
    public const SORT_CREATED_AT = 'created_at';
    public const SORT_PATIENT_NAME = 'patient_name';
    public const SORT_PATIENT_ACCOUNT = 'patient_account';
    public const SORT_BRANCH = 'branch';
    public const SORT_PAYER = 'payer';
    public const SORT_VACCINE_NAME = 'vaccine_name';
    public const SORT_DOSE = 'dose';
    public const SORT_EARLIEST_DATE = 'earliest_date';
    public const SORT_DUE_DATE = 'due_date';
    public const SORT_STATUS = 'status';
    public const SORT_ASSIGNEE_NAME = 'assignee_name';
    public const SORT_OUTCOME = 'outcome';

    public const ALL_SORT_COLUMNS = [
        self::SORT_CREATED_AT,
        self::SORT_PATIENT_NAME,
        self::SORT_PATIENT_ACCOUNT,
        self::SORT_BRANCH,
        self::SORT_PAYER,
        self::SORT_VACCINE_NAME,
        self::SORT_DOSE,
        self::SORT_EARLIEST_DATE,
        self::SORT_DUE_DATE,
        self::SORT_STATUS,
        self::SORT_ASSIGNEE_NAME,
        self::SORT_OUTCOME,
    ];

    public function __construct(
        public ?array $state = [],
        public array $branchIds = [],
        public array $vaccineCvxCodes = [],
        public array $statuses = [],
        public array $payers = [],
        public ?string $search = null,
        public int $page = 1,
        public int $perPage = 10,
        public string $sortBy = self::SORT_CREATED_AT,
        public string $sortDir = 'desc',
        public ?string $earliestDateFrom = null,
        public ?string $earliestDateTo = null,
        public bool $meMode = false,
        public array $outcomes = [],
    ) {
    }
}
