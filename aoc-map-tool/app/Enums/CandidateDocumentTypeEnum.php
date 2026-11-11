<?php

namespace App\Enums;

/**
 * Values of the aoc_documents.document_type column for documents of a candidate
 * (aoc_documents.entity_type = EntityTypeEnum::CANDIDATE).
 */
enum CandidateDocumentTypeEnum: string
{
    case OFFER_ACCEPTED = 'offer_accepted';
    case NEO_ATTACHMENT = 'neo_attachment';
    case OFFER_EXTENDED_ATTACHMENT = 'offer_extended_attachment';
}
