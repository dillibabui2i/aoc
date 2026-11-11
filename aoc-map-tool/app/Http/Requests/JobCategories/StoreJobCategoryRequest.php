<?php

namespace App\Http\Requests\JobCategories;

use App\Http\Requests\CoreRequest;

class StoreJobCategoryRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'State' => ['required', 'string', 'max:255'],
            'Type' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'State.required' => 'State is required.',
            'Type.required' => 'Type is required.',
            'category.required' => 'Category is required.',
        ];
    }

}
