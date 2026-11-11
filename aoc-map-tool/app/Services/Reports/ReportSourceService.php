<?php

namespace App\Services\Reports;

use App\Repositories\Candidates\CandidateReadRepository;
use Illuminate\Support\LazyCollection;

class ReportSourceService
{
    public function __construct(private CandidateReadRepository $candidateReadRepository)
    {}

    public function getSource(array $sourceType): LazyCollection
    {
        return $this->candidateReadRepository->getAppSourcesBySourceTypes($sourceType);
    }
}
