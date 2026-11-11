<?php

namespace App\VBC\Http\Requests\Followup;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Followup\FollowupCreationDTO;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use App\VBC\Rules\FollowupEligibleTracker;

/** Creates a Follow-up "Open"ed against a selected hospitalization tracker (AC6/AC29). */
class CreateFollowupRequest extends CoreRequest
{
    public function __construct(private readonly FollowupReadRepository $repository)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'hospitalization_tracker_id' => ['required', 'integer', new FollowupEligibleTracker($this->repository)],
        ];
    }

    public function toDto(): FollowupCreationDTO
    {
        return new FollowupCreationDTO(
            hospitalizationTrackerId: (int) $this->input('hospitalization_tracker_id'),
        );
    }
}
