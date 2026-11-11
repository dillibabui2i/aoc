<?php

namespace App\Http\Requests\NRR;

use App\DTO\NationalRecruitingReport\TableParams;
use Illuminate\Foundation\Http\FormRequest;

class NationalRecruitingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'tab' => ['string', 'sometimes'],
            'isajax' => ['string', 'sometimes'],
            'period' => ['string', 'sometimes'],
            'source' => ['string', 'sometimes'],
            'view' => ['string', 'sometimes'],
            'search' => ['array', 'sometimes'],
            'date' => ['array', 'sometimes'],
        ];
    }

    /**
     * @return TableParams
     */
    public function toDTO(): TableParams
    {
        return new TableParams(
            tab: $this->get('tab'),
            isajax: $this->get('isajax'),
            period: $this->get('period'),
            source: $this->get('source'),
            view: $this->get('view'),
            search: $this->get('search', []),
            date: $this->get('date', []),
        );
    }
}
