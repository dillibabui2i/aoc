<?php

declare(strict_types=1);

namespace App\Services\GpCalculator;

use App\Helpers\H;
use App\MssqlFacilities;
use App\Models\GrossMarginConfiguration;
use App\Models\PaymentSource;

final class GpCalculatorEscalationGuideService
{
    private const EMPTY_MESSAGE = 'No gross margin targets have been configured for this payer. Please contact your administrator.';

    public function __construct(
        private readonly GpCalculatorEscalationGuideMapper $mapper
    ) {
    }

    /**
     * @param array<string,mixed>|null $client
     * @return array<string,mixed>
     */
    public function buildForClient(?array $client): array
    {
        $payerName = trim((string) ($client['clientPayer'] ?? ''));
        $userId = auth()->id();
        $roleIds = $userId
            ? H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userId)
            : [];
        $visibleColumnKeys = $this->mapper->visibleColumnKeys($roleIds);

        $guide = [
            'title' => 'Gross Margin Escalation Guide',
            'location' => '',
            'payer' => $payerName,
            'visible_columns' => $this->mapper->visibleColumns($visibleColumnKeys),
            'rows' => [],
            'is_empty' => true,
            'message' => self::EMPTY_MESSAGE,
        ];

        if ($payerName === '') {
            return $guide;
        }

        $branchId = (int) ($client['branchId'] ?? 0);
        if ($branchId <= 0) {
            return $guide;
        }

        $guide['location'] = (string) MssqlFacilities::query()->whereKey($branchId)->value('FacilityName');

        $payerId = PaymentSource::query()
            ->where('name', $payerName)
            ->value('id');

        if (!$payerId) {
            return $guide;
        }

        $configs = GrossMarginConfiguration::query()
            ->where('payer_id', (int) $payerId)
            ->where(function ($query) use ($branchId) {
                $query->whereJsonContains('branch_ids', $branchId)
                    ->orWhereJsonContains('branch_ids', (string) $branchId);
            })
            ->orderBy('lob_enum')
            ->get();

        if ($configs->isEmpty()) {
            return $guide;
        }

        $rows = [];
        foreach ($configs as $config) {
            $row = [
                'lob' => $config->lob_enum?->label() ?? '',
            ];

            foreach ($visibleColumnKeys as $columnKey) {
                $value = $config->{$columnKey};
                $row[$columnKey] = $value === null ? '' : number_format((float) $value, 1, '.', '');
            }

            $rows[] = $row;
        }

        $guide['rows'] = $rows;
        $guide['is_empty'] = count($rows) === 0;

        return $guide;
    }
}
