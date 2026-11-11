<?php

namespace App\Services\Candidate;

use App\Candidates;
use App\CustomFields;
use App\Enums\EntityTypeEnum;
use App\Events\CandidateFieldUpdated;
use App\Helpers\LD;
use App\Jobs;
use App\Repositories\Candidates\CandidateReadRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateService
{

    public function __construct(
        private CustomFields $customField,
        private CandidateReadRepository $candidateReadRepository,
        private Jobs $jobs,
    ) {}

    public function create(array $data): Candidates
    {
        $jobIds = $this->normalizeJobIds($data['job_id'] ?? []);
        $eIdsByJobId = $this->jobEIds($jobIds);
        $eIds = array_map(static fn (int $jobId) => $eIdsByJobId[$jobId] ?? '', $jobIds);

        $candidateData = [
          'email' => $data['email'],
          'firstName' => $data['firstName'],
          'lastName' => $data['lastName'],
          'fullName' => "{$data['firstName']} {$data['lastName']}",
          'Search_Name' => "{$data['firstName']} {$data['lastName']} {$data['firstName']}",
          'address' => $data['address'] ?? '',
          'stateCode' => $data['state'] ?? '',
          'city' => $data['city'] ?? '',
          'postalCode' => $data['postalCode'] ?? '',
          'title' => $data['title'],
          'mobile' => $data['mobile'] ?? '',
          'jobRecruiters' => $data['recruiter'] ?? '',
          'jobCategoryName' => '',
          'jobTitle' => '',
          'jobId' => implode(',', $jobIds),
          'comments' => $data['comments'] ?? '',
          // Resolved from the jobs table rather than taken from the request, so the stored
          // eIds always match the requisitions the candidate is actually attached to.
          'eId' => implode(',', $eIds),
          'application_date' => now()->toDateString(),
          'appSentDate' => now(),
          'created_by' => Auth::id(),
          'updated_by' => Auth::id(),
          'user_id' => $data['recruiter_id'] ?? 0,
          'appWorkflowState' => 'New',
          'appSource' => $data['source'] ?? '',
          'appSourceType' => $data['source_type'] ?? '',
        ];

        $customFieldTypes = collect();

        if (LD::checkFeatureFlag('add-custom-field-to-candidate')) {
            if (!empty($data['custom_fields']) && is_array($data['custom_fields'])) {
                $customFieldTypes = $this->getCustomFieldsForCandidate()
                    ->pluck('input_type', 'db_field_name');

                foreach ($data['custom_fields'] as $key => $value) {
                    $type = $customFieldTypes[$key] ?? null;
                    $value = match ($type) {
                        'date' => empty($value)
                            ? null
                            : Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d'),

                        'mdropdown' => is_array($value)
                            ? implode(',', $value)
                            : $value,

                        default => $value,
                    };

                    $candidateData[$key] = is_array($value) ? implode(',', $value) : $value;
                }
            }
        }

        $candidate = Candidates::query()->create($candidateData);

        // Add activity notes when CFs are first set
        if (LD::checkFeatureFlag('add-custom-field-to-candidate')) {
            if ($customFieldTypes->isNotEmpty()) {
                foreach ($customFieldTypes->keys() as $key) {
                    if (array_key_exists($key, $candidateData) &&
                        $candidateData[$key] !== null && $candidateData[$key] !== '') {
                        $oldValue = '';
                        $newValue = $candidateData[$key];
                        $type = $customFieldTypes[$key] ?? null;

                        [$logOldValue, $logNewValue] = $this->formatValuesForLog($oldValue, $newValue, $type);

                        event(new CandidateFieldUpdated(
                            candidate: $candidate,
                            field: $key,
                            oldValue: $logOldValue,
                            newValue: $logNewValue,
                            userId: Auth::id()
                        ));
                    }
                }
            }
        }

        return $candidate;
    }

    public function attachApplications(Candidates $candidate, array $data): void
    {
        $jobIds = $this->normalizeJobIds($data['job_id'] ?? []);

        if ($jobIds === []) {
            // Guarded by StoreCandidateRequest - a candidate saved without an application
            // row loses its requisition and drops out of the recruiting buckets, so make
            // the skip visible instead of silently disconnecting the candidate.
            Log::warning('attachApplications skipped - candidate saved without a requisition', [
              'candidate_id' => $candidate->id,
            ]);

            return;
        }

        $userId = Auth::id();
        $currentDateTime = $candidate->created_at;

        $eIds = $this->jobEIds($jobIds);

        foreach ($jobIds as $jobId) {
            DB::table('candidate_applications')->insert([
              'candidate_id' => $candidate->id,
              'job_id' => $jobId,
              'eId' => $eIds[$jobId] ?? '',
              'workflowState' => 'New',
              'personalDataProcessingStatus' => 'active',
              'source' => $data['source'] ?? '',
              'sourceType' => $data['source_type'] ?? '',
              'application_date' => $currentDateTime->toDateString(),
              'created_by' => $userId,
              'updated_by' => $userId,
              'created_at' => $currentDateTime,
              'updated_at' => $currentDateTime,
              'lastUpdatedDate' => $currentDateTime,
              'sentDate' => $currentDateTime,
            ]);
        }
    }

    /**
     * The add-candidate form posts one job_id per requisition row; older callers passed a
     * comma-separated list, so both shapes are accepted here.
     *
     * @return int[]
     */
    private function normalizeJobIds(mixed $jobIds): array
    {
        if (is_string($jobIds)) {
            $jobIds = explode(',', $jobIds);
        }

        $jobIds = array_map('intval', (array) $jobIds);

        return array_values(array_unique(array_filter($jobIds)));
    }

    /**
     * @param int[] $jobIds
     * @return array<int, string> eId keyed by job id
     */
    private function jobEIds(array $jobIds): array
    {
        if ($jobIds === []) {
            return [];
        }

        return $this->jobs->query()
            ->whereIn('id', $jobIds)
            ->pluck('eId', 'id')
            ->all();
    }

    public function getCustomFieldsForCandidate(): Collection
    {
        return $this->customField->query()
            ->where('entity', EntityTypeEnum::CANDIDATE)
            ->where('is_deleted', 0)
            ->orderBy('sort')
            ->get();
    }

    public function formatBooleanForLog($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (int)$value === 1 ? 'Yes' : 'No';
    }

    public function formatValuesForLog(mixed $oldValue, mixed $newValue, string $type): array
    {
        $logOld = $oldValue;
        $logNew = $newValue;

        if ($type === 'boolean') {
            $logOld = $this->formatBooleanForLog($oldValue);
            $logNew = $this->formatBooleanForLog($newValue);
        }

        if ($type === 'date') {
            $logOld = $oldValue ? Carbon::parse($oldValue)->format('m/d/Y') : null;
            $logNew = $newValue ? Carbon::parse($newValue)->format('m/d/Y') : null;
        }

        return [$logOld, $logNew];
    }

    public function normalizeCustomFieldValueByType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'date' => empty($value)
                ? null
                : Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d'),

            'mdropdown' => is_array($value)
                ? implode(',', $value)
                : $value,

            'boolean' => $value === '' || $value === null
                ? null
                : (int) $value,

            default => $value,
        };
    }

    public function isTherapy(?string $category = null): bool
    {
        if (is_null($category)) {
            return false;
        }

        return str_contains(strtolower($category), 'therap');
    }

    public function isTherapyForCandidate(int $candidateId): bool
    {
        return $this->isTherapy($this->candidateReadRepository->getJobCategoryForCandidate($candidateId));
    }
}
