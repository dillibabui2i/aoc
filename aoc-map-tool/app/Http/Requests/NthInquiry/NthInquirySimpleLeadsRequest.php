<?php

namespace App\Http\Requests\NthInquiry;

use App\DTO\NthInquiryFilterDTO;
use App\Http\Requests\CoreRequest;

class NthInquirySimpleLeadsRequest extends CoreRequest
{

    public function rules(): array
    {
        return [
          'ClinicianID' => ['required', 'string'],
          'exclude_lead_id' => ['nullable', 'integer'],
          'order.0.column' => ['nullable', 'integer'],
          'order.0.dir' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function toDTO(): NthInquiryFilterDTO
    {
        return new NthInquiryFilterDTO(
          clinicianId: (string)$this->input('ClinicianID'),
          excludeLeadId: $this->input('exclude_lead_id') ? (int)$this->input('exclude_lead_id') : null,
          orderColumn: $this->getOrderColumn(),
          orderDirection: $this->getOrderDirection()
        );
    }

    private function getOrderColumn(): string
    {
        $columns = [
          0 => 'ticket_id',
          1 => 'aoc_patient_id',
          2 => 'patient_name',
          3 => 'city_state',
          4 => 'Status',
          5 => 'Recruiter',
        ];

        $index = $this->input('order.0.column');
        return $columns[$index] ?? 'created_at';
    }

    private function getOrderDirection(): string
    {
        $dir = strtolower($this->input('order.0.dir', 'desc'));
        return in_array($dir, ['asc', 'desc']) ? $dir : 'desc';
    }

}
