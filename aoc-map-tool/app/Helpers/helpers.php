<?php

function formatDateForDb(?string $date): ?string
{
    if (!empty($date) && $date !== '0000-00-00') {
        return date('Y-m-d', strtotime($date));
    }

    return null;
}

function getFormatedDate($date)
{
    if ($date != '0000-00-00' && $date != '0000-00-00 00:00:00' && $date != '') {
        return date('m/d/Y', strtotime($date));
    }

    return '';
}

function getFormatedTime($datetime)
{
    if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
        $datetime = new \DateTime($datetime);

        return $datetime->format('g:i A');
    }

    return '';
}

/**
 * "g:i A - g:i A" for a class time range; start only when there is no end time.
 */
function getFormatedTimeRange(?string $startTime, ?string $endTime): string
{
    $start = getFormatedTime($startTime);

    if ($start === '' || !$endTime) {
        return $start;
    }

    return $start . ' - ' . getFormatedTime($endTime);
}

function getFormatedDateTime($datetime)
{
    if ($datetime != '0000-00-00 00:00:00' && $datetime != '') {
        $datetime = new \DateTime($datetime);

        return $datetime->format('m/d/y g:i A');
    }

    return '';
}

/**
 * US number (any formatting) to E.164, e.g. (570) 555-0142 => +15705550142.
 */
function toE164(string $phone): string
{
    return '+1' . preg_replace('/\D/', '', $phone);
}

/**
 * E.164 US number to (###) ###-####. Returns '' for empty input.
 */
function formatPhone(?string $e164): string
{
    $digits = substr(preg_replace('/\D/', '', (string)$e164), -10);

    if (strlen($digits) !== 10) {
        return '';
    }

    return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
}
