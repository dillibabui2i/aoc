<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Str;

class UserFixture extends Fixture
{
    protected function table(): string
    {
        return 'users';
    }

    protected function defaults(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'test-user-' . Str::random(12) . '@example.com',
            'password' => bcrypt('password'),
            'role' => '1',
            'status' => 1,
            'is_deleted' => 0,
            'tmpUpdated' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
