<?php

namespace App\Services\Snowflake;

class Anonymize
{
    private const ANONYMIZE_EXCEPTIONS = ['id', 'created_at'];

    function anonymize(mixed $value, $field = null): mixed
    {
        if (in_array($field, self::ANONYMIZE_EXCEPTIONS)) {
            return $value;
        }

        if (is_null($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return preg_replace('/(.+)@(.+)/', '***@$2', $value);
        }

        if (is_numeric($value)) {
            return 0;
        }

        return str_repeat('*', strlen($value));
    }
}
