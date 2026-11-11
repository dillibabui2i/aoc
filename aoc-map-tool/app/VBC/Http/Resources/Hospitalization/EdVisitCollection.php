<?php

namespace App\VBC\Http\Resources\Hospitalization;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EdVisitCollection extends ResourceCollection
{
    public $collects = EdVisitCollectionItemResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ],
        ];
    }

    /** Same reason as HospitalizationCollection::paginationInformation(). */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [];
    }
}
