<?php

namespace App\Services\Offer;

use App\Models\Offer;

use App\RightSignatureDocuments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PDF;
use App\Enums\CandidateDocumentTypeEnum;
use App\Enums\EntityTypeEnum;

class OfferResourcesService
{
    public const OFFER_EXTENDED_FILES_MAX_COUNT = 10;
    public const OFFER_EXTENDED_FILES_MAX_TOTAL_SIZE = 52428800; // 50 MB
    public const OFFER_EXTENDED_FILES_MIMES = ['pdf','doc','docx'];

    public function getRawAttachmentData(Offer $offer): string
    {
        $offer->loadMissing('finalCompensationItems.offerField');

        return PDF::loadView('offer.pdf.document', [
            'offer' => $offer->finalValues->keyBy('key'),
            'compensationItems' => $offer->finalCompensationItems,
        ])->output();
    }

    /**
     * Saves the Offer Extended attachments the same way as the other candidate documents:
     * on disk in storage/documents and referenced in the aoc_documents table.
     * @param UploadedFile[] $files
     */
    public function storeOfferExtendedFiles(array $files, $candidateId): void
    {
        $documentsPath = storage_path() . '/documents/';
        if (!is_dir($documentsPath)) {
            mkdir($documentsPath);
        }

        $rows = [];

        foreach ($files as $file) {
            $clientName = $file->getClientOriginalName();

            // Several files are uploaded at once, so time() alone is not unique enough.
            // The name is trimmed as well because document_filename is a varchar(200).
            $filename = time() . '_' . Str::random(8) . '_'
                . Str::limit(pathinfo($clientName, PATHINFO_FILENAME), 150, '')
                . '.' . $file->getClientOriginalExtension();
            $file->move($documentsPath, $filename);

            $rows[] = [
                'entity_type' => EntityTypeEnum::CANDIDATE->value,
                'entity_id' => $candidateId,
                'tracker_id' => null,
                'document_type' => CandidateDocumentTypeEnum::OFFER_EXTENDED_ATTACHMENT->value,
                'document_filename' => $filename,
                'description' => date('Y') . ' Offer Extended Attachment',
                // Keeps the name the recruiter picked the file with, it is the one the
                // candidate sees when the file is attached to the offer letter.
                'extra_data' => json_encode(['original_filename' => $clientName]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($rows)) {
            RightSignatureDocuments::insert($rows);
        }
    }
}
