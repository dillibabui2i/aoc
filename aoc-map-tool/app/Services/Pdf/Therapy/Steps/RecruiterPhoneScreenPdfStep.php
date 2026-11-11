<?php

namespace App\Services\Pdf\Therapy\Steps;

use App\Candidates;
use App\Models\CandidateScreening;
use App\Services\Pdf\Therapy\Contracts\TherapyPdfStepInterface;
use Illuminate\Support\Str;

class RecruiterPhoneScreenPdfStep implements TherapyPdfStepInterface
{
    private const WORKLOAD_LABELS = [
        '0' => '0-5',
        '1' => '5-10',
        '2' => '10-20',
        '3' => '20-30',
        '4' => '30+',
    ];

    public function getData(int $candidateId): array
    {
        $candidate = Candidates::findOrFail($candidateId);
        $screening = CandidateScreening::where('candidate_id', $candidateId)
            ->with('recruiter')
            ->firstOrFail();

        $workloadLabel = self::WORKLOAD_LABELS[$screening->workload] ?? $screening->workload;

        return compact('candidate', 'screening', 'workloadLabel');
    }

    public function getTemplate(): string
    {
        return 'pdf.therapy.recruiter_phone_screen';
    }

    public function getFilename(array $data): string
    {
        return 'recruiter-phone-screen-' . Str::slug($data['candidate']->fullName) . '.pdf';
    }
}
