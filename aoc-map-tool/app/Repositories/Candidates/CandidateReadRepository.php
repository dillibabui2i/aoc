<?php

namespace App\Repositories\Candidates;

use App\Candidates;
use App\DTO\CandidateDTO;
use App\Jobs;
use Illuminate\Support\LazyCollection;

class CandidateReadRepository
{

    public function __construct(private Candidates $candidate, private Jobs $job) {}

    public function getCandidateByID(int $candidateID): ?Candidates
    {
        return $this->candidate->with(['offer.finalValues', 'interviewManagerUser'])->find($candidateID);
    }

    public function getCandidateByPhoneOrEmail(CandidateDTO $data): ?Candidates
    {
        return $this->candidate->query()
          ->where(function ($q) use ($data) {
              if ($data->email) {
                  $q->whereRaw('LOWER(email) = ?', [$data->email])
                    ->orWhereRaw('LOWER(jobvite_email) = ?', [$data->email]);
              }
              if ($data->externalEmail) {
                  $q->whereRaw('LOWER(email) = ?', [$data->externalEmail])
                    ->orWhereRaw('LOWER(jobvite_email) = ?', [$data->externalEmail]);
              }
          })
          ->orWhere(function ($q) use ($data) {
              if ($data->phone) {
                  $q->orWhereRaw('REGEXP_REPLACE(homePhone, "[^0-9]", "") = ?', [$data->phone])
                    ->orWhereRaw('REGEXP_REPLACE(mobile, "[^0-9]", "") = ?', [$data->phone]);
              }
              if ($data->mobile) {
                  $q->orWhereRaw('REGEXP_REPLACE(homePhone, "[^0-9]", "") = ?', [$data->mobile])
                    ->orWhereRaw('REGEXP_REPLACE(mobile, "[^0-9]", "") = ?', [$data->mobile]);
              }
          })
          ->first();
    }

    public function getAppSourcesBySourceTypes(array $types): LazyCollection
    {
        return Candidates::query()
            ->select('appSource')
            ->distinct()
            ->whereIn('appSourceType', $types)
            ->where('appSource', '!=', '')
            ->cursor();
    }

    public function getJobCategoryForCandidate(int $candidateID): ?string
    {
        $candidate = $this->candidate->find($candidateID);

        if (is_null($candidate)) {
            return null;
        }

        return $this->job->where('eId', $candidate->jobId)
            ->orWhere('id', $candidate->jobId)
            ->value('category');
    }

}
