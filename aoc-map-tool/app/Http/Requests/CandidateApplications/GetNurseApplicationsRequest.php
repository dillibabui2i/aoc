<?php

namespace App\Http\Requests\CandidateApplications;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;
use App\Enums\FeatureFlagsEnum;
use App\Helpers\LD;
use App\Http\Requests\CoreRequest;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Class GetNurseApplicationsRequest
 *
 * @package App\Http\Requests\CandidateApplications
 */
class GetNurseApplicationsRequest extends CoreRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'type' => ['string', 'sometimes'],
            'draw' => ['int', 'sometimes'],
            'columns' => ['array', 'sometimes'],
            'order' => ['array', 'sometimes'],
            'start' => ['int', 'sometimes'],
            'length' => ['int', 'sometimes'],
            'search' => ['array', 'sometimes'],
            '_' => ['string', 'sometimes'],
        ];
    }

    /**
     * @param bool $isTCMRole
     * @return CandidateApplicationsDTO
     * @throws Exception
     */
    public function getNurseApplicationsListDTO(bool $isTCMRole = false): CandidateApplicationsDTO
    {
        return new CandidateApplicationsDTO(
            LD::checkFeatureFlag(FeatureFlagsEnum::JOBVITE_ADD_CANDIDATE_NOTE->value),
            LD::checkFeatureFlag(FeatureFlagsEnum::ADD_PET_FILTER_TO_JOBVITE_PET_BUCKET->value),
            Auth::id(),
            Auth::user()->email,
            $this->get('filters', []),
            $this->get('type'),
            $isTCMRole,
        );
    }
}
