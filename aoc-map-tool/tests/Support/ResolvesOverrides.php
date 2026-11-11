<?php

namespace Tests\Support;

use Closure;

trait ResolvesOverrides
{
    /**
     * Merge $defaults with $overrides, then lazily evaluate any default that is
     * still a Closure after the merge (i.e. wasn't replaced by an override) —
     * lets a default require a side effect (e.g. creating a related fixture)
     * without paying for it when the caller already supplied a value.
     */
    protected function resolveOverrides(array $defaults, array $overrides = []): array
    {
        $merged = array_merge($defaults, $overrides);

        foreach ($merged as $key => $value) {
            if ($value instanceof Closure) {
                $merged[$key] = $value();
            }
        }

        return $merged;
    }
}
