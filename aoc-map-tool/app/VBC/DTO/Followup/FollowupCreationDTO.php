<?php

namespace App\VBC\DTO\Followup;

final readonly class FollowupCreationDTO
{
    public function __construct(
        public int $hospitalizationTrackerId,
    ) {
    }
}
