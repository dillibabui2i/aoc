<?php

namespace App\UseCases\GpCalculator;

final readonly class SaveCalculatorCommand
{
    public function __construct(
        public ?string         $calculatorJson,
        public int|string|null $userId = null,   // optional
        public int|string|null $calculatorId = null, // optional (update vs create)
    ) {
    }
}
