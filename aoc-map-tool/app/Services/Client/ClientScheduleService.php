<?php

namespace App\Services\Client;

use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\Client\ClientScheduleReadRepository;
use Exception;
use Illuminate\Support\Collection;

class ClientScheduleService
{
    /**
     * @param ClientScheduleReadRepository $clientScheduleReadRepository
     */
    public function __construct(
        protected ClientScheduleReadRepository $clientScheduleReadRepository
    )
    {
    }

    /**
     * @param string $term
     * @return mixed
     * @throws Exception
     */
    public function getClient(string $term): Collection
    {
        $clientList = $this->clientScheduleReadRepository->getClientSchedule($term);

        $clientList = $clientList->map(function ($item) {

            $item->is_specialized = 0;

            if (
                LD::checkFeatureFlag('schedule_trach_vent_tpn_class') &&
                H::canPermissions('showscheduleclassform') &&
                !empty($item->CF_Skills) &&
                H::getHelper('NTHChecklist')->isSpecialized($item->CF_Skills)
            ) {
                $item->is_specialized = 1;
            }

            return $item;
        });

        return $clientList;
    }
}
