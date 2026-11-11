<?php

namespace Tests\Feature\VBC;

use App\VBC\DTO\Hospitalization\HospitalizationFilterDTO;
use App\VBC\Http\Requests\Hospitalization\GetHospitalizationsRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Contract tests for the Hospitalization list request: validation rules + DTO mapping.
 * No database — these assert what the endpoint accepts/rejects and how it maps to the DTO.
 */
class GetHospitalizationsRequestTest extends TestCase
{
    // --- validation ---------------------------------------------------------

    public function test_accepts_a_fully_populated_valid_query(): void
    {
        $passes = $this->validate([
            'state' => ['TX'],
            'branch_ids' => [1, 2],
            'region_ids' => [3],
            'assignee_ids' => [1, 11],
            'statuses' => ['OPEN', 'IN_PROGRESS'],
            'search' => 'PAYNE',
            'me_mode' => 'true',
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'admission_date',
            'sort_dir' => 'asc',
            'admission_date_from' => '01/01/2024',
            'admission_date_to' => '12/31/2024',
        ])->passes();

        $this->assertTrue($passes);
    }

    public function test_rejects_an_unknown_sort_by_column(): void
    {
        $this->assertTrue($this->validate(['sort_by' => 'id'])->errors()->has('sort_by'));
    }

    public function test_accepts_every_whitelisted_sort_by_column(): void
    {
        foreach (HospitalizationFilterDTO::ALL_SORT_COLUMNS as $column) {
            $this->assertFalse(
                $this->validate(['sort_by' => $column])->errors()->has('sort_by'),
                "sort_by={$column} should be accepted"
            );
        }
    }

    /** Pins the literal wire contract so renaming a SORT_* constant's value fails the build. */
    public function test_sort_by_wire_values_match_the_public_contract(): void
    {
        $this->assertSame(
            ['admission_date', 'discharge_date', 'status', 'level', 'type', 'patient_name', 'patient_account', 'tracker_id', 'assignee_name', 'branch', 'last_follow_up'],
            HospitalizationFilterDTO::ALL_SORT_COLUMNS
        );
    }

    public function test_accepts_valid_status_enum_values(): void
    {
        $this->assertFalse($this->validate(['statuses' => ['OPEN', 'CLOSED']])->errors()->has('statuses.0'));
    }

    public function test_rejects_an_invalid_status_value(): void
    {
        $this->assertTrue($this->validate(['statuses' => ['NOT_A_STATUS']])->errors()->has('statuses.0'));
    }

    public function test_accepts_valid_level_enum_values(): void
    {
        $this->assertFalse($this->validate(['levels' => ['LEVEL_I', 'LEVEL_II_III']])->errors()->has('levels.0'));
    }

    public function test_rejects_an_invalid_level_value(): void
    {
        $this->assertTrue($this->validate(['levels' => ['NOT_A_LEVEL']])->errors()->has('levels.0'));
    }

    public function test_accepts_facility_names_as_an_array_of_strings(): void
    {
        $this->assertFalse($this->validate(['facility_names' => ['General Hospital']])->errors()->has('facility_names'));
    }

    public function test_accepts_valid_export_type_enum_values(): void
    {
        $this->assertFalse($this->validate(['export_type' => 'HIGH_UTILIZERS'])->errors()->has('export_type'));
    }

    public function test_rejects_an_invalid_export_type_value(): void
    {
        $this->assertTrue($this->validate(['export_type' => 'NOT_A_TYPE'])->errors()->has('export_type'));
    }

    public function test_state_must_be_an_array(): void
    {
        $this->assertTrue($this->validate(['state' => 'TX'])->errors()->has('state'));
        $this->assertFalse($this->validate(['state' => ['TX']])->errors()->has('state'));
    }

    public function test_rejects_out_of_range_per_page(): void
    {
        $this->assertTrue($this->validate(['per_page' => 0])->errors()->has('per_page'));
        $this->assertTrue($this->validate(['per_page' => 101])->errors()->has('per_page'));
        $this->assertFalse($this->validate(['per_page' => 50])->errors()->has('per_page'));
    }

    public function test_rejects_admission_date_to_before_admission_date_from(): void
    {
        $errors = $this->validate(['admission_date_from' => '06/10/2024', 'admission_date_to' => '06/01/2024'])->errors();

        $this->assertTrue($errors->has('admission_date_to'));
    }

    public function test_accepts_valid_encounter_type_enum_values(): void
    {
        $this->assertFalse($this->validate(['encounter_types' => ['ED', 'IP']])->errors()->has('encounter_types.0'));
    }

    public function test_rejects_an_invalid_encounter_type_value(): void
    {
        $this->assertTrue($this->validate(['encounter_types' => ['OBS']])->errors()->has('encounter_types.0'));
    }

    public function test_encounter_types_must_be_an_array(): void
    {
        $this->assertTrue($this->validate(['encounter_types' => 'ED'])->errors()->has('encounter_types'));
    }

    // --- DTO mapping --------------------------------------------------------

    public function test_maps_query_params_to_dto(): void
    {
        $dto = GetHospitalizationsRequest::create('/x', 'GET', [
            'statuses' => ['OPEN'],
            'levels' => ['LEVEL_I'],
            'facility_names' => ['General Hospital'],
            'export_type' => 'HIGH_UTILIZERS',
            'assignee_ids' => ['1', '11'],
            'me_mode' => 'true',
            'sort_by' => 'status',
            'sort_dir' => 'asc',
        ])->toDto();

        $this->assertSame(['OPEN'], $dto->statuses);
        $this->assertSame(['LEVEL_I'], $dto->levels);
        $this->assertSame(['General Hospital'], $dto->facilityNames);
        $this->assertSame('HIGH_UTILIZERS', $dto->exportType);
        $this->assertSame([1, 11], $dto->assigneeIds);
        $this->assertTrue($dto->meMode);
        $this->assertSame('status', $dto->sortBy);
        $this->assertSame('asc', $dto->sortDir);
    }

    public function test_applies_defaults_when_params_are_absent(): void
    {
        $dto = GetHospitalizationsRequest::create('/x', 'GET', [])->toDto();

        $this->assertSame('admission_date', $dto->sortBy);
        $this->assertSame('desc', $dto->sortDir);
        $this->assertSame(10, $dto->perPage);
        $this->assertFalse($dto->meMode);
        $this->assertSame([], $dto->statuses);
        $this->assertSame([], $dto->levels);
        $this->assertSame([], $dto->facilityNames);
        $this->assertNull($dto->exportType);
        // Explicit backend default: an absent filter means IP, not "any type" (AOC encounter_type ticket).
        $this->assertSame(['IP'], $dto->encounterTypes);
    }

    public function test_maps_encounter_types_to_dto(): void
    {
        $dto = GetHospitalizationsRequest::create('/x', 'GET', ['encounter_types' => ['ED']])->toDto();

        $this->assertSame(['ED'], $dto->encounterTypes);
    }

    private function validate(array $data): ValidatorContract
    {
        return Validator::make($data, (new GetHospitalizationsRequest())->rules());
    }
}
