<?php

namespace App\Repositories\Documents;

use App\Enums\CandidateDocumentTypeEnum;
use App\Enums\EntityTypeEnum;
use App\RightSignatureDocuments;
use Illuminate\Database\Eloquent\Collection;

class DocumentReadRepository
{
    public function __construct(private RightSignatureDocuments $document)
    {
    }

    public function getCandidateDocumentsByType(int $candidateID, CandidateDocumentTypeEnum $type): Collection
    {
        return $this->document->newQuery()
            ->where('entity_type', EntityTypeEnum::CANDIDATE->value)
            ->where('entity_id', $candidateID)
            ->where('document_type', $type->value)
            ->orderBy('id')
            ->get();
    }
}
