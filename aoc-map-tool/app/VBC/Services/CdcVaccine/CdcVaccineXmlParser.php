<?php

namespace App\VBC\Services\CdcVaccine;

use App\VBC\DTO\CdcVaccine\CdcVaccineDTO;
use DateTimeImmutable;

/**
 * Parses the CDC "cvx" file: <CVXCodes><CVXInfo> records with ShortDescription, FullVaccinename,
 * CVXCode, Notes, Status and LastUpdated (M/D/YYYY) child elements.
 */
class CdcVaccineXmlParser
{
    private const ROOT = 'CVXCodes';
    private const RECORD = 'CVXInfo';
    private const SOURCE_DATE_FORMAT = '!n/j/Y';
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(private CdcXmlReader $reader)
    {
    }

    /**
     * @return array<int, CdcVaccineDTO> keyed by cvx_code
     * @throws CdcSyncException
     */
    public function parse(string $xml): array
    {
        $root = $this->reader->read($xml, self::ROOT);

        $vaccines = [];
        $records = 0;
        foreach ($root->{self::RECORD} as $record) {
            $records++;
            $cvxCode = trim((string) $record->CVXCode);

            if (!ctype_digit($cvxCode)) {
                throw new CdcSyncException('Invalid CDC cvx record: missing "CVXCode"');
            }

            $vaccines[(int) $cvxCode] = new CdcVaccineDTO(
                (int) $cvxCode,
                $this->nullIfEmpty((string) $record->FullVaccinename),
                $this->nullIfEmpty((string) $record->ShortDescription),
                $this->nullIfEmpty((string) $record->Status),
                $this->parseDate((string) $record->LastUpdated),
            );
        }

        if ($records === 0) {
            throw new CdcSyncException('Invalid CDC cvx file: no <' . self::RECORD . '> records');
        }

        return $vaccines;
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(self::SOURCE_DATE_FORMAT, $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format(self::DATE_FORMAT);
    }
}
