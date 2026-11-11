<?php

namespace App\Transformer\Dashboard\Buckets\Jobvite;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;

class ApplicationsTransformerFactory
{
    public function make(CandidateApplicationsDTO $dto): AbstractApplicationsTransformer
    {
        if ($dto->isTherapyBucket()) {
            return new TherapyApplicationsTransformer();
        }

        return new NursesApplicationsTransformer();
    }
}
