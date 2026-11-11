<?php

namespace App\DTO\ManagePayer;

use App\Enums\ManagePayer\GrossMarginLobEnum;

readonly class ManagePayerRowParamsDTO
{
    public function __construct(
        public readonly ?int  $id = null,
        public readonly GrossMarginLobEnum $lob,
        public readonly array $branchIds,
        public readonly ?string $recruiterCoverageTarget,
        public readonly ?string $recruiterEscalated,
        public readonly ?string $tlManagerEscalated,
        public readonly ?string $tlManagerWholeHomeEscalated,
        public readonly ?string $execTeamEscalated,
        public readonly ?string $coverageEscalated,
        public readonly ?string $coverageTlManagerEscalated,
        public readonly ?string $coverageTlManagerWholeHomeEscalated,
        public readonly ?string $coverageExecTeamEscalated,
    )
    {
    }
}
