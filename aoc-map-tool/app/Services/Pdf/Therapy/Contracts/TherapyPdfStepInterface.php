<?php

namespace App\Services\Pdf\Therapy\Contracts;

interface TherapyPdfStepInterface
{
    public function getData(int $candidateId): array;

    public function getTemplate(): string;

    public function getFilename(array $data): string;
}
