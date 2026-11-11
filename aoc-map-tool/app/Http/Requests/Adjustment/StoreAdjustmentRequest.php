<?php

namespace App\Http\Requests\Adjustment;

use App\Http\Requests\CoreRequest;

class StoreAdjustmentRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'id' => ['integer', 'nullable'],
            'category' => ['required', 'string', 'max:255'],
            'requires_ops_review' => ['required', 'boolean'],
            'approval_level_1' => ['nullable', 'regex:/^(user:\d+|role:\d+)?$/'],
            'approval_level_2' => ['nullable', 'regex:/^(user:\d+|role:\d+)?$/'],
            'approval_level_3' => ['nullable', 'regex:/^(user:\d+|role:\d+)?$/'],
        ];
    }
}
