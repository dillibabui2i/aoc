<?php

namespace App\VBC\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Accepts any punctuation/spacing as long as there are exactly 10 digits (US/NAMP number) — independent
 * of `@angels-of-care/ui`'s `UiInput type="phone"` mask format, so a future mask change on the frontend
 * can't silently desync from what the backend accepts.
 */
readonly class UsPhoneNumber implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^\d{10}$/', self::digitsOnly($value)) === 1;
    }

    public function message(): string
    {
        return 'Enter a valid 10-digit phone number.';
    }

    /**
     * Strips all punctuation/spacing, keeping only the 10 significant digits — what's actually
     * stored. Tolerates an optional leading US country-code "1" (e.g. `+1 (555) 123-4567`, 11 digits
     * once stripped) so this stays compatible whether or not `UiInput type="phone"`'s mask includes the
     * `+1` prefix.
     */
    public static function digitsOnly(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return strlen($digits) === 11 && $digits[0] === '1' ? substr($digits, 1) : $digits;
    }
}
