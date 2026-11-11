<?php

namespace App\Traits;

use DateMalformedStringException;
use DateTime;

/**
 * Class DateFormatter
 *
 * @package App\Traits
 */
trait DateFormatter
{
    /**
     * @param $datetime
     * @return string
     * @throws DateMalformedStringException
     */
    public function getFormatedDateTime($datetime): string
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new DateTime($datetime);

            return $datetime->format('m/d/y g:i A');
        }

        return '';
    }

    /**
     * @param $datetime
     * @return string
     * @throws DateMalformedStringException
     */
    public function getFormatedLongDateTime($datetime): string
    {
        if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
            $datetime = new DateTime($datetime);

            return $datetime->format('m/d/Y g:i A');
        }

        return '';
    }
}
