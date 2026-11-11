<?php

namespace App\Services\Pdf\Therapy\Steps;

use App\Candidates;
use App\Models\CandidateClinicalInterviewQuestion;
use App\Models\Offer;
use App\Models\OfferField;
use App\Services\Pdf\Therapy\Contracts\TherapyPdfStepInterface;
use Illuminate\Support\Str;

class ClinicalInterviewPdfStep implements TherapyPdfStepInterface
{
    public const INTERVIEW_LABELS = [
        'about_self' => 'Tell me a little about yourself',
        'position_expectations' => 'What are you looking for in a position?',
        'pediatric_clinical_skills' => 'Tell me about your clinical skill set specific to the pediatric population',
        'professional_strengths' => 'What are your strengths as a professional?',
        'discipline_specific_questions' => 'Discipline specific questions',
        'areas_for_improvement' => 'What are some opportunities for improvement?',
        'organizational_skills' => 'Can you speak to your organizational skills?',
        'challenging_situation' => 'Challenging situation scenario',
        'previous_employer_feedback' => 'If I spoke to your previous employer...',
        'emr_and_technology_experience' => 'Do you have EMR experience?',
        'community_travel_comfort' => 'How comfortable are you traveling in the community?',
        'non_english_family_experience' => 'Comfort with non-English speaking families?',
        'five_year_goals' => 'What are your goals for the next 5 years?',
        'leadership_interest' => 'Do you have a history or interest in leadership opportunities?',
        'discussion_notes' => 'Discussion notes',
        'desired_weekly_visits' => 'How many visits would you like completing a week?',
        'preferred_area_and_driving_range' => 'Preferred area and driving range',
        'early_intervention_interest' => 'Do you have any interest in Early Intervention?',
        'candidate_questions' => 'What questions do you have for me?',
        'desired_pay_per_visit' => 'What is your goal to be paid per visit?',
        'additional_comments' => 'Additional comments and outstanding questions',
    ];

    public function getData(int $candidateId): array
    {
        $candidate = Candidates::findOrFail($candidateId);
        $interview = CandidateClinicalInterviewQuestion::where('employee_id', $candidateId)->firstOrFail();
        $offer = Offer::where('employee_id', $candidateId)
            ->with(['originalValues.field'])
            ->firstOrFail();
        $fields = OfferField::orderBy('id')->get()->keyBy('key');

        $offerValues = $offer->originalValues->keyBy('key');

        return compact('candidate', 'interview', 'offer', 'fields', 'offerValues');
    }

    public function getTemplate(): string
    {
        return 'pdf.therapy.clinical_interview';
    }

    public function getFilename(array $data): string
    {
        return 'clinical-interview-' . Str::slug($data['candidate']->fullName) . '.pdf';
    }
}
