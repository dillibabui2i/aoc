<?php

namespace App\Repositories\App\CandidateApplications;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;
use App\Enums\FeatureFlagsEnum;
use App\Enums\JobViteRecruiterJobType;
use App\Enums\JobviteTherapyOutcomeEnum;
use App\Helpers\LD;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class CandidateApplicationsReadRepository
 *
 * @package App\Repositories\App\CandidateApplications
 */
class CandidateApplicationsReadRepository
{
    public function __construct(
        public readonly ?string $entity = null,
        public readonly ?string $entity_id = null,
        public readonly ?string $type = null,
        public readonly ?string $notes = null,
        public readonly ?Carbon $completed_at = null
    ) {
    }

    private const APP_SENT_DATE = '2022-11-28';
    private const STATUS_NEW = 'new';
    private const STATUS_JPET = 'jpet';
    private const STATUS_JPET_THERAPY = 'PET_therapy';
    private const STATUS_PENDING_GRADUATION_FOLLOWUP = 'pending_graduation_followup';
    private const STATUS_PENDING_GRADUATION_FOLLOWUP_THERAPY = 'pending_graduation_follow_up_therapy';
    private const STATUS_RECRUITER_PHONE_SCREEN = 'recruiter_phone_screen';
    private const STATUS_RECRUITER_PHONE_SCREEN_THERAPY = 'recruiter_phone_screen_therapy';
    private const STATUS_OFFER_EXTENDED_THERAPY = 'offer_extended_therapy';
    private const STATUS_OFFER_EXTENDED_THERAPY_FILTER = 'Offer Extended';
    private const PET_COLUMNS_FILTER = ['workFlowStateChangeDate', 'PET1Date', 'PET2Date', 'PET3Date'];
    private const JPET_WORKFLOW_STATE_FILTER = ['P.E.T. #1', 'P.E.T. #2', 'P.E.T. #3'];
    private const RECRUITER_PHONE_SCREEN_FILTER = 'Recruiter Phone Screen';
    private const EMPTY_WORKFLOW_STATE_CHANGE_DATE = '0000-00-00 00:00:00';
    private const NURSE_JOB_CATEGORY_KEYWORD = 'nurs';
    private const THERAPY_JOB_CATEGORY_KEYWORD = 'therap';

    /**
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return Collection
     * @throws Exception
     */
    public function getFilteredNursesApplications(CandidateApplicationsDTO $candidateApplicationsDTO): Collection
    {
        return $this->getFilteredNursesApplicationsBaseQuery($candidateApplicationsDTO)->get();
    }

    /**
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return array
     * @throws Exception
     */
    public function getFilteredNursesApplicationsPastDueCount(CandidateApplicationsDTO $candidateApplicationsDTO): array
    {
        $query = $this->getFilteredNursesApplicationsBaseQuery($candidateApplicationsDTO);
        $query = match ($candidateApplicationsDTO->getStatus()) {
            strtoupper(self::STATUS_NEW) => $query
                ->select([
                    'candidate_applications.id',
                    'candidate_applications.candidate_id',
                    'candidates.fullName',
                    'users.name as recruiter_name',
                    'jobs.title',
                    'candidate_applications.application_date',
                    DB::raw('TIMESTAMPDIFF(HOUR, candidate_applications.application_date, NOW()) as hours_diff'),
                    DB::raw('TIMESTAMPDIFF(HOUR, candidate_applications.application_date, NOW()) > 1 as is_past_due'),
                ]),
            self::STATUS_RECRUITER_PHONE_SCREEN => $query
                ->select([
                    'candidate_applications.id',
                    'candidate_applications.candidate_id',
                    DB::raw('TIMESTAMPDIFF(DAY, candidates.FollowUpDateTime, NOW()) as days_diff'),
                    DB::raw('TIMESTAMPDIFF(DAY, candidates.FollowUpDateTime, NOW()) > 1 as is_past_due'),
                ]),
            JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value => $query
                ->select([
                    'candidate_applications.id',
                    'candidate_applications.candidate_id',
                    DB::raw('TIMESTAMPDIFF(HOUR, candidates.scheduledRecruiterPhoneScreenDate, NOW()) as hours_diff'),
                    DB::raw('TIMESTAMPDIFF(HOUR, candidates.scheduledRecruiterPhoneScreenDate, NOW()) > 1 as is_past_due'),
                ]),
            default => $query
                ->select([
                    'candidate_applications.id',
                    'candidate_applications.candidate_id',
                    DB::raw('TIMESTAMPDIFF(DAY, candidates.workFlowStateChangeDate, NOW()) as days_diff'),
                    DB::raw('TIMESTAMPDIFF(DAY, candidates.workFlowStateChangeDate, NOW()) > 1 as is_past_due'),
                ]),
        };

        $total = $query->count();

        return [
            'past_due_count' => $query->having('is_past_due', 1)->count(),
            'total' => $total,
        ];
    }

    /**
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return Builder
     * @throws Exception
     */
    private function getFilteredNursesApplicationsBaseQuery(CandidateApplicationsDTO $candidateApplicationsDTO): Builder
    {
        $currentDateTime = Carbon::now()->toDateString();
        $tomorrowDateTime = Carbon::tomorrow()->toDateString();
        $query = DB::table(
            DB::raw('(SELECT candidate_id, MAX(id) AS maxId FROM candidate_applications GROUP BY candidate_id) AS x')
        )
            ->select([
                'candidates.appWorkFlowState',
                'candidates.id',
                'users.name AS primaryRecruiter',
                'candidates.fullName',
                'jobs.title',
                'jobs.title AS requisition',
                'jobs.locationCity',
                'candidates.appSentDate AS application_date',
                'candidates.mobile',
                'candidates.homePhone',
                'candidates.email',
                DB::raw(
                    "DATE_FORMAT(candidates.FollowUpDateTime, '"
                    . config('constant.mysqlsearchdatetimeformat')
                    . "') AS FollowUpDateTime"
                ),
                DB::raw(
                    "DATE_FORMAT(candidates.scheduledRecruiterPhoneScreenDate, '"
                    . config('constant.mysqlsearchdatetimeformat')
                    . "') AS scheduledRecruiterPhoneScreenDate"
                ),
                DB::raw("DATE_FORMAT(candidates.GraduationDate, '%m/%d/%Y') AS GraduationDate"),
                'candidates.workFlowStateChangeDate',
                DB::raw("DATE_FORMAT(candidates.clinicalInterviewDate, '%m/%d/%Y %h:%i') AS clinicalInterviewDate"),
                DB::raw("DATE_FORMAT(candidates.scheduleInterviewDate, '%m/%d/%Y %h:%i') AS scheduleInterviewDate"),
                DB::raw("DATE_FORMAT(candidates.rescheduleInterviewDate, '%m/%d/%Y %h:%i') AS rescheduleInterviewDate"),
            ]);

        if (
            $candidateApplicationsDTO->hasJobviteAddCandidateNoteFlag()
            && $this->hasBasicStatuses($candidateApplicationsDTO)) {
            $query->addSelect(
                DB::raw(
                    "(SELECT CONCAT(DATE_FORMAT(notes.created_at, '%m/%d'), ' ', users.name, ' commented: ', notes.notes)
                    FROM notes
                    LEFT JOIN users ON users.id = notes.created_by
                    WHERE entity = 'candidate'
                    AND entity_id = candidates.id
                    ORDER BY notes.id DESC
                    LIMIT 1
                    ) AS Candidate_Notes"
                )
            );
        } else {
            $query->addSelect(DB::raw('NULL AS Candidate_Notes'));
        }

        $query->leftJoin('candidate_applications', function (Builder $q) {
            $q->on('x.maxId', '=', 'candidate_applications.id');
        });
        $query->leftJoin('candidates', function (Builder $q) {
            $q->on('candidate_applications.candidate_id', '=', 'candidates.id');
        });
        $query->leftJoin('users', function (Builder $q) {
            $q->on('users.id', '=', 'candidates.user_id');
        });
        $query->leftJoin('jobs', function (Builder $q) {
            $q->on('candidate_applications.job_id', '=', 'jobs.id');
        });

        $query->leftJoin('users as therapy_clinical_manager', function (Builder $q) {
            $q->on(
                'therapy_clinical_manager.id',
                '=',
                'candidates.interviewManager'
            );
        });
        $query->addSelect(
            'therapy_clinical_manager.name AS therapyClinicalManager'
        );
        $query->whereDate('candidates.appSentDate', '>', self::APP_SENT_DATE);

        if ($candidateApplicationsDTO->isNursingParentBucket()) {
            $query->where('jobs.category', 'like', sprintf('%%%s%%', self::NURSE_JOB_CATEGORY_KEYWORD));
        }

        if ($candidateApplicationsDTO->isTherapyParentBucket()) { //TODO apply FF for therapist
            $query->where('jobs.category', 'like', sprintf('%%%s%%', self::THERAPY_JOB_CATEGORY_KEYWORD));
        }


//        if (LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value)) {
//            $query->where('jobs.category', 'like', sprintf('%%%s%%', self::NURSE_JOB_CATEGORY_KEYWORD));
//        }

        if ($candidateApplicationsDTO->hasAuthUserFilter()) {
            $query = $this->applyAuthUserFilters($query, $candidateApplicationsDTO);
        }

        $query = $this->applyStatusFilters($query, $candidateApplicationsDTO, $tomorrowDateTime, $currentDateTime);

        return $this->applyMainFilters($query, $candidateApplicationsDTO);
    }

    /**
     * @param Builder $builder
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @param string $tomorrowDateTime
     * @param string $currentDateTime
     * @return Builder
     * @throws Exception
     */
    private function applyStatusFilters(
        Builder $builder,
        CandidateApplicationsDTO $candidateApplicationsDTO,
        string $tomorrowDateTime,
        string $currentDateTime
    ): Builder {
        switch ($candidateApplicationsDTO->getStatus()) {
            case strtoupper(self::STATUS_NEW):
            case 'NEW_therapy':
                $builder->where('appWorkFlowState', ucfirst(self::STATUS_NEW));
                break;

            case self::STATUS_OFFER_EXTENDED_THERAPY:
                $builder->where('appWorkFlowState', self::STATUS_OFFER_EXTENDED_THERAPY_FILTER);
                break;

            case 'clinical_interview_therapy':
                $builder->where('appWorkFlowState', 'Clinical Interview');
                break;

            case 'rescheduled_clinical_interview_therapy':
                $builder->where('appWorkFlowState', 'Rescheduled Clinical Interview');
                break;

            case 'scheduled_clinical_interview_therapy':
                $states = ['Scheduled Clinical Interview'];
                if ($candidateApplicationsDTO->isTherapyCareManagerRole()) {
                    $states[] = 'Rescheduled Clinical Interview';
                }

                $builder->whereIn('appWorkFlowState', $states);
                break;

            case self::STATUS_RECRUITER_PHONE_SCREEN:
            case self::STATUS_RECRUITER_PHONE_SCREEN_THERAPY:
                $builder->where('appWorkFlowState', self::RECRUITER_PHONE_SCREEN_FILTER)
                    ->where(function ($q) use ($tomorrowDateTime) {
                        $q->where('FollowUpDateTime', '<', $tomorrowDateTime)
                            ->orWhereNull('FollowUpDateTime')
                            ->orWhere('workFlowStateChangeDate', self::EMPTY_WORKFLOW_STATE_CHANGE_DATE);
                    });
                break;

            case JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value:
                $builder->where('appWorkFlowState', JobviteTherapyOutcomeEnum::SCHEDULED_RECRUITER_PHONE_SCREEN->value);
                break;

            case self::STATUS_JPET:
            case self::STATUS_JPET_THERAPY:
                $builder->whereIn('appWorkFlowState', self::JPET_WORKFLOW_STATE_FILTER);

                foreach (self::PET_COLUMNS_FILTER as $column) {
                    $builder->where(function (Builder $q) use ($currentDateTime, $column) {
                        $q->where($column, '<', $currentDateTime)
                            ->orWhereNull($column)
                            ->orWhere($column, self::EMPTY_WORKFLOW_STATE_CHANGE_DATE);
                    });
                }

                if (
                    $candidateApplicationsDTO->hasAddPetFilterToJobvitePetBucketFlag()
                    && $candidateApplicationsDTO->hasFilters()
                ) {
                    $pet = collect($candidateApplicationsDTO->getFilters())->firstWhere('name', 'pet')['value'] ?? '';

                    if ($pet !== '') {
                        $builder->where('appWorkFlowState', $pet);
                    }
                }
                break;

            case self::STATUS_PENDING_GRADUATION_FOLLOWUP:
                $builder->where('appWorkFlowState', 'Nursing Student: Pending Graduation')
                    ->where(function (Builder $q) use ($tomorrowDateTime) {
                        $q->where('FollowUpDateTime', '<', $tomorrowDateTime)
                            ->orWhereNull('FollowUpDateTime')
                            ->orWhere('workFlowStateChangeDate', self::EMPTY_WORKFLOW_STATE_CHANGE_DATE);
                    })
                    ->where(function (Builder $q) use ($currentDateTime) {
                        $q->where('workFlowStateChangeDate', '<', $currentDateTime)
                            ->orWhereNull('workFlowStateChangeDate')
                            ->orWhere('workFlowStateChangeDate', self::EMPTY_WORKFLOW_STATE_CHANGE_DATE);
                    });
                break;

            case self::STATUS_PENDING_GRADUATION_FOLLOWUP_THERAPY:
                if ($candidateApplicationsDTO->isTherapyParentBucket()) {
                    $builder->where('appWorkFlowState', 'Therapist Student: Pending Graduation');
                }
                break;
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return Builder
     */
    private function applyMainFilters(Builder $builder, CandidateApplicationsDTO $candidateApplicationsDTO): Builder
    {
        if ($candidateApplicationsDTO->hasFilters()) {
            $filters = collect($candidateApplicationsDTO->getFilters())
                ->filter(fn($f) => !empty($f['value']))
                ->groupBy('name')
                ->map(fn($group) => $group->pluck('value')->all());

            $state = $filters->get('jobvitestate', []);
            $category = $filters->get('Category', []);
            $branch = $filters->get('branch', []);
            $recruiter = $filters->get('recruiter', []);
            $region = $filters->get('region', []);

            if ($state) {
                $builder->whereIn('jobs.state', $state);
            }

            if ($branch) {
                $builder->whereIn('jobs.branch', $branch);
            }

            if ($region) {
                $regionValues = collect($region)
                    ->flatMap(fn($r) => explode(',', $r))
                    ->map(fn($r) => trim($r))
                    ->filter()
                    ->values()
                    ->all();

                if ($regionValues) {
                    $builder->whereIn('jobs.branch', $regionValues);
                }
            }

            if ($recruiter) {
                $builder->whereIn('candidates.user_id', $recruiter);
            }

            if ($category) {
                $builder->whereIn('jobs.category', $category);
            }
        }

        return $builder;
    }

    /**
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return bool
     */
    private function hasBasicStatuses(CandidateApplicationsDTO $candidateApplicationsDTO): bool
    {
        $type = $candidateApplicationsDTO->getStatus();

        return in_array($type, [
            JobViteRecruiterJobType::NEW->value,
            JobViteRecruiterJobType::NEW_THERAPY->value,
            JobViteRecruiterJobType::RECRUITER_PHONE_SCREEN->value,
            JobViteRecruiterJobType::RECRUITER_PHONE_SCREEN_THERAPY->value,
            JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value,
            JobViteRecruiterJobType::JPET->value,
            JobViteRecruiterJobType::JPET_THERAPY->value,
            JobViteRecruiterJobType::PENDING_GRADUATION_FOLLOWUP,
            JobViteRecruiterJobType::PENDING_GRADUATION_FOLLOWUP_THERAPY,
        ]);
    }

    /**
     * @param Builder $builder
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return Builder
     */
    private function applyAuthUserFilters(Builder $builder, CandidateApplicationsDTO $candidateApplicationsDTO): Builder
    {
        $builder->where(function (Builder $q) use ($candidateApplicationsDTO) {
            $q->where('jobs.recruiters', 'like', sprintf('%%%s%%', $candidateApplicationsDTO->getAuthUserEmail()))
                ->orWhere('candidates.user_id', $candidateApplicationsDTO->getAuthUserId());

            if ($candidateApplicationsDTO->isTherapyCareManagerRole() && $candidateApplicationsDTO->isTherapyParentBucket()) {
                $q->orWhere('candidates.interviewManager', $candidateApplicationsDTO->getAuthUserId());
            }
        });

        return $builder;
    }
}
