<?php

namespace App\VBC\Services\Diagnosis;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxies the NLM Clinical Tables ICD-10-CM search API (PD-3498) — there is no official API that returns
 * the full ICD-10 catalog, only search. A thin, dependency-free wrapper: reusable outside VBC later if
 * another module needs ICD-10 lookups, without any code change now.
 *
 * @see https://clinicaltables.nlm.nih.gov/api/icd10cm/v3/search?terms=A15 (example)
 */
class Icd10SearchService
{
    /**
     * @return array<int, array{code: string, description: string}>
     */
    public function search(string $terms): array
    {
        try {
            $response = Http::get(config('services.icd10.search_url'), ['terms' => $terms])->throw();
        } catch (RequestException $e) {
            Log::error('ICD-10 search failed', ['terms' => $terms, 'exception' => $e->getMessage()]);

            return [];
        }

        // Response shape: [total, codes, null, [[code, description], ...], ...] — only index 3 matters.
        $matches = $response->json(3) ?? [];

        return array_map(
            fn (array $match) => ['code' => $match[0], 'description' => $match[1]],
            $matches
        );
    }
}
