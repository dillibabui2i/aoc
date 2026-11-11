<?php

namespace App\Http\Resources\NeedToHire;

use App\Helpers\H;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleWithHoursResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'value' => $this->id,
            'label' =>
                $this->StartDayOfWeek . ' - ' .
                H::getHelper('ConstantHelper')->getSmallTimeFormat($this->StartHour . ':' . $this->StartMinute) . ' - ' .
                H::getHelper('ConstantHelper')->getSmallTimeFormat($this->EndHour . ':' . $this->EndMinute),
            'ShiftHours' => $this->ShiftHours,
        ];
    }
}
