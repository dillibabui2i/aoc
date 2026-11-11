<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;
use App\Models\PayerPriorityConfig;
use App\Models\PaymentSource;
use App\Notes;
use Illuminate\Support\Facades\Auth;

class PriorityPayersConfigHelper
{
    public const SELECT_PAYER_PLACEHOLDER = 'Select a payer from SPGM…';

    public function getPaymentSourceNames(): Collection
    {
        return PaymentSource::pluck('name');
    }

    public function getPayersByState(): array
    {
        $payers = $this->getPaymentSourceNames();
        $states = config('constant.states');
        $result = [];

        $globalPayers = [];

        foreach ($payers as $payer) {
            $parts = explode('- ', $payer, 2);
            $abbr = trim($parts[0]);

            if (isset($states[$abbr])) {
                $result[$states[$abbr]][] = $payer;
            } else {
                $globalPayers[] = $payer;
            }
        }

        // Add global payers to each state
        if (!empty($globalPayers)) {
            foreach ($result as $stateName => &$statePayers) {
                $statePayers = array_merge($statePayers, $globalPayers);
            }
        }

        return $result;
    }

    public function getConfiguredPayerPriorities(): Collection
    {
        return PayerPriorityConfig::orderBy('state')->orderBy('rank')->get();
    }

    public function savePayerPriorityConfig(array $data): void
    {
        PayerPriorityConfig::truncate();

        $formattedNotes = [];

        foreach ($data as $state => $payers) {
            $payersList = array_filter($payers, fn($p) => !empty($p) && $p !== self::SELECT_PAYER_PLACEHOLDER);

            if (!empty($payersList)) {
                $formattedNotes[] = "$state: " . implode(', ', $payersList);
            }
        }

        Notes::create([
            'entity'     => 'NeedToHire',
            'type'       => 'payer_priority_change',
            'notes'      => empty($formattedNotes) ? 'Payer priority configuration cleared' : implode('; ', $formattedNotes),
            'created_by' => Auth::id(),
        ]);

        if (empty($data)) {
            return;
        }

        $states = config('constant.states');
        $stateToAbbr = array_flip($states);

        foreach ($data as $stateName => $payers) {
            if (!isset($stateToAbbr[$stateName])) {
                continue;
            }

            $stateAbbr = $stateToAbbr[$stateName];

            foreach ($payers as $index => $payer) {
                if (empty($payer) || $payer === self::SELECT_PAYER_PLACEHOLDER) {
                    continue;
                }

                PayerPriorityConfig::create([
                    'state'      => $stateName,
                    'state_abbr' => $stateAbbr,
                    'rank'       => (int) $index,
                    'payer'      => $payer,
                ]);
            }
        }
    }
}
