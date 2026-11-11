<?php

namespace App\Events\Offer;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferApprovedEvent
{
    use Dispatchable, SerializesModels;

    public int $candidateID;
    public ?int $initiatedBy;

    public function __construct(int $candidateID, ?int $initiatedBy = null)
    {
        $this->candidateID = $candidateID;
        $this->initiatedBy = $initiatedBy;
    }

    public function getCandidateID(): int
    {
        return $this->candidateID;
    }

    public function getInitiatedBy(): ?int
    {
        return $this->initiatedBy;
    }
}
