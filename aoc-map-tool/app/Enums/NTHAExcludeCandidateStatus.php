<?php

namespace App\Enums;

enum NTHAExcludeCandidateStatus: string
{
    case CandidateStart = 'Candidate Start';
    case ScheduleNextStep = 'Schedule Next Step';
    case ClientMatch = 'Client Match';

    public static function excludedNthAlertStatuses(): array
    {
        return [
            self::CandidateStart->value,
            self::ScheduleNextStep->value,
            self::ClientMatch->value,
        ];
    }

    public static function getExcludedNthAlertStatusesForSql(): string
    {
        return "'" . implode("', '", self::excludedNthAlertStatuses()) . "'";
    }
}
