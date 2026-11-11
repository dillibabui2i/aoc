<?php

namespace App\Kantime\Common;

class KantimeSyncConfig
{
    public function __construct(public readonly bool $debugSync = false)
    {
    }
}
