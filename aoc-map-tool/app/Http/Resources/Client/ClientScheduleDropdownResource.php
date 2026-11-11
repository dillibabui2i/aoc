<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientScheduleDropdownResource extends JsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'payer'             => $this->Payer,

            'label' => $this->Patient_LastName . ', '
                . $this->Patient_FirstName . ' | '
                . $this->PatientAccount . ' | '
                . $this->PatientStatus . ' | '
                . $this->BranchName . ' | '
                . $this->AdmitNo,

            'value'             => $this->Patient_FirstName . ' ' . $this->Patient_LastName,
            'branch_name'       => $this->FacilityName,
            'is_specialized'    => $this->is_specialized ?? 0,

            'Patient_Address1'  => $this->Patient_Address1,
            'Patient_Address2'  => $this->Patient_Address2,
            'Patient_Zip'       => $this->Patient_Zip,
            'Patient_FirstName' => $this->Patient_FirstName,
            'Patient_LastName'  => $this->Patient_LastName,
            'PatientAccount'    => $this->PatientAccount,
            'Patient_City'      => $this->Patient_City,
            'Patient_State'     => $this->Patient_State,

            'respiteTerminalPlayer' => $this->RespitePayer,
            'aoc_facility_id'       => $this->aoc_facility_id,
            'LOBID'                 => $this->LOBID,
            'CF_Specialized'        => $this->CF_Specialized,
            'ic_id'                 => $this->ic_id,
            'PatientStatus'         => $this->PatientStatus,

            'SchoolName'        => $this->CF_SchoolName,
            'SchoolDistrict'    => $this->CF_SchoolDistrict,
        ];
    }
}
