<?php

namespace App\Http\Requests\WhiteBoard;

use App\Http\Requests\CoreRequest;

class ManageWhiteBoardRequest extends CoreRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'user_name' => ['required'],
            'row_id' => ['required'],
            'territory' => ['required'],
        ];
    }
}
