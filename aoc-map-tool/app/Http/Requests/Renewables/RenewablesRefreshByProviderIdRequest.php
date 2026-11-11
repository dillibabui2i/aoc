<?php

namespace App\Http\Requests\Renewables;

use App\Http\Requests\CoreRequest;

class RenewablesRefreshByProviderIdRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
            ]
        ];
    }
}
