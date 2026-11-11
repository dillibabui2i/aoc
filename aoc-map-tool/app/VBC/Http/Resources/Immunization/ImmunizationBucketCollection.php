<?php

namespace App\VBC\Http\Resources\Immunization;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ImmunizationBucketCollection extends ResourceCollection
{
    public $collects = ImmunizationBucketCollectionItemResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
