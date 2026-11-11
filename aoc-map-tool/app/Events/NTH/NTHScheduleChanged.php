<?php

namespace App\Events\NTH;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NTHScheduleChanged
{
    use Dispatchable, SerializesModels;

    private string $action;
    private string $aocNTHAccountsID;

    public function __construct(string $action, string $aocNTHAccountsID)
    {
        $this->action = $action;
        $this->aocNTHAccountsID = $aocNTHAccountsID;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getAocNthAccountsID(): string
    {
        return $this->aocNTHAccountsID;
    }

    public function isEditMode(): bool
    {
        return Str::contains($this->getAction(), 'edit');
    }

    public function isDeleteMode(): bool
    {
        return Str::contains($this->getAction(), 'remove') || Str::contains($this->getAction(), 'del');
    }
}
