<?php

namespace App\Http\Requests;

use App\Entity\FirstAdvantageCheckEntity;
use Illuminate\Foundation\Http\FormRequest;

class FirstAdvantageCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['array', 'sometimes'],
        ];
    }

    public function getEntity(): FirstAdvantageCheckEntity
    {
        return new FirstAdvantageCheckEntity($this->get('order'));
    }
}
