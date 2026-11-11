<?php

namespace App\Http\Requests\Map;

use App\DTO\Map\MapTableParams;
use App\Http\Requests\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class MapTableRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'string'],
            'period' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'view' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'array'],
            'date.start' => ['nullable', 'date'],
            'date.end' => ['nullable', 'date', 'after_or_equal:date.start'],
            'order' => ['nullable', 'array'],
            'order.*.column' => ['required_with:order', 'integer', 'min:0'],
            'order.*.dir' => ['required_with:order', 'in:asc,desc'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:1000'],
            '_token' => ['nullable', 'string'],
            'isCheckBox' => ['nullable', 'boolean'],

            'columns' => ['nullable', 'array'],
            'columns.*.data' => ['nullable', 'string', 'max:100'],
            'columns.*.name' => ['nullable', 'string', 'max:100'],
            'columns.*.searchable' => ['nullable', 'in:true,false,1,0'],
            'columns.*.orderable' => ['nullable', 'in:true,false,1,0'],
            'columns.*.search' => ['nullable', 'array'],
            'columns.*.search.value' => ['nullable', 'string', 'max:255'],
            'columns.*.search.regex' => ['nullable', 'in:true,false,1,0'],
        ];
    }

    /**
     * @return MapTableParams
     */
    public function toDTO(): MapTableParams
    {
        $data = $this->validated();

        if (! isset($data['search']) || ! is_array($data['search'])) {
            $data['search'] = ['value' => null, 'regex' => 'false'];
        }

        if (! isset($data['date']) || ! is_array($data['date'])) {
            $data['date'] = [];
        }

        if (! isset($data['order']) || ! is_array($data['order'])) {
            $data['order'] = [];
        }

        $data['start'] = $data['start'] ?? 0;
        $data['length'] = $data['length'] ?? 10;
        $data['columns'] = (array) ($data['columns'] ?? []);
        $data['isajax'] = $data['isajax'] ?? false;
        $data['isCheckBox'] = $data['isCheckBox'] ?? false;

        return new MapTableParams(
            draw: $data['draw'] ?? null,
            period: $data['period'] ?? null,
            source: $data['source'] ?? null,
            view: $data['view'] ?? null,
            search: isset($data['search']) && is_array($data['search']) ? $data['search'] : ['value' => null, 'regex' => 'false'],
            date: isset($data['date']) && is_array($data['date']) ? $data['date'] : [],
            order: isset($data['order']) && is_array($data['order']) ? $data['order'] : [],
            start: isset($data['start']) ? (int) $data['start'] : 0,
            length: isset($data['length']) ? (int) $data['length'] : 10,
            _token: $data['_token'] ?? null,
            isCheckBox: isset($data['isCheckBox']) ? (bool) $data['isCheckBox'] : false,
            columns: (array) ($data['columns'] ?? []),
        );
    }
}
