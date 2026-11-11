<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProviderEligibleForPreferenceUpdate implements Rule
{
    public function passes($attribute, $value): bool
    {
        return DB::table('mssql_providers')
            ->where('id', $value)
            ->where('is_deleted', 0)
            ->exists();
    }

    public function message(): string
    {
        return 'The selected provider is invalid.';
    }
}
