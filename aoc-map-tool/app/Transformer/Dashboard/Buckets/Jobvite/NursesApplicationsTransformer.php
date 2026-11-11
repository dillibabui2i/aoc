<?php

namespace App\Transformer\Dashboard\Buckets\Jobvite;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;
use App\Enums\JobViteRecruiterJobType;
use App\Helpers\LD;
use App\Traits\DateFormatter;
use Carbon\Carbon;
use DateMalformedStringException;
use DateTime;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use stdClass;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

/**
 * Class NursesApplicationsTransformer
 *
 * @package App\Transformer\Dashboard\Buckets\Jobvite
 */
class NursesApplicationsTransformer
{
    use DateFormatter;

    public const NURSING_PARENT_BUCKET_KEYWORD = 'nurse';
    public const THERAPY_PARENT_BUCKET_KEYWORD = 'therapist';
    private const ASSIGN_RECRUITER_TO_JOB = [
        'NEW',
        'recruiter_phone_screen',
        'jpet',
        'pending_graduation_followup',
    ];

    /**
     * @param Collection $data
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @param array $pastDue
     * @return JsonResponse
     * @throws Throwable
     */
    public function transform(
        Collection $data,
        CandidateApplicationsDTO $candidateApplicationsDTO,
        array $pastDue
    ): JsonResponse {
        $type = $this->getParentBucketTypeKeyword($candidateApplicationsDTO->getStatus());
        $datatable = DataTables::of($data)
            ->with('pastduecount', $pastDue['past_due_count'])
            ->setRowAttr([
                'class' => fn(stdClass $row) => $this->getRowClass($row, $candidateApplicationsDTO),
            ])
            ->addColumn('application_date', fn(stdClass $row) => $this->formatApplicationDate($row))
            ->addColumn('Action', fn(stdClass $row) => $this->getActionButtons($row, $type));

        if ($this->canShowCheckbox($candidateApplicationsDTO->getStatus())) {
            $datatable->addColumn('select', fn(stdClass $row) => $this->getCheckbox($row, $candidateApplicationsDTO))
                ->escapeColumns('select');
        } else {
            $datatable->escapeColumns('Action');
        }

        return $datatable->addIndexColumn()->make(true);
    }

    /**
     * @param stdClass $row
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return string
     */
    private function getRowClass(stdClass $row, CandidateApplicationsDTO $candidateApplicationsDTO): string
    {
        $now = now();
        $type = $candidateApplicationsDTO->getStatus();

        if ($candidateApplicationsDTO->isNursingParentBucket()) {
            if ($type === 'NEW' && !empty($row->application_date)) {
                $appliedAt = Carbon::parse($row->application_date);

                if ($appliedAt->isBefore($now->copy()->subHour())) {
                    return 'dashboard-older-record-cl-ra';
                }
                return '';
            }

            if ($type === 'recruiter_phone_screen' && !empty($row->FollowUpDateTime)) {
                $followUpAt = Carbon::parse($row->FollowUpDateTime);

                if ($followUpAt->isBefore($now->copy()->subDay())) {
                    return 'dashboard-older-record-cl-ra';
                }
                return '';
            }

            if (!empty($row->workFlowStateChangeDate)) {
                $changedAt = Carbon::parse($row->workFlowStateChangeDate);

                if ($changedAt->isBefore($now->copy()->subDay())) {
                    return 'dashboard-older-record-cl-ra';
                }
            }
        }

        if ($candidateApplicationsDTO->isTherapyParentBucket()) {
            if ($type === 'New_therapy' && !empty($row->application_date)) {
                $appliedAt = Carbon::parse($row->application_date);

                if ($appliedAt->isBefore($now->copy()->subHour())) {
                    return 'dashboard-older-record-cl-ra';
                }
                return '';
            }

            if ($type === 'PET_therapy') {
                $petDate = null;

                if (!empty($row->PET3Date)) {
                    $petDate = Carbon::parse($row->PET3Date);
                } elseif (!empty($row->PET2Date)) {
                    $petDate = Carbon::parse($row->PET2Date);
                } elseif (!empty($row->PET1Date)) {
                    $petDate = Carbon::parse($row->PET1Date);
                }

                if ($petDate) {
                    $threshold = $this->addBusinessHours($petDate->copy()->addDay(), 1);

                    if ($now->greaterThan($threshold)) {
                        return 'dashboard-older-record-cl-ra';
                    }
                }

                return '';
            }

            if ($type === 'recruiter_phone_screen_therapy' && !empty($row->FollowUpDateTime)) {
                $followUpAt = Carbon::parse($row->FollowUpDateTime);
                $threshold = $this->addBusinessHours($followUpAt->copy(), 1);

                if ($now->greaterThan($threshold)) {
                    return 'dashboard-older-record-cl-ra';
                }

                return '';
            }

            if ($type === JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value
                && !empty($row->scheduledRecruiterPhoneScreenDate)) {
                $scheduledAt = Carbon::parse($row->scheduledRecruiterPhoneScreenDate);
                $threshold = $this->addBusinessHours($scheduledAt->copy(), 1);

                if ($now->greaterThan($threshold)) {
                    return 'dashboard-older-record-cl-ra';
                }

                return '';
            }

            if (in_array($type, ['scheduled_clinical_interview_therapy'], true) && !empty($row->scheduleInterviewDate)) {

                $interviewAt = Carbon::parse($row->scheduleInterviewDate);
                $threshold = $this->addBusinessDays($interviewAt->copy(), 1);

                if ($now->greaterThan($threshold)) {
                    return 'dashboard-older-record-cl-ra';
                }

                return '';
            }

            if ($type === 'clinical_interview_therapy' && !empty($row->clinicalInterviewDate)) {
                $interviewAt = Carbon::parse($row->clinicalInterviewDate);

                if ($now->greaterThan($interviewAt->copy()->addHours(2))) {
                    return 'dashboard-older-record-cl-ra';
                }

                return '';
            }

            if ($type === 'pending_graduation_follow_up_therapy') {
                $followUpDateTime = Carbon::parse($row->FollowUpDateTime);

                if ($now->addDay()->greaterThan($followUpDateTime)) {
                    return 'dashboard-older-record-cl-ra';
                }
            }
        }

        return '';
    }

    /**
     * @param Carbon $date
     * @param int $days
     * @return Carbon
     */
    private function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();

        while ($days > 0) {
            $result->addDay();

            if (!$result->isWeekend()) {
                $days--;
            }
        }

        return $result;
    }

    /**
     * @param Carbon $date
     * @param int $hours
     * @return Carbon
     */
    private function addBusinessHours(Carbon $date, int $hours): Carbon
    {
        $result = $date->copy();

        while ($hours > 0) {
            $result->addHour();

            if ($this->isBusinessHour($result)) {
                $hours--;
            }
        }

        return $result;
    }

    /**
     * @param Carbon $date
     * @return bool
     */
    private function isBusinessHour(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $hour = (int)$date->format('G');

        return $hour >= 8 && $hour < 18;
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws DateMalformedStringException
     */
    private function formatApplicationDate(stdClass $row): string
    {
        return $this->getFormatedDateTime($row->application_date) ?: '-';
    }

    /**
     * @param string $status
     * @return bool
     * @throws Throwable
     */
    private function canShowCheckbox(string $status): bool
    {
        return LD::checkFeatureFlag('jobvite-assign-recruiter-to-job')
            && in_array($status, self::ASSIGN_RECRUITER_TO_JOB, true);
    }

    /**
     * @param stdClass $row
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return string
     * @throws Throwable
     */
    private function getCheckbox(stdClass $row, CandidateApplicationsDTO $candidateApplicationsDTO): string
    {
        return view('datatables.dashboard.jobvite.nurses.checkbox', [
            'id' => $row->id,
            'type' => $candidateApplicationsDTO->getStatus(),
        ])->render();
    }

    /**
     * @param stdClass $row
     * @param string $type
     * @return string
     * @throws Throwable
     */
    private function getActionButtons(stdClass $row, string $type): string
    {
        return view('datatables.dashboard.jobvite.nurses.buttons', [
            'id' => $row->id,
            'type' => $type,
        ])->render();
    }

    /**
     * @param string $type
     * @return string
     */
    private function getParentBucketTypeKeyword(string $type): string
    {
        return str_contains(
            $type,
            '_therap'
        ) ? self::THERAPY_PARENT_BUCKET_KEYWORD : self::NURSING_PARENT_BUCKET_KEYWORD;
    }

    /**
     * @param $date
     * @return bool
     */
    private function validateDate($date): bool
    {
        $dt = DateTime::createFromFormat(DateTimeInterface::RFC3339, $date);

        return $dt && $dt->format(DateTimeInterface::RFC3339) === $date;
    }
}
