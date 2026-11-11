<?php

namespace App\UseCases\GpCalculator;

use App\Repositories\GpCalculator\GpCalculatorRepository;

final class SaveCalculatorUseCase
{
    // Inject repo/mapper later when you’re ready:
    // public function __construct(private CalculatorRepository $repo) {}
    public function __construct(private readonly GpCalculatorRepository $gpCalculatorRepository)
    {
    }

    public function execute(SaveCalculatorCommand $cmd): SaveCalculatorResult
    {
        $calculatorJson = $cmd->calculatorJson ?? null;

        if (!$calculatorJson) {
            return SaveCalculatorResult::badRequest('No source data for calculation');
        }

        $data = json_decode($calculatorJson, true);

        if (!is_array($data)) {
            return SaveCalculatorResult::badRequest('Calculation source data invalid');
        }

        // Required top-level pieces
        $clients = $data['clients'] ?? null;
        $nurses = $data['nurses'] ?? null;
        $links = $data['links'] ?? null;
        $type = $data['calculationType'] ?? null;

        if (!is_array($clients) || !is_array($nurses) || !is_array($links) || !$type) {
            return SaveCalculatorResult::badRequest('Calculation source data invalid');
        }

        // Optional: validate allowed calculationType values
        $allowedTypes = [
            'singleClientSingleNurse',
            'singleClientMultipleNurses',
            'multipleClientsSingleNurse',
        ];
        if (!in_array($type, $allowedTypes, true)) {
            return SaveCalculatorResult::badRequest('Calculation source data invalid: unsupported calculationType');
        }

        $id = $this->gpCalculatorRepository->save($cmd->calculatorJson, $cmd->userId, ($clients[0] ? $clients[0]['patientId'] : 0), $cmd->calculatorId);

        return SaveCalculatorResult::ok('Calculator saved', [
            'calculationType' => $type,
            'clientsCount' => count($clients),
            'nursesCount' => count($nurses),
            'linksCount' => count($links),
            'gpId' => $id,
        ]);
    }

    private function indexByClientKeyAndSlot(array $items): array
    {
        $idx = [];
        foreach ($items as $i => $row) {
            if (!is_array($row)) {
                continue;
            }

            // For clients/nurses you likely have: clientKey + slotIndex
            $key = $row['clientKey'] ?? ($row['nurseKey'] ?? null);
            $slot = $row['slotIndex'] ?? null;

            if ($key === null || $slot === null) {
                continue;
            }
            $idx[$key . '#' . $slot] = true;
        }

        return $idx;
    }
}
