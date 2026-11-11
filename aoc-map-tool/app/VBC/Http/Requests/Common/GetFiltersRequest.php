<?php

namespace App\VBC\Http\Requests\Common;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Common\GetFiltersDTO;
use App\VBC\Enum\Common\FilterBucketEnum;
use Illuminate\Validation\Rule;

class GetFiltersRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'bucket' => ['sometimes', 'string', Rule::enum(FilterBucketEnum::class)],
        ];
    }

    public function toDto(): GetFiltersDTO
    {
        return new GetFiltersDTO(
            bucket: $this->filled('bucket') ? FilterBucketEnum::from($this->input('bucket')) : null,
        );
    }
}
