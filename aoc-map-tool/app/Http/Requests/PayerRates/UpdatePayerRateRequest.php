<?php

namespace App\Http\Requests\PayerRates;

use App\Enums\PayerRatesStatus;
use App\Helpers\LD;
use App\Http\Requests\CoreRequest;
use App\PayerRates;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdatePayerRateRequest extends CoreRequest
{
    public function rules(): array
    {
        $rules = [
            'id' => ['required', 'numeric'],
            'payer' => ['required', 'string'],
            'effectiveFrom' => ['required', 'date'],
            'effectiveThrough' => ['required', 'date'],
            'comment' => ['required', 'string'],
        ];

        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            $rules = array_merge($rules, [
                'services' => ['required', 'array'],
                'services.*.service_name' => ['required', 'string'],
                'services.*.rate' => ['required', 'numeric', 'min:0.01'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'hhagroupprimaryrate' => ['required', 'numeric'],
                'cnagroupprimaryrate' => ['required', 'numeric'],
                'lvnlpnrate' => ['required', 'numeric'],
                'lvnlpnspecrate' => ['required', 'numeric'],
                'rnrate' => ['required', 'numeric'],
                'rnspecrate' => ['required', 'numeric'],
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payer.required' => 'The Payer field is required.',
            'payer.unique' => 'Duplicate record found. Please enter unique combination value for Payer',
            'effectiveFrom.required' => 'The Effective From field is required.',
            'effectiveThrough.required' => 'The Effective Through field is required.',
            'hhagroupprimaryrate.required' => 'The HHA Group Primary Rate field is required.',
            'cnagroupprimaryrate.required' => 'The CNA Group Primary Rate field is required.',
            'lvnlpnrate.required' => 'The LVN/LPN Rate field is required.',
            'lvnlpnspecrate.required' => 'The LVN/LPN SP Rate field is required.',
            'rnrate.required' => 'The RN Rate field is required.',
            'rnspecrate.required' => 'The RN SP Rate field is required.',
            'comment.required' => 'The Comment field is required.',
            'services.required' => 'The services list is required.',
            'services.array' => 'The services must be a valid array.',
            'services.*.service_name.required' => 'The service name is required.',
            'services.*.rate.required' => 'The service rate is required.',
            'services.*.rate.numeric' => 'The service rate must be a valid number.',
            'services.*.rate.min' => 'The service rate must be at least :min.',
        ];
    }
}
