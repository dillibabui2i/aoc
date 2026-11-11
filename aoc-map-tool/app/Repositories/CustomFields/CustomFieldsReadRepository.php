<?php

namespace App\Repositories\CustomFields;

use App\CustomFields;
use Illuminate\Support\Collection;

class CustomFieldsReadRepository
{
    public function __construct(private CustomFields $customFields)
    {}

    public function getCustomFieldsByIds(array $ids) : Collection
    {
        return $this->customFields
            ->query()
            ->where('is_deleted', false)
            ->whereIn('id', $ids)
            ->get([
                'id',
                'db_field_name',
                'field_name',
            ]);
    }
}
