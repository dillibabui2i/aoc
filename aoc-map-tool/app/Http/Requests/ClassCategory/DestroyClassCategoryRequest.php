<?php

namespace App\Http\Requests\ClassCategory;

use App\Http\Requests\CoreRequest;

class DestroyClassCategoryRequest extends CoreRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:class_categories,id'],
        ];
    }
}
