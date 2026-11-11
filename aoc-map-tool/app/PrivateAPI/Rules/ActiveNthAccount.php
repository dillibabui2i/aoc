<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ActiveNthAccount implements Rule
{
    private string $message = 'The selected NTH is invalid.';

    public function passes($attribute, $value): bool
    {
        $nth = DB::table('NeedToHire_Accounts')
            ->where('id', $value)
            ->first(['IsActive']);

        if (!$nth) {
            $this->message = 'The selected NTH is invalid.';

            return false;
        }

        if ((int)$nth->IsActive !== 1) {
            $this->message = 'The NTH is not active.';

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
