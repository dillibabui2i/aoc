<?php

namespace App\Services\GpCalculator;

use App\Repositories\GpCalculator\CandidatesRepository;
use App\Repositories\GpCalculator\ProvidersRepository;

readonly class GpClinicianService
{
    public function __construct(
        private ProvidersRepository $providersRepository,
        private CandidatesRepository $candidatesRepository
    ) {
    }

    public function getClinicianList(string $candidateType, string $term) : array
    {
        $clientList = $candidateType == 'current'
            ? $this->providersRepository->getProvidersList($term)
            : $this->candidatesRepository->getCandidatesList($term);

        return $this->formatList($clientList);
    }

    private function formatList(array $clientList) : array
    {
        $clientListData = [];
        foreach ($clientList as $clientSubData) {
            $clientListData[] = [
                'label' => sprintf(
                    '%s, %s | %s %s %s',
                    $clientSubData['LastName'],
                    $clientSubData['FirstName'],
                    $clientSubData['ClinicianID'],
                    $clientSubData['EmploymentStatus'] ? '| ' . $clientSubData['EmploymentStatus'] : '',
                    $clientSubData['BranchName'] ? '| ' . $clientSubData['BranchName'] : ''
                ),
                'value' => sprintf('%s %s', $clientSubData['FirstName'], $clientSubData['LastName']),
                'email' => $clientSubData['External_Email'],
                'phone' => $clientSubData['Mobile'],
                'title' => $clientSubData['DisciplineName'],
                'id' => $clientSubData['id'],
                'isIneligibleRehire' => $clientSubData['CF_NotEligibleForRehire'] ?? '',
                'ClinicianID' => $clientSubData['ClinicianID'] ?? '',
            ];
        }

        return $clientListData;
    }
}
