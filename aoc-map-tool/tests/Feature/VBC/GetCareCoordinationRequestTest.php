<?php

namespace Tests\Feature\VBC;

use App\VBC\DTO\CareCoordination\CareCoordinationFilterDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Requests\CareCoordination\GetCareCoordinationRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Contract tests for the Care Coordination list request: validation rules + DTO mapping.
 * No database — these assert what the endpoint accepts/rejects and how it maps to the DTO.
 * Note: `status` is a completion flag (bool) that maps to the DTO's `isCompleted`.
 */
class GetCareCoordinationRequestTest extends TestCase
{
    // --- validation ---------------------------------------------------------

    public function test_accepts_a_fully_populated_valid_query(): void
    {
        $passes = $this->validate([
            'state' => ['TX'],
            'branch_ids' => [1, 2],
            'region_ids' => [3],
            'assignee_ids' => [1, 11],
            'status' => '1',
            'search' => 'LAFEBRE',
            'me_mode' => 'true',
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'created_at',
            'sort_dir' => 'asc',
        ])->passes();

        $this->assertTrue($passes);
    }

    public function test_rejects_an_unknown_sort_by_column(): void
    {
        $this->assertTrue($this->validate(['sort_by' => 'id'])->errors()->has('sort_by'));
    }

    // --- encounter_type (PD-3969) -------------------------------------------

    public function test_accepts_ed_and_ip_encounter_types_and_rejects_anything_else(): void
    {
        $this->assertFalse($this->validate(['encounter_type' => 'ED'])->errors()->has('encounter_type'));
        $this->assertFalse($this->validate(['encounter_type' => 'IP'])->errors()->has('encounter_type'));
        $this->assertTrue($this->validate(['encounter_type' => 'XX'])->errors()->has('encounter_type'));
    }

    public function test_maps_encounter_type_to_the_dto_and_defaults_to_null(): void
    {
        $this->assertSame(EncounterTypeEnum::ED, $this->dtoFor(['encounter_type' => 'ED'])->encounterType);
        $this->assertNull($this->dtoFor([])->encounterType);
    }

    public function test_accepts_every_whitelisted_sort_by_column(): void
    {
        foreach (CareCoordinationFilterDTO::ALL_SORT_COLUMNS as $column) {
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
            ['created_at', 'coordination_type', 'due_date', 'patient_name', 'patient_account', 'hospital_name', 'assignee_name'],
            CareCoordinationFilterDTO::ALL_SORT_COLUMNS
        );
    }

    public function test_accepts_boolean_like_status_values(): void
    {
        foreach (['true', 'false', '1', '0'] as $value) {
            $this->assertFalse(
                $this->validate(['status' => $value])->errors()->has('status'),
                "status={$value} should be accepted"
            );
        }
    }

    public function test_rejects_a_non_boolean_status_value(): void
    {
        $this->assertTrue($this->validate(['status' => '5'])->errors()->has('status'));
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

    // --- DTO mapping --------------------------------------------------------

    public function test_maps_status_flag_to_is_completed(): void
    {
        $this->assertTrue($this->dtoFor(['status' => '1'])->isCompleted);
        $this->assertFalse($this->dtoFor(['status' => '0'])->isCompleted);
        $this->assertNull($this->dtoFor([])->isCompleted);
    }

    public function test_maps_query_params_to_dto(): void
    {
        $dto = $this->dtoFor([
            'assignee_ids' => ['1', '11'],
            'state' => ['TX'],
            'me_mode' => 'true',
            'sort_by' => 'due_date',
            'sort_dir' => 'asc',
        ]);

        $this->assertSame([1, 11], $dto->assigneeIds);
        $this->assertSame(['TX'], $dto->state);
        $this->assertTrue($dto->meMode);
        $this->assertSame('due_date', $dto->sortBy);
    }

    public function test_applies_defaults_when_params_are_absent(): void
    {
        $dto = $this->dtoFor([]);

        $this->assertSame('created_at', $dto->sortBy);
        $this->assertSame('desc', $dto->sortDir);
        $this->assertSame(10, $dto->perPage);
        $this->assertFalse($dto->meMode);
    }

    private function validate(array $data): ValidatorContract
    {
        return Validator::make($data, (new GetCareCoordinationRequest())->rules());
    }

    private function dtoFor(array $query): CareCoordinationFilterDTO
    {
        return GetCareCoordinationRequest::create('/x', 'GET', $query)->toDto();
    }
}
