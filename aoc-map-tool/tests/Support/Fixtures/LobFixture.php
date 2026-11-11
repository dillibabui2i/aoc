<?php

namespace Tests\Support\Fixtures;

class LobFixture extends Fixture
{
    protected function table(): string
    {
        return 'lob';
    }

    protected function defaults(): array
    {
        return [
            'LOBID' => random_int(10000, 99999),
            'LOBName' => 'RN',
        ];
    }
}
