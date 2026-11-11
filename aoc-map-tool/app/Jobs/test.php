<?php

namespace App\Jobs;

class test extends Job
{
    public function handle(): void
    {
        file_put_contents(public_path('queue.txt'), time() . "\n", FILE_APPEND);
    }
}
