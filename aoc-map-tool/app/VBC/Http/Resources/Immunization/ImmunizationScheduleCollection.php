<?php

namespace App\VBC\Http\Resources\Immunization;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ImmunizationScheduleCollection extends ResourceCollection
{
    public $collects = ImmunizationScheduleCollectionItemResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
