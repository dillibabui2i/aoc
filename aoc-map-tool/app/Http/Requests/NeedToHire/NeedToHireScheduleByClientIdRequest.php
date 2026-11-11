<?php

namespace App\Http\Requests\NeedToHire;

use App\DTO\NeedToHire\NeedToHireScheduleByClientIdDTO;
use App\Http\Requests\CoreRequest;

class NeedToHireScheduleByClientIdRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'client_id' => ['string', 'required'],
        ];
    }

    /**
     * @return NeedToHireScheduleByClientIdDTO
     */
    public function toDTO(): NeedToHireScheduleByClientIdDTO
    {
        return new NeedToHireScheduleByClientIdDTO(
            clientId: $this->get('client_id'),
        );
    }
}
