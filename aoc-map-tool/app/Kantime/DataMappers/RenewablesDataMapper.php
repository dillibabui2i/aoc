<?php

namespace App\Kantime\DataMappers;

use App\Kantime\Domain\RenewableItem;

final class RenewablesDataMapper
{
    /**
     * Map a raw BigQuery row into a RenewableItem DTO.
     */
    public function fromDaasRow(array $row): RenewableItem
    {
        return new RenewableItem(
            lastModified:     $row['LASTMODIFIED'] ?? '',
            uniqueKey:        $row['UNIQUE_KEY'] ?? '',
            hha:              $row['HHA'] ?? null,
            isValidRecord:    filter_var($row['ISVALIDRECORD'] ?? true, FILTER_VALIDATE_BOOLEAN),
            renewClinicianId: $row['RENEW_CLINICIAN_ID'] ?? null,
            providerId:       $row['ProviderID'] ?? null,
            expirationDate:   $row['expiration_date'] ?? null,
            renewableItemId:  (int) $row['renewable_item_id'] ?? 0,
            issuedDate:       $row['issued_date'] ?? null,
            mandatory:        $row['mandatory'] ?? null,
            renewablesType:   $row['renewables_type'] ?? null,
            renewItem:        $row['renew_item'] ?? null,
            licenseNo:        $row['license_no'] ?? null,
            licenseState:     $row['license_state'] ?? null,
        );
    }

    /**
     * Convert DTO into DB-ready array (for insert/upsert).
     *
     * Keep DB column naming consistent with your existing renewables table.
     */
    public function toDbRow(RenewableItem $item, int $aocProviderId): array
    {
        return [
            'aoc_provider_id'     => $aocProviderId,
            'renewable_item_id'   => $item->renewableItemId,
            'renewables_type'     => $item->renewablesType,
            'expiration_date'     => $item->expirationDate ?? '',
            'issued_date'         => $item->issuedDate ?? '',
            'mandatory'           => is_bool($item->mandatory) ? ($item->mandatory ? 1 : 0) : null,
            'license_no'          => $item->licenseNo ?? '',
            'license_state'       => $item->licenseState ?? '',
            'ProviderID'          => $item->providerId ?? '',
            'UniqueKey'           => $item->uniqueKey ?? '',
        ];
    }
}
