<?php

namespace App\Services\Dashboard\Buckets\Jobvite;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;
use App\Repositories\App\CandidateApplications\CandidateApplicationsReadRepository;
use App\Services\LogSq;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class CandidateApplicationsService
 *
 * @package App\Services\Dashboard\Buckets\Jobvite
 */
readonly class CandidateApplicationsService
{
    /**
     * @param CandidateApplicationsReadRepository $applicationsReadRepository
     */
    public function __construct(private CandidateApplicationsReadRepository $applicationsReadRepository)
    {
    }

    /**
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return Collection
     * @throws Exception
     */
    public function getFilteredCandidateApplications(CandidateApplicationsDTO $candidateApplicationsDTO): Collection
    {
        return $this->applicationsReadRepository->getFilteredNursesApplications($candidateApplicationsDTO);
    }
}
