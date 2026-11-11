<?php

namespace App\Services\NationalRecruitingReport;

use App\DTO\NationalRecruitingReport\TableParams;
use App\Services\NationalRecruitingReport\Tabs\CandidateStartsService;
use App\Services\NationalRecruitingReport\Tabs\GrossMarginService;
use App\Services\NationalRecruitingReport\Tabs\IpeasService;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

class NRRFactoryService
{
    /**
     * @param string $tab
     * @param TableParams $params
     * @return Application|mixed
     */
    public function make(string $tab, TableParams $params): mixed
    {
        return match ($tab) {
            'candidateStarts' => app(CandidateStartsService::class, ['params' => $params]),
            'IPEAs' => app(IPEAsService::class, ['params' => $params]),
            'grossMargin' => app(GrossMarginService::class, ['params' => $params]),
            default => throw new InvalidArgumentException('Unknown tab:' . $tab),
        };
    }
}
