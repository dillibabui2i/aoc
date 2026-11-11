<?php

namespace App\Kantime\Jobs;

interface SyncJobInterface
{
    public function getType() : string;
}
