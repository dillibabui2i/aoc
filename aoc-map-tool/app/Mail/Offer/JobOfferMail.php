<?php

namespace App\Mail\Offer;

use App\DTO\Offer\OfferAttachmentDTO;
use App\Models\Offer;
use App\Services\Offer\OfferResourcesService;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobOfferMail extends Mailable
{
    use Queueable, SerializesModels;

    private const SUBJECT = 'Angels of Care Pediatric Home Health - Job Offer';
    private const ATTACHMENT_NAME = 'Angels of Care Pediatric Home Health - Job Offer.pdf';

    /**
     * @param OfferAttachmentDTO[] $offerAttachments files the recruiter attached to the outcome.
     *                                               The property cannot be named $attachments,
     *                                               Mailable already uses that name internally.
     */
    public function __construct(
        public Offer $offer,
        public OfferResourcesService $offerResourcesService,
        public ?User $clinicalManager = null,
        public array $offerAttachments = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECT,
        );
    }

    public function content(): Content
    {
        $name = $this->offer->finalValues->keyBy('key')->toArray()['employee']['value_string'] ?? '';

        return new Content(view: 'offer.email.template', with: [
            'name' => $name,
            'clinicalManagerName' => $this->clinicalManager->name ?? null,
            'clinicalManagerEmail' => $this->clinicalManager->email ?? null,
        ]);
    }

    public function attachments(): array
    {
        $attachments = [
            Attachment::fromData(
                fn() => $this->offerResourcesService->getRawAttachmentData($this->offer),
                self::ATTACHMENT_NAME
            )->withMime('application/pdf'),
        ];

        foreach ($this->offerAttachments as $offerAttachment) {
            $attachments[] = Attachment::fromPath($offerAttachment->getPath())
                ->as($offerAttachment->getName());
        }

        return $attachments;
    }
}
