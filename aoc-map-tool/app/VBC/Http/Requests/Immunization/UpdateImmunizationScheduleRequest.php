<?php

namespace App\VBC\Http\Requests\Immunization;

class UpdateImmunizationScheduleRequest extends StoreImmunizationScheduleRequest
{
    protected function ignoredScheduleId(): ?int
    {
        return (int) $this->route('scheduleId');
    }
}
