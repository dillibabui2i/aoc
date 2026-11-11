<?php

namespace App\VBC\Http\Requests\Immunization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Immunization\DoseAgeTargetDTO;
use App\VBC\DTO\Immunization\ImmunizationScheduleDTO;
use App\VBC\Enum\CdcVaccine\CdcVaccineStatusEnum;
use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Repositories\Immunization\ImmunizationScheduleReadRepository;
use App\VBC\Rules\UniqueVaccinePerState;
use App\VBC\Rules\ValidDoseAgeTargets;
use Illuminate\Validation\Rule;

class StoreImmunizationScheduleRequest extends CoreRequest
{
    // unsigned SMALLINT column
    private const MAX_AGE = 65535;

    public function __construct(private readonly ImmunizationScheduleReadRepository $repository)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            // Vaccine Category is not submitted: the vaccine already belongs to exactly one category.
            'vaccine_cvx_code' => [
                'required',
                'integer',
                Rule::exists('cdc_vaccines', 'cvx_code')
                    ->whereNull('deleted_at')
                    ->whereNotNull('cvx_group_code')
                    ->where('cdc_status', CdcVaccineStatusEnum::ACTIVE->value),
                new UniqueVaccinePerState($this->repository, $this->input('state'), $this->ignoredScheduleId()),
            ],
            'state' => ['required', 'string', Rule::in(array_keys(config('constant.states')))],
            'recommended_for' => ['required', 'string', Rule::enum(ImmunizationRecommendedForEnum::class)],
            'frequency' => ['required', 'string', Rule::in(array_column(ImmunizationRecommendationFrequencyEnum::scheduleCases(), 'value'))],
            'is_active' => ['required', 'boolean'],
            'dose_age_targets' => ['required', 'array', 'min:1', new ValidDoseAgeTargets()],
            'dose_age_targets.*' => ['array'],
            'dose_age_targets.*.from_age' => ['required', 'integer', 'min:0', 'max:' . self::MAX_AGE],
            'dose_age_targets.*.from_age_unit' => ['required', Rule::enum(ImmunizationDoseAgeUnitEnum::class)],
            'dose_age_targets.*.to_range' => ['required', 'boolean'],
            // Required-when-range and the value limits are ValidDoseAgeTargets' job.
            'dose_age_targets.*.to_age' => ['nullable', 'integer', 'min:0', 'max:' . self::MAX_AGE],
            'dose_age_targets.*.to_age_unit' => ['nullable', Rule::enum(ImmunizationDoseAgeUnitEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'vaccine_cvx_code' => 'Vaccine Name',
            'state' => 'State',
            'recommended_for' => 'Recommended For',
            'frequency' => 'Frequency',
            'is_active' => 'Active',
            'dose_age_targets' => 'Dose Age Targets',
            'dose_age_targets.*.from_age' => 'Start value',
            'dose_age_targets.*.from_age_unit' => 'Start unit',
            'dose_age_targets.*.to_age' => 'End value',
            'dose_age_targets.*.to_age_unit' => 'End unit',
        ];
    }

    /** Update excludes the schedule being edited from the "one template per vaccine and state" check. */
    protected function ignoredScheduleId(): ?int
    {
        return null;
    }

    public function toDto(): ImmunizationScheduleDTO
    {
        return new ImmunizationScheduleDTO(
            vaccineCvxCode: (int) $this->input('vaccine_cvx_code'),
            state: $this->input('state'),
            recommendedFor: ImmunizationRecommendedForEnum::from($this->input('recommended_for')),
            frequency: ImmunizationRecommendationFrequencyEnum::from($this->input('frequency')),
            status: $this->boolean('is_active')
                ? ImmunizationRecommendationStatusEnum::ACTIVE
                : ImmunizationRecommendationStatusEnum::INACTIVE,
            doseAgeTargets: array_map(fn (array $target) => $this->toTargetDto($target), array_values($this->input('dose_age_targets'))),
        );
    }

    // `to_age`/`to_age_unit` are ignored (stored as NULL) unless "to range" is ticked.
    private function toTargetDto(array $target): DoseAgeTargetDTO
    {
        $isRange = filter_var($target['to_range'], FILTER_VALIDATE_BOOLEAN);

        return new DoseAgeTargetDTO(
            fromAge: (int) $target['from_age'],
            fromAgeUnit: ImmunizationDoseAgeUnitEnum::from($target['from_age_unit']),
            toAge: $isRange ? (int) $target['to_age'] : null,
            toAgeUnit: $isRange ? ImmunizationDoseAgeUnitEnum::from($target['to_age_unit']) : null,
        );
    }
}
