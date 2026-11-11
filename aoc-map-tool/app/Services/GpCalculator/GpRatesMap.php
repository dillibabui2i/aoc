<?php

namespace App\Services\GpCalculator;

class GpRatesMap
{
    public const RATES_MAP = [
        'rn'                         => 'rn',
        'rn_specialized'             => 'rn',
        'lvn_lpn'                    => 'lvnlpn',
        'lvnlpn'                     => 'lvnlpn',
        'cna'                        => 'cna',
        'coverage_lvnlpn_mdc_prate'  => 'coverage_lvnlpn_mdcprate',
        'coverage_lvnlpn_mdcprate'   => 'coverage_lvnlpn_mdcprate',
    ];

    const RATES_MAP_ALIASES = [
        'lvn_lpn'  => 'lvnlpn',
        'lvn'      => 'lvnlpn',
        'lpn'      => 'lvnlpn',
        'rn_sp'    => 'rn',
        'rn_spec'  => 'rn',
        'rn_specialized' => 'rn',
        'cna_specialized' => 'cna',
    ];
}
