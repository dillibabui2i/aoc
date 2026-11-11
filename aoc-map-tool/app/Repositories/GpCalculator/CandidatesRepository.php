<?php

namespace App\Repositories\GpCalculator;

use Illuminate\Support\Facades\DB;

class CandidatesRepository
{
    public function getCandidatesList(string $search, $limit = 30) : array
    {
        $candidatesList = DB::table('candidates')
            ->select(
                'candidates.id',
                'candidates.firstName as FirstName',
                'candidates.lastName as LastName',
                'candidates.eId as ClinicianID',
                'candidates.jobvite_email as External_Email',
                'candidates.discipline as DisciplineName',
                'candidates.mobile as Mobile',
                'candidates.city as City',
                'candidates.state as State',
                'candidates.mobile as Mobile',
                'candidates.disposition as EmploymentStatus'
            )
            ->selectRaw(
                "COALESCE(
                    NULLIF(TRIM(BOTH ',' FROM TRIM(location)), ''),
                    NULLIF(TRIM(BOTH ',' FROM TRIM(jobLocation)), ''),
                    NULLIF(TRIM(BOTH ',' FROM TRIM(jobBranch)), '')
                ) AS BranchName"
            )
            ->selectRaw('0 AS CF_NotEligibleForRehire')
            ->where(
                function ($query) use ($search) {
                    $query->where('candidates.Search_Name', 'like', '%' . $search . '%');
                }
            )->where('candidates.is_deleted', 0)
            ->orderBy('candidates.Search_Name', 'ASC')
            ->limit($limit)
            ->get();

        return json_decode(json_encode($candidatesList), true);
    }
}
