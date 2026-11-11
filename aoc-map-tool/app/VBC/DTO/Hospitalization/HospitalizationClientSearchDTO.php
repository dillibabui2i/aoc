<?php

namespace App\VBC\DTO\Hospitalization;

/** Client autocomplete on the "Client Name" field. The bounds live here so the rule and the fallback agree. */
final readonly class HospitalizationClientSearchDTO
{
    /**
     * The search is a leading-wildcard LIKE over every current admission, so a one- or two-letter term
     * scans the table to return a list nobody can use. The autocomplete holds back until the same
     * threshold — CLIENT_SEARCH_MIN_LENGTH in resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js.
     */
    public const MIN_SEARCH_LENGTH = 3;

    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 50;

    public function __construct(
        public string $search,
        public int $limit = self::DEFAULT_LIMIT,
    ) {
    }
}
