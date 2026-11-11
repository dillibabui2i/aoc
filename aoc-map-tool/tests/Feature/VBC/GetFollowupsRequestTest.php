<?php

namespace Tests\Feature\VBC;

use App\VBC\DTO\Followup\FollowupFilterDTO;
use App\VBC\Http\Requests\Followup\GetFollowupsRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Contract tests for the Follow-up list request: validation rules + DTO mapping.
 * No database — these assert what the endpoint accepts/rejects and how it maps to the DTO.
 */
class GetFollowupsRequestTest extends TestCase
{
    // --- validation ---------------------------------------------------------

    public function test_accepts_a_fully_populated_valid_query(): void
    {
        $passes = $this->validate([
            'state' => ['TX'],
            'branch_ids' => [1, 2],
            'region_ids' => [3],
            'assignee_ids' => [1, 11],
            'statuses' => ['SCHEDULED', 'COMPLETED'],
            'search' => 'LAFEBRE',
            'me_mode' => 'true',
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'followup_date',
            'sort_dir' => 'asc',
        ])->passes();

        $this->assertTrue($passes);
    }

    public function test_rejects_an_unknown_sort_by_column(): void
    {
        $this->assertTrue($this->validate(['sort_by' => 'id'])->errors()->has('sort_by'));
    }

    public function test_accepts_every_whitelisted_sort_by_column(): void
    {
        foreach (FollowupFilterDTO::ALL_SORT_COLUMNS as $column) {
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
            ['followup_date', 'patient_name', 'patient_account', 'facility_name', 'assignee_name'],
            FollowupFilterDTO::ALL_SORT_COLUMNS
        );
    }

    public function test_accepts_valid_status_enum_values(): void
    {
        $this->assertFalse($this->validate(['statuses' => ['NOT_STARTED', 'CANCELED']])->errors()->has('statuses.0'));
    }

    public function test_rejects_an_invalid_status_value(): void
    {
        $this->assertTrue($this->validate(['statuses' => ['NOT_A_STATUS']])->errors()->has('statuses.0'));
    }

    public function test_state_must_be_a_string(): void
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

    public function test_maps_query_params_to_dto(): void
    {
        $dto = GetFollowupsRequest::create('/x', 'GET', [
            'statuses' => ['COMPLETED'],
            'assignee_ids' => ['1', '11'],
            'state' => ['TX'],
            'me_mode' => 'true',
            'sort_by' => 'patient_name',
            'sort_dir' => 'asc',
        ])->toDto();

        $this->assertSame(['COMPLETED'], $dto->statuses);
        $this->assertSame([1, 11], $dto->assigneeIds);
        $this->assertSame(['TX'], $dto->state);
        $this->assertTrue($dto->meMode);
        $this->assertSame('patient_name', $dto->sortBy);
    }

    public function test_applies_defaults_when_params_are_absent(): void
    {
        $dto = GetFollowupsRequest::create('/x', 'GET', [])->toDto();

        $this->assertSame('created_at', $dto->sortBy);
        $this->assertSame('desc', $dto->sortDir);
        $this->assertSame(10, $dto->perPage);
        $this->assertFalse($dto->meMode);
        $this->assertSame([], $dto->statuses);
    }

    private function validate(array $data): ValidatorContract
    {
        return Validator::make($data, (new GetFollowupsRequest())->rules());
    }
}
