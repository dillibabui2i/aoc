<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\GridFilterDTO;
use App\Http\Requests\CoreRequest;

class GridRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'draw' => ['nullable', 'integer'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer'],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'array'],
            'order.0.column' => ['nullable', 'integer'],
            'order.0.dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function toDto(): GridFilterDTO
    {
        $data = $this->validated();
        $orderColumn = $data['order'][0]['column'] ?? null;

        return new GridFilterDTO(
            draw: (int) ($data['draw'] ?? 0),
            start: isset($data['start']) ? (int) $data['start'] : null,
            length: isset($data['length']) ? (int) $data['length'] : null,
            searchValue: $data['search']['value'] ?? null,
            orderColumn: $orderColumn !== null ? (int) $orderColumn : null,
            orderDirection: $data['order'][0]['dir'] ?? null,
        );
    }
}
