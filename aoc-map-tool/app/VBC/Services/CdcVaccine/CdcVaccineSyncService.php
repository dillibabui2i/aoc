<?php

namespace App\VBC\Services\CdcVaccine;

use App\Mail\VBC\CdcVaccineGroupsNeedAocNameMail;
use App\VBC\Repositories\CdcVaccine\CdcVaccineGroupReadRepository;
use App\VBC\Repositories\CdcVaccine\CdcVaccineGroupWriteRepository;
use App\VBC\Repositories\CdcVaccine\CdcVaccineWriteRepository;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;

class CdcVaccineSyncService
{
    public function __construct(
        private CdcXmlDownloader $downloader,
        private CdcVaxToGroupXmlParser $groupParser,
        private CdcVaccineXmlParser $vaccineParser,
        private CdcVaccineGroupReadRepository $groupReadRepository,
        private CdcVaccineGroupWriteRepository $groupWriteRepository,
        private CdcVaccineWriteRepository $vaccineWriteRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Downloads and parses first; the DB is written only when the whole file is valid.
     *
     * @throws CdcSyncException
     */
    public function syncGroups(): int
    {
        $parsed = $this->groupParser->parse($this->downloader->download((string) config('cdc_vaccines.groups_url')));

        $this->groupWriteRepository->upsertMany($parsed->groups);

        return count($parsed->groups);
    }

    /**
     * @throws CdcSyncException
     */
    public function syncVaccines(): int
    {
        $groupMap = $this->groupParser
            ->parse($this->downloader->download((string) config('cdc_vaccines.groups_url')))
            ->vaccineGroupCodes;
        $vaccines = $this->vaccineParser
            ->parse($this->downloader->download((string) config('cdc_vaccines.vaccines_url')));

        $knownGroups = array_flip($this->groupReadRepository->existingGroupCodes());

        $prepared = [];
        foreach ($vaccines as $cvxCode => $vaccine) {
            $groupCode = $groupMap[$cvxCode] ?? null;

            if ($groupCode !== null && !isset($knownGroups[$groupCode])) {
                $this->logger->warning('CDC vaccine references an unknown group; stored without a group', [
                    'cvx_code' => $cvxCode,
                    'cvx_group_code' => $groupCode,
                ]);
                $groupCode = null;
            }
            $prepared[$cvxCode] = $vaccine->withGroupCode($groupCode);
        }

        $this->vaccineWriteRepository->upsertMany($prepared);

        return count($prepared);
    }

    /**
     * @return int number of groups listed in the email (0 = nothing sent)
     */
    public function notifyGroupsWithoutAocName(): int
    {
        $groups = $this->groupReadRepository->getWithoutAocName();

        if ($groups->isEmpty()) {
            return 0;
        }

        $recipient = config('cdc_vaccines.notification_recipient');

        if (!is_string($recipient) || trim($recipient) === '') {
            $this->logger->warning('CDC vaccine groups need an AOC name but no notification recipient is configured', [
                'groups' => $groups->count(),
            ]);

            return 0;
        }

        $rows = [];
        foreach ($groups as $group) {
            $rows[] = ['cvx_group_code' => $group->cvx_group_code, 'cdc_name' => $group->cdc_name];
        }

        Mail::to(trim($recipient))->send(new CdcVaccineGroupsNeedAocNameMail($rows));

        return count($rows);
    }
}
