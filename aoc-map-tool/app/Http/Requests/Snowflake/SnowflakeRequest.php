<?php

namespace App\Http\Requests\Snowflake;

use App\Http\Requests\CoreRequest;

class SnowflakeRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'query' => [
                'required',
                'string',
            ]
        ];
    }
}
