<?php

namespace App\DTO\TrachVentClass;

final readonly class EnrollmentResultDTO
{
    public const STATUS_CLASS_NOT_FOUND = 'class_not_found';
    public const STATUS_CLASS_FULL = 'class_full';
    public const STATUS_ALREADY_ENROLLED = 'already_enrolled';
    public const STATUS_ENROLLED = 'enrolled';

    public function __construct(
        public string $status,
        public ?int $enrolledCount = null,
        public ?int $capacity = null,
    ) {
    }

    public static function classNotFound(): self
    {
        return new self(self::STATUS_CLASS_NOT_FOUND);
    }

    public static function classFull(): self
    {
        return new self(self::STATUS_CLASS_FULL);
    }

    public static function alreadyEnrolled(): self
    {
        return new self(self::STATUS_ALREADY_ENROLLED);
    }

    public static function enrolled(int $enrolledCount, int $capacity): self
    {
        return new self(self::STATUS_ENROLLED, $enrolledCount, $capacity);
    }
}
