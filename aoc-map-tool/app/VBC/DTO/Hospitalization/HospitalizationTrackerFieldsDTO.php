<?php

namespace App\VBC\DTO\Hospitalization;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;

/**
 * Editable fields of the "Hospitalization Record" header form as submitted. Shared by both write
 * endpoints: creating a tracker carries whatever the user filled in up front, editing carries exactly
 * one entry. The write repository therefore has a single path for storing a field, whichever endpoint
 * it arrived through.
 *
 * Only keys actually submitted are present — an omitted field keeps its current/default value rather
 * than being written as null or false.
 */
final readonly class HospitalizationTrackerFieldsDTO
{
    /**
     * @param array<string, mixed> $values keyed by HospitalizationTrackerFieldEnum values — PHP cannot
     *                                     key an array by an enum, so the backing values are used
     */
    public function __construct(public array $values)
    {
    }

    /** @param array<string, mixed> $values validated input, keyed by field name */
    public static function cast(array $values): self
    {
        $cast = [];

        foreach ($values as $field => $value) {
            $cast[$field] = HospitalizationTrackerFieldEnum::from($field)->cast($value);
        }

        return new self($cast);
    }
}
