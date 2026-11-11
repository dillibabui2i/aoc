<?php

namespace App\Http\Requests\Candidates;

use App\Helpers\LD;
use App\Http\Requests\CoreRequest;
use App\Jobs;
use App\Repositories\App\JobCategories\JobCategoriesReadRepository;
use App\Rules\AvailableRequisition;
use App\Services\Candidate\CandidateService;

class StoreCandidateRequest extends CoreRequest
{
    /**
     * The form lets a recruiter attach up to three requisitions ("Add More").
     */
    private const MAX_REQUISITIONS = 3;

    public function __construct(
      private CandidateService $service,
      private JobCategoriesReadRepository $jobCategories,
      private Jobs $jobs,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $rules = [
          'firstName' => ['required', 'string', 'max:255'],
          'lastName' => ['required', 'string', 'max:255'],
          'title' => ['required', 'string', 'max:255'],
          'email' => ['required', 'email', 'max:255'],
          'mobile' => ['nullable', 'string', 'max:255'],
          'address' => ['nullable', 'string', 'max:255'],
          'state' => ['nullable', 'string', 'max:255'],
          'city' => ['nullable', 'string', 'max:255'],
          'postalCode' => ['nullable', 'string', 'max:255'],
          'comments' => ['nullable', 'string'],
          // The form posts one entry per requisition row, so every id is validated on its own -
          // a comma-separated string could never be checked against the jobs table.
          'job_id' => ['required', 'array', 'min:1', 'max:' . self::MAX_REQUISITIONS],
          'job_id.*' => ['required', 'integer', 'distinct', new AvailableRequisition($this->availableCategories(), $this->jobs)],
          'source' => ['nullable', 'string', 'max:255'],
          'source_type' => ['nullable', 'string', 'max:255'],
          'recruiter' => ['nullable', 'email'],
          'jobCategoryType' => ['sometimes'],
        ];

        if (LD::checkFeatureFlag('add-custom-field-to-candidate')) {
            $rules = array_merge($rules, $this->getCustomFieldsRules());
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
          'firstName.required' => 'First name is required.',
          'lastName.required' => 'Last name is required.',
          'email.required' => 'Email is required.',
          'email.email' => 'Email format is invalid.',
          'job_id.required' => 'Please select a requisition from the list.',
          'job_id.array' => 'Please select a requisition from the list.',
          'job_id.min' => 'Please select a requisition from the list.',
          'job_id.max' => 'You can attach up to ' . self::MAX_REQUISITIONS . ' requisitions to a candidate.',
          'job_id.*.required' => 'Please select a requisition from the list.',
          'job_id.*.integer' => 'Please select a requisition from the list.',
          'job_id.*.distinct' => 'The same requisition is selected more than once.',
        ];
    }

    /**
     * The same list the form builds its category dropdown from.
     *
     * @see \App\Http\Controllers\CandidatesController::getAddCandidateForm
     */
    private function availableCategories(): array
    {
        return $this->jobCategories->getJobCategoriesFilteredByKeyword($this->input('jobCategoryType'));
    }

    private function getCustomFieldsRules(): array
    {
        $rules = [];

        $customFields = $this->service
            ->getCustomFieldsForCandidate()
            ->map
            ->only(['db_field_name', 'input_type', 'is_required']);

        foreach ($customFields as $field) {
            $fieldRules = [];

            if ($field['is_required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field['input_type'] === 'mdropdown') {
                $fieldRules[] = 'array';
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            }

            $rules["custom_fields.{$field['db_field_name']}"] = $fieldRules;
        }

        return $rules;
    }

}
