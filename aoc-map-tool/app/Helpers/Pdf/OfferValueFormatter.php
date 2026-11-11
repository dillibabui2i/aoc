<?php

namespace App\Helpers\Pdf;

use App\Models\OfferValue;

class OfferValueFormatter
{
    public static function format(OfferValue $value): string
    {
        $type = $value->field?->value_type ?? 'string';

        return match ($type) {
            'decimal' => $value->value_decimal !== null ? '$' . number_format((float)$value->value_decimal, 2) : '—',
            'int' => $value->value_int !== null ? (string)$value->value_int : '—',
            'date' => $value->value_date ? $value->value_date->format('m/d/Y') : '—',
            'bool' => $value->value_bool ? 'Yes' : 'No',
            default => $value->value_string ?: '—',
        };
    }
}
