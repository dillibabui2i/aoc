<?php

namespace App\Http\Requests;

use App\DTO\SendLoginOtpDTO;
use App\DTO\VerifyOtpDTO;
use App\Models\MobileOtpLog;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'mobile_number' => [
                'nullable',
                'regex:/^\+?[0-9]{10,15}$/'
            ],
            'email' => [
                'nullable',
                'email:rfc,dns'
            ],
            'email_otp' => ['nullable','string'],
            'mobile_otp' => ['nullable','string'],
        ];
    }

    /**
     * @return VerifyOtpDTO
     */
    public function toDTO(): VerifyOtpDTO
    {
        return new VerifyOtpDTO(
            mobileNumber: $this->filled('mobile_number')
                ? MobileOtpLog::getFormatPhone($this->all())
                : '',
            email: $this->input('email'),
            otp: $this->input('email_otp') ?? $this->input('mobile_otp')
        );
    }
}
