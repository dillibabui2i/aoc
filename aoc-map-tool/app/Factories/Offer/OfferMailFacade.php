<?php

namespace App\Factories\Offer;

use App\DTO\Offer\OfferAttachmentDTO;
use App\Mail\Offer\JobOfferMail;
use App\Models\Offer;
use App\Services\Offer\OfferResourcesService;
use App\User;

class OfferMailFacade
{
    /**
     * @param OfferAttachmentDTO[] $offerAttachments
     */
    public function getInstance(
        Offer $offer,
        OfferResourcesService $offerResourcesService,
        ?User $clinicalManager = null,
        array $offerAttachments = []
    ): JobOfferMail
    {
        return new JobOfferMail($offer, $offerResourcesService, $clinicalManager, $offerAttachments);
    }
}
