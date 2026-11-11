<?php

namespace App\UseCases\GpCalculator;

final readonly class SaveCalculatorResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly int $httpCode,
        public readonly string $message,
        public readonly array $payload = []
    ) {
    }

    public static function ok(string $message, array $payload = []): self
    {
        return new self(true, 200, $message, $payload);
    }

    public static function badRequest(string $message, array $payload = []): self
    {
        return new self(false, 400, $message, $payload);
    }

    public static function serverError(string $message = 'Server error', array $payload = []): self
    {
        return new self(false, 500, $message, $payload);
    }
}
