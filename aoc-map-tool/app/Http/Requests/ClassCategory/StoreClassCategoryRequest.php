<?php

namespace App\Http\Requests\ClassCategory;

use App\Http\Requests\CoreRequest;
use App\Models\ClassCategory;
use Illuminate\Validation\Rule;

class StoreClassCategoryRequest extends CoreRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(ClassCategory::class, 'name')],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'The class type already exists.',
            'color.regex' => 'The color must be a valid hex color.',
        ];
    }
}
