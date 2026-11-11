<?php

namespace App\Http\Requests;

use App\DTO\SendLoginOtpDTO;
use App\Models\MobileOtpLog;

class SendLoginOtpRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'mobile_number' => ['nullable','string'],
            'email' => ['nullable','email'],
        ];
    }

    /**
     * @return SendLoginOtpDTO
     */
    public function toDTO(): SendLoginOtpDTO
    {
        return new SendLoginOtpDTO(
            mobileNumber:
            $this->filled('mobile_number')
                ? MobileOtpLog::getFormatPhone($this->all())
                : '',

            email: $this->filled('email')
                ? $this->input('email')
                : '',
        );
    }
}
