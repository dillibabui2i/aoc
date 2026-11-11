<?php

namespace App\Listeners\Offer;

use App\Events\Offer\OfferApprovedEvent;
use App\Services\Offer\OfferService;

class OfferListener
{
    private OfferService $offerService;

    public function __construct(OfferService $offerService)
    {
        $this->offerService = $offerService;
    }

    public function handle(OfferApprovedEvent $event)
    {
        $this->offerService->send($event->getCandidateID(), $event->getInitiatedBy());
    }
}
