<?php

namespace App\Http\Resources\ConsentTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsentTemplateResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'language' => $this->language,
            'content' => $this->content
        ];
    }
}
