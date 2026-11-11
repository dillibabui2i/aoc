<?php

namespace Tests\Support\Fixtures;

class NotificationsFixture extends Fixture
{
    protected function table(): string
    {
        return 'notifications';
    }

    protected function defaults(): array
    {
        return [
            'type' => 'statusChangeRequest',
            'title' => 'Notification',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
