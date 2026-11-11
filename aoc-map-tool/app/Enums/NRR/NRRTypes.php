<?php

namespace App\Enums\NRR;

enum NRRTypes: string
{
    case RECRUITER = 'recruiter';
    case INTAKE_COORDINATOR = 'intake_coordinator';

    case APPROVER = 'approver';
    case BRANCH = 'branch';

    case IPEA = 'ipea';
    case PEPI = 'pepi';
    case WEPI = 'wepi';
    case CM = 'cm';

    case VIEW_HOURS = 'hours';
    case VIEW_COUNT = 'count';

    public static function getIPWTypes(): array
    {
        return [
            self::IPEA->value,
            self::PEPI->value,
            self::WEPI->value,
            self::CM->value,
        ];
    }
}
