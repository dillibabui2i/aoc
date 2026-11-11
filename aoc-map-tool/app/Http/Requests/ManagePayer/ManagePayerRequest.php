<?php

namespace App\Http\Requests\ManagePayer;

use App\DTO\ManagePayer\ManagePayerParamsDTO;
use App\DTO\ManagePayer\ManagePayerRowParamsDTO;
use App\Enums\ManagePayer\GrossMarginLobEnum;
use App\Http\Requests\CoreRequest;
use App\Services\ManagePayer\ManagePayerService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ManagePayerRequest extends CoreRequest
{
    public function __construct(protected ManagePayerService $service)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:payment_source,id'],
            'state' => ['required', 'string', 'max:255'],
            'rows' => ['sometimes', 'array', 'min:1'],
            'rows.*.id' => ['sometimes', 'nullable', 'integer'],
            'rows.*.lob' => ['required', 'integer', Rule::enum(GrossMarginLobEnum::class)],
            'rows.*.branch_ids' => ['required', 'array', 'min:1'],
            'rows.*.branch_ids.*' => ['required', 'integer', 'exists:mssql_facilities,id'],
            'rows.*.recruiter_coverage_target' => ['sometimes'],
            'rows.*.recruiter_escalated' => ['sometimes'],
            'rows.*.tl_manager_escalated' => ['sometimes'],
            'rows.*.tl_manager_whole_home_escalated' => ['sometimes'],
            'rows.*.exec_team_escalated' => ['sometimes'],
            'rows.*.coverage_escalated' => ['sometimes'],
            'rows.*.coverage_tl_manager_escalated' => ['sometimes'],
            'rows.*.coverage_tl_manager_whole_home_escalated' => ['sometimes'],
            'rows.*.coverage_exec_team_escalated' => ['sometimes'],
        ];
    }

    /**
     * @return ManagePayerParamsDTO
     */
    public function toDTO(): ManagePayerParamsDTO
    {
        $data = $this->validated();

        return new ManagePayerParamsDTO(
            id: (int)$data['id'],
            state: $data['state'],
            rows: array_map(
                fn(array $row) => new ManagePayerRowParamsDTO(
                    id:  isset($row['id']) ? (int) $row['id'] : null,
                    lob: GrossMarginLobEnum::from((int)$row['lob']),
                    branchIds: array_map('intval', $row['branch_ids']),
                    recruiterCoverageTarget: (float)$row['recruiter_coverage_target'],
                    recruiterEscalated: (float)$row['recruiter_escalated'],
                    tlManagerEscalated: (float)$row['tl_manager_escalated'],
                    tlManagerWholeHomeEscalated: (float)$row['tl_manager_whole_home_escalated'],
                    execTeamEscalated: (float)$row['exec_team_escalated'],
                    coverageEscalated: (float)$row['coverage_escalated'],
                    coverageTlManagerEscalated: (float)$row['coverage_tl_manager_escalated'],
                    coverageTlManagerWholeHomeEscalated: (float)$row['coverage_tl_manager_whole_home_escalated'],
                    coverageExecTeamEscalated: (float)$row['coverage_exec_team_escalated'],
                ),
                $data['rows'] ?? []
            ),
        );
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $dto = $this->toDTO();

            if ($this->service->isGrossMarginConfigurationExist($dto)) {
                $validator->errors()->add(
                    'rows',
                    'The configuration already exists'
                );
            }
        });
    }
}
