<?php

namespace App\Http\Requests\NthInquiry;

use App\DTO\NthInquiryBulkResolveDTO;
use App\Http\Requests\CoreRequest;

class NthInquiryBulkResolveRequest extends CoreRequest
{

    public function rules(): array
    {
        return [
          'ids' => ['required', 'array', 'min:1'],
          'ids.*' => ['integer'],
          'advancing_lead_id' => ['required', 'integer'],
        ];
    }

    public function toDTO(): NthInquiryBulkResolveDTO
    {
        return new NthInquiryBulkResolveDTO(
          ids: $this->input('ids'),
          advancingLeadId: (int)$this->input('advancing_lead_id'),
          recruiterId: auth()->id(),
          recruiterName: auth()->user()->name
        );
    }

}
