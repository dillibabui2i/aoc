<?php

namespace App\VBC\Http\Resources\Immunization;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AllImmunizationCollection extends ResourceCollection
{
    public $collects = AllImmunizationCollectionItemResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
