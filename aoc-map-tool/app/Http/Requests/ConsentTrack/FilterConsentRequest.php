<?php

namespace App\Http\Requests\ConsentTrack;

use App\DTO\ConsentTrack\ConsentTableParams;
use App\Http\Requests\CoreRequest;

class FilterConsentRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'string'],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'array'],
            'start' => ['nullable', 'integer', 'min:0'],
            'order.*.column' => ['required_with:order', 'integer', 'min:0'],
            'order.*.dir' => ['required_with:order', 'in:asc,desc'],
            'length' => ['nullable', 'integer', 'min:1', 'max:1000'],

            'columns' => ['nullable', 'array'],
            'columns.*.data' => ['nullable', 'string', 'max:100'],
            'columns.*.name' => ['nullable', 'string', 'max:100'],
            'columns.*.searchable' => ['nullable', 'in:true,false,1,0'],
            'columns.*.orderable' => ['nullable', 'in:true,false,1,0'],
            'columns.*.search' => ['nullable', 'array'],
            'columns.*.search.value' => ['nullable', 'string', 'max:255'],
            'columns.*.search.regex' => ['nullable', 'in:true,false,1,0'],

            'consent_version' => ['nullable', 'array'],
            'consent_type' => ['nullable', 'array'],
            'consent_status' => ['nullable', 'array'],
        ];
    }

    /**
     * @return ConsentTableParams
     */
    public function toDTO(): ConsentTableParams
    {
        $data = $this->validated();

        if (! isset($data['order']) || ! is_array($data['order'])) {
            $data['order'] = [];
        }

        $data['columns'] = (array) ($data['columns'] ?? []);

        return new ConsentTableParams(
            draw: $data['draw'] ?? null,
            search: isset($data['search']) && is_array($data['search']) ? $data['search'] : ['value' => null, 'regex' => 'false'],
            order: is_array($data['order']) ? $data['order'] : [],
            start: isset($data['start']) ? (int) $data['start'] : 0,
            length: isset($data['length']) ? (int) $data['length'] : 10,
            columns: ($data['columns'] ?? []),
            consent_version: (array)($data['consent_version'] ?? []),
            consent_type:    (array)($data['consent_type'] ?? []),
            consent_status:  (array)($data['consent_status'] ?? []),
        );
    }
}
