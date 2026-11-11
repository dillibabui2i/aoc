<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
