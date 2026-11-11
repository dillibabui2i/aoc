<?php

namespace App\VBC\Http\Resources\Followup;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FollowupCollection extends ResourceCollection
{
    public $collects = FollowupCollectionItemResource::class;

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

    /**
     * Prevent Laravel's default pagination `links`/`meta` from being merged on top of the custom
     * `meta` block above — otherwise ResourceCollection::preparePaginatedResponse() array_merge_recursive
     * s the two, turning every scalar meta value (current_page, total, ...) into a 2-element array.
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [];
    }
}
