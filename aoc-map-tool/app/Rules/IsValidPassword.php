<?php

namespace App\Rules;

use App\Helpers\H;
use Illuminate\Contracts\Validation\Rule;

class IsValidPassword implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public $listPasses = true;
    public $sequencePasses = true;

    public function __construct()
    {
        // Do nothing.
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->sequencePasses = $this->isSequence($value);
        $this->listPasses = $this->isInList($value);

        return $this->sequencePasses && $this->listPasses;
    }

    public function isInList($value)
    {
        $restrictedPasswords = H::getRestrictedPasswordList();
        if (in_array($value, $restrictedPasswords)) {
            return false;
        } else {
            return true;
        }
    }

    public function isSequence($value)
    {
        $invalid = true;
        preg_match_all('/([a-zA-Z0-9])\1{2,}/', $value, $matches);
        if (!empty($matches[1])) {
            $invalid = false;
        } else {
            $arr = str_split($value);
            $last = count($arr) - 1;
            foreach ($arr as $key => $t) {
                if (!is_numeric($t)) {
                    continue;
                }
                $prev = $key > 0 && $arr[$key - 1] == ($t - 1);
                $next = $key < $last && $arr[$key + 1] == ($t + 1);

                if ($prev && $next) {
                    $invalid = false;
                    break;
                }
            }
        }

        return $invalid;
    }

    public function message()
    {
        switch (true) {
            case !$this->sequencePasses:
                return 'The password may not be sequencial or contain repeated characters';
            case !$this->listPasses:
                return 'This password has been banned because it is not secure.  Please choose a new one.';
            default:
                break;
        }

        return '';
    }
}
