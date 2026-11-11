<?php

namespace App\VBC\Services\Common;

use App\Helpers\ConstantHelper;
use App\VBC\Enum\Common\FilterBucketEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationExportTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseOutcomeEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusEnum;
use App\VBC\Repositories\CdcVaccine\CdcVaccineGroupReadRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\Immunization\ImmunizationReadRepository;
use App\VBC\Repositories\Immunization\ImmunizationScheduleReadRepository;

/**
 * Shared filter-options payload (states/branches/regions/statuses/...) consumed by every ICM
 * dashboard bucket (Hospitalization, Care Coordination, Follow-up), the All Hospitalizations
 * page and the All Immunizations page — none of them own this data, so it lives here rather
 * than under one entity's controller.
 */
readonly class FiltersService
{
    public function __construct(
        private ConstantHelper $constantHelper,
        private HospitalizationReadRepository $repository,
        private ImmunizationReadRepository $immunizationRepository,
        private ImmunizationScheduleReadRepository $immunizationScheduleRepository,
        private CdcVaccineGroupReadRepository $vaccineGroupRepository,
    ) {}

    /**
     * The ICM buckets and All Hospitalizations share one payload (any bucket other than IMMUNIZATION
     * gets it unchanged). A bucket that needs a different set of options gets its own arm here, so the
     * queries behind options it doesn't use are never run.
     */
    public function getOptions(?FilterBucketEnum $bucket = null): array
    {
        return match ($bucket) {
            FilterBucketEnum::IMMUNIZATION => $this->immunizationOptions(),
            FilterBucketEnum::IMMUNIZATION_SCHEDULE => $this->immunizationScheduleOptions(),
            FilterBucketEnum::HOSPITALIZATION => $this->hospitalizationOptions(
                HospitalizationTrackerStatusEnum::bucketOptions()
            ),
            FilterBucketEnum::ED_VISITS => $this->hospitalizationOptions(
                HospitalizationTrackerStatusEnum::edVisitsBucketOptions()
            ),
            default => $this->hospitalizationOptions(HospitalizationTrackerStatusEnum::options()),
        };
    }

    private function hospitalizationOptions(array $statusOptions): array
    {
        return [
            ...$this->locationOptions(),
            'regions' => $this->repository->getRegionOptions()->map(fn ($region) => [
                'value' => $region->id,
                'text' => $region->region_name,
                'state' => $region->state,
            ]),
            'hospitalizationStatuses' => $statusOptions,
            'followupStatuses' => HospitalizationTrackerFollowupStatusEnum::options(),
            'level' => HospitalizationTrackerIcmLevelEnum::options(),
            'assignees' => $this->repository->getAssigneeOptions()->map(fn ($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ]),
            'facility_names' => $this->repository->getFacilityNameOptions()->map(fn ($name) => [
                'value' => $name,
                'label' => $name,
            ]),
            'export_types' => HospitalizationExportTypeEnum::options(),
        ];
    }

    private function immunizationOptions(): array
    {
        return [
            ...$this->locationOptions(),
            'vaccines' => $this->immunizationRepository->getVaccineOptions()->map(fn ($vaccine) => [
                'value' => $vaccine->cvx_code,
                'text' => $vaccine->cdc_full_name,
            ]),
            'vaccineDoseStatuses' => VaccineDoseStatusEnum::options(),
            'vaccineDoseOutcomes' => VaccineDoseOutcomeEnum::options(),
            'payers' => $this->immunizationRepository->getPayerOptions()->map(fn ($payer) => [
                'value' => $payer,
                'label' => $payer,
            ]),
        ];
    }

    /** Options of the Add/Edit Immunization Schedule modal. */
    private function immunizationScheduleOptions(): array
    {
        return [
            // Every US state (same list the `originating_state` column is validated against), not just the
            // dashboard states — a template may target a state AOC doesn't operate in yet.
            'states' => collect(config('constant.states'))
                ->map(fn ($name, $abbr) => ['value' => $abbr, 'text' => $name])
                ->values()
                ->all(),
            'vaccineCategories' => $this->vaccineGroupRepository->getOptions()->map(fn ($group) => [
                'value' => $group->cvx_group_code,
                'text' => $group->aoc_name ?? $group->cdc_name,
            ]),
            'vaccines' => $this->immunizationScheduleRepository->getVaccineOptions()->map(fn ($vaccine) => [
                'value' => $vaccine->cvx_code,
                'text' => $vaccine->cdc_full_name,
                'category' => $vaccine->cvx_group_code,
            ]),
            'recommendedFor' => ImmunizationRecommendedForEnum::options(),
            'statuses' => ImmunizationRecommendationStatusEnum::options(),
            'frequencies' => array_map(
                fn (ImmunizationRecommendationFrequencyEnum $case) => ['value' => $case->value, 'label' => $case->label()],
                ImmunizationRecommendationFrequencyEnum::scheduleCases()
            ),
            // The modal's wording ("Month(s)" / "Years") — the enum's own labels feed the list's age chips.
            'doseAgeUnits' => [
                ['value' => ImmunizationDoseAgeUnitEnum::MONTH->value, 'label' => 'Month(s)'],
                ['value' => ImmunizationDoseAgeUnitEnum::YEAR->value, 'label' => 'Years'],
            ],
        ];
    }

    /** States and branches — the only options every page needs. */
    private function locationOptions(): array
    {
        return [
            'states' => $this->constantHelper->getDashboardState(),
            'branches' => $this->constantHelper->getFacilities()->map(fn ($facility) => [
                'text' => $facility['FacilityName'],
                'value' => $facility['id'],
                'state' => $facility['State'],
            ]),
        ];
    }
}
