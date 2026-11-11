<?php

namespace App\Services\Utils;

/**
 * Class CacheKeysGeneratorService
 *
 * @package App\Services\Utils\CacheKeysGeneratorService
 */
class CacheKeysGeneratorService
{
    private const KEY_PATTERN = '-%s';

    /**
     * @param mixed ...$args
     * @return string
     */
    public function generateKey(mixed ...$args): string
    {
        return ltrim($this->buildKey($args), '-');
    }

    /**
     * @param mixed ...$args
     * @return string
     */
    public function generateHashedKey(mixed ...$args): string
    {
        return md5($this->buildKey($args));
    }

    /**
     * @param array $args
     * @return string
     */
    private function buildKey(array $args): string
    {
        return implode('', array_map(function ($arg) {
            if (is_array($arg)) {
                $arg = implode('-', $arg);
            }
            return sprintf(self::KEY_PATTERN, (string)$arg);
        }, $args));
    }
}
