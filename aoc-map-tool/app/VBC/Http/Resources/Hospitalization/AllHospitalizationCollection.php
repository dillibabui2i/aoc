<?php

namespace App\VBC\Http\Resources\Hospitalization;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AllHospitalizationCollection extends ResourceCollection
{
    public $collects = AllHospitalizationCollectionItemResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
