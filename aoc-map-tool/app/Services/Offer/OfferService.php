<?php

namespace App\Services\Offer;

use App\DTO\Offer\OfferAttachmentDTO;
use App\Enums\CandidateDocumentTypeEnum;
use App\Factories\Offer\OfferMailFacade;
use App\Models\Offer;
use App\Repositories\Candidates\CandidateReadRepository;
use App\Repositories\Documents\DocumentReadRepository;
use App\RightSignatureDocuments;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OfferService
{
    public function __construct(
        private OfferMailFacade $offerMailFacade,
        private CandidateReadRepository $candidateReadRepository,
        private OfferResourcesService  $offerResourcesService,
        private DocumentReadRepository  $documentReadRepository
    )
    {
    }

    public function send(int $candidateID, ?int $initiatedBy = null): void
    {
        $candidate = $this->candidateReadRepository->getCandidateByID($candidateID);
        $recipients = array_unique(array_filter([$candidate->email, $candidate->jobvite_email]));

        if (empty($recipients)) {
            Log::channel('therapy_candidates')->error('Offer letter not sent: candidate has no email on file.', [
                'candidate_id' => $candidateID,
                'initiated_by' => $initiatedBy,
            ]);

            return;
        }

        $clinicalManager = $candidate->interviewManagerUser;
        $attachments = $this->getOfferAttachments($candidateID);
        $offer = $this->offerMailFacade->getInstance(
            $candidate->offer,
            $this->offerResourcesService,
            $clinicalManager,
            $attachments
        );

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue($offer);
        }

        Log::channel('therapy_candidates')->info('Offer letter queued.', [
            'candidate_id' => $candidateID,
            'initiated_by' => $initiatedBy,
            'recipients' => $recipients,
            'attachments' => count($attachments),
        ]);

        $candidate->offer->update(['status' => Offer::STATUS_SENT]);
    }

    /**
     * Files the recruiter attached to the Offer Extended outcome. They are sent together with
     * the offer letter itself, so the candidate gets everything in one email.
     *
     * @return OfferAttachmentDTO[]
     */
    private function getOfferAttachments(int $candidateID): array
    {
        $documents = $this->documentReadRepository->getCandidateDocumentsByType(
            $candidateID,
            CandidateDocumentTypeEnum::OFFER_EXTENDED_ATTACHMENT
        );

        $attachments = [];

        foreach ($documents as $document) {
            $path = storage_path('documents/' . $document->document_filename);

            // A missing file must not fail the whole offer letter, the PDF is the important part.
            if (!is_file($path)) {
                Log::channel('therapy_candidates')->warning('Offer letter attachment skipped: file not found.', [
                    'candidate_id' => $candidateID,
                    'document_id' => $document->id,
                    'path' => $path,
                ]);

                continue;
            }

            $attachments[] = new OfferAttachmentDTO($path, $this->resolveAttachmentName($document));
        }

        return $attachments;
    }

    /**
     * The file on disk carries a generated prefix, the candidate should see the original name.
     */
    private function resolveAttachmentName(RightSignatureDocuments $document): string
    {
        $extraData = json_decode((string)$document->extra_data, true);

        return $extraData['original_filename'] ?? $document->document_filename;
    }
}
