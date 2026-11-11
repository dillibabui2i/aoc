<?php

namespace App\VBC\Services\CdcVaccine;

use App\VBC\DTO\CdcVaccine\CdcVaccineGroupDTO;
use App\VBC\DTO\CdcVaccine\CdcVaxToGroupDTO;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * Parses the CDC "vax2vg" file: <VGCodes><CVXVGInfo> records, each a flat sequence of alternating
 * <Name>/<Value> elements (ShortDescription, CVXCode, Status, Vaccine Group Name, CVX for Vaccine Group).
 */
class CdcVaxToGroupXmlParser
{
    private const ROOT = 'VGCodes';
    private const RECORD = 'CVXVGInfo';
    private const FIELD_CVX_CODE = 'CVXCode';
    private const FIELD_GROUP_NAME = 'Vaccine Group Name';
    private const FIELD_GROUP_CODE = 'CVX for Vaccine Group';

    public function __construct(private CdcXmlReader $reader, private LoggerInterface $logger)
    {
    }

    /**
     * @throws CdcSyncException
     */
    public function parse(string $xml): CdcVaxToGroupDTO
    {
        $root = $this->reader->read($xml, self::ROOT);

        $groups = [];
        $vaccineGroupCodes = [];
        $records = 0;

        foreach ($root->{self::RECORD} as $record) {
            $records++;
            $fields = $this->fields($record);

            $groupCode = $this->toInt($fields[self::FIELD_GROUP_CODE] ?? null);

            if ($groupCode === null) {
                throw new CdcSyncException('Invalid CDC vax2vg record: missing "' . self::FIELD_GROUP_CODE . '"');
            }

            $groupName = ($fields[self::FIELD_GROUP_NAME] ?? '') !== '' ? $fields[self::FIELD_GROUP_NAME] : null;

            if (!isset($groups[$groupCode])) {
                $groups[$groupCode] = new CdcVaccineGroupDTO($groupCode, $groupName);
            }

            $cvxCode = $this->toInt($fields[self::FIELD_CVX_CODE] ?? null);

            if ($cvxCode === null) {
                throw new CdcSyncException('Invalid CDC vax2vg record: missing "' . self::FIELD_CVX_CODE . '"');
            }

            if (!isset($vaccineGroupCodes[$cvxCode])) {
                $vaccineGroupCodes[$cvxCode] = $groupCode;
            } elseif ($vaccineGroupCodes[$cvxCode] !== $groupCode) {
                $this->logger->warning('CDC vaccine belongs to several groups; first group kept', [
                    'cvx_code' => $cvxCode,
                    'kept_group_code' => $vaccineGroupCodes[$cvxCode],
                    'ignored_group_code' => $groupCode,
                ]);
            }
        }

        if ($records === 0) {
            throw new CdcSyncException('Invalid CDC vax2vg file: no <' . self::RECORD . '> records');
        }

        return new CdcVaxToGroupDTO($groups, $vaccineGroupCodes);
    }

    /**
     * @return array<string, string> trimmed Name => trimmed Value
     */
    private function fields(SimpleXMLElement $record): array
    {
        $fields = [];
        $name = null;
        foreach ($record->children() as $child) {
            if ($child->getName() === 'Name') {
                $name = trim((string) $child);
            } elseif ($child->getName() === 'Value' && $name !== null) {
                $fields[$name] = trim((string) $child);
                $name = null;
            }
        }

        return $fields;
    }

    private function toInt(?string $value): ?int
    {
        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
