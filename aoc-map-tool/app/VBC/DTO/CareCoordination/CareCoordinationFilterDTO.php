<?php

namespace App\VBC\DTO\CareCoordination;

use App\VBC\Enum\Encounter\EncounterTypeEnum;

final readonly class CareCoordinationFilterDTO
{
    public const SORT_CREATED_AT = 'created_at';
    public const SORT_COORDINATION_TYPE = 'coordination_type';
    public const SORT_DUE_DATE = 'due_date';
    public const SORT_CLIENT_NAME = 'patient_name';
    public const SORT_CLIENT_ID = 'patient_account';
    public const SORT_HOSPITAL_NAME = 'hospital_name';
    public const SORT_ASSIGNEE_NAME = 'assignee_name';

    public const ALL_SORT_COLUMNS = [
        self::SORT_CREATED_AT,
        self::SORT_COORDINATION_TYPE,
        self::SORT_DUE_DATE,
        self::SORT_CLIENT_NAME,
        self::SORT_CLIENT_ID,
        self::SORT_HOSPITAL_NAME,
        self::SORT_ASSIGNEE_NAME,
    ];

    public function __construct(
        public array $state = [],
        public array $branchIds = [],
        public array $regionIds = [],
        public array $assigneeIds = [],
        public ?bool $isCompleted = null,
        public ?string $search = null,
        public bool $meMode = false,
        // Scopes the list to activities on one tracker's encounter (Hospitalization Tracker modal's
        // Care Coordination tab, AC-less follow-on to PD-3494) — everything else here filters the ICM
        // dashboard bucket instead.
        public ?int $hospitalizationTrackerId = null,
        public int $page = 1,
        public int $perPage = 10,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        // PD-3969: scopes the list to IP or ED activities (by the encounter's type); null = no scoping.
        public ?EncounterTypeEnum $encounterType = null,
    ) {
    }
}
