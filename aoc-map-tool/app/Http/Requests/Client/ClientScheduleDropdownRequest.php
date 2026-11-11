<?php

namespace App\Http\Requests\Client;

use App\DTO\Client\ScheduleDropdownDTO;
use App\Http\Requests\CoreRequest;

class ClientScheduleDropdownRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'term' => ['string', 'required'],
        ];
    }

    /**
     * @return ScheduleDropdownDTO
     */
    public function toDTO(): ScheduleDropdownDTO
    {
        return new ScheduleDropdownDTO(
            term: $this->get('term'),
        );
    }
}
