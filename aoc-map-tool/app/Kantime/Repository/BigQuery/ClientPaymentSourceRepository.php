<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use Generator;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class ClientPaymentSourceRepository implements KantimeRepositoryInterface
{
    private const TABLE_NAME = 'daas_dw_clientpaymentsources_vw';

    public function __construct(private readonly BigQueryConnection $connection)
    {
    }

    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): Generator
    {
        $params = [
            'combinedString' => (string) $offset,
            'limit' => $limit,
        ];

        foreach ($this->connection->cursor($this->buildQuery(), $params) as $row) {
            yield [
                'lastModified' => (string) ($row['lastModified'] ?? ''),
                'combinedString' => (string) ($row['combinedString'] ?? ''),
                'agencyId' => $row['agencyId'] ?? null,
                'clientId' => $row['clientId'] ?? null,
                'intakeId' => $row['intakeId'] ?? null,
                'clientPsIsPrimary' => $row['clientPsIsPrimary'] ?? null,
                'clientPsPayertype' => $row['clientPsPayertype'] ?? null,
                'clientPsStartDate' => isset($row['clientPsStartDate']) ? (string) $row['clientPsStartDate'] : null,
                'clientPsEndDate' => isset($row['clientPsEndDate']) ? (string) $row['clientPsEndDate'] : null,
                'clientPsEffectiveperiod' => $row['clientPsEffectiveperiod'] ?? null,
                'clientPsPrimaryId' => $row['clientPsPrimaryId'] ?? null,
                'clientPsParentId' => $row['clientPsParentId'] ?? null,
                'clientPsPrimaryPayerId' => $row['clientPsPrimaryPayerId'] ?? null,
                'clientPsAdditionalPayerId' => $row['clientPsAdditionalPayerId'] ?? null,
                'clientPsUniqueId' => $row['clientPsUniqueId'] ?? null,
                'daasLastModifiedTimestamp' => isset($row['daasLastModifiedTimestamp']) ? (string) $row['daasLastModifiedTimestamp'] : null,
                'clientPsEffperiodInternalId' => $row['clientPsEffperiodInternalId'] ?? null,
                'clientPsPrimaryInternalId' => $row['clientPsPrimaryInternalId'] ?? null,
                'clientPsAddpayerInternalId' => $row['clientPsAddpayerInternalId'] ?? null,
                'clentPsInsuranceNo' => $row['clentPsInsuranceNo'] ?? null,
                'etlUpdatedTimestamp' => isset($row['etlUpdatedTimestamp']) ? (string) $row['etlUpdatedTimestamp'] : null,
                'isValidRecord' => $row['isValidRecord'] ?? null,
                'csUniqueId' => $row['csUniqueId'] ?? null,
            ];
        }
    }

    private function buildQuery(): string
    {
        $tableRef = $this->connection->tableRef(self::TABLE_NAME);

        return <<<SQL
            SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastModified,
                CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    CONCAT(
                        CAST(CLIENT_PS_EFFPERIOD_INTERNAL_ID AS STRING),
                        CAST(CLIENT_PS_PRIMARY_INTERNAL_ID AS STRING),
                        CAST(CLIENT_PS_ADDPAYER_INTERNAL_ID AS STRING)
                    )
                ) AS combinedString,
                AGENCY_ID AS agencyId,
                CLIENT_ID AS clientId,
                INTAKE_ID AS intakeId,
                CAST(CLIENT_PS_IS_PRIMARY AS BOOL) AS clientPsIsPrimary,
                CLIENT_PS_PAYERTYPE AS clientPsPayertype,
                CAST(CLIENT_PS_START_DATE AS STRING) AS clientPsStartDate,
                CAST(CLIENT_PS_END_DATE AS STRING) AS clientPsEndDate,
                CAST(CLIENT_PS_EFFECTIVEPERIOD AS STRING) AS clientPsEffectiveperiod,
                CAST(CLIENT_PS_PRIMARY_ID AS STRING) AS clientPsPrimaryId,
                CAST(CLIENT_PS_PARENT_ID AS STRING) AS clientPsParentId,
                CLIENT_PS_PRIMARY_PAYER_ID AS clientPsPrimaryPayerId,
                CLIENT_PS_ADDITIONAL_PAYER_ID AS clientPsAdditionalPayerId,
                CAST(CLIENT_PS_UNIQUE_ID AS STRING) AS clientPsUniqueId,
                CAST(DAAS_LAST_MODIFIED_TIMESTAMP AS STRING) AS daasLastModifiedTimestamp,
                CLIENT_PS_EFFPERIOD_INTERNAL_ID AS clientPsEffperiodInternalId,
                CLIENT_PS_PRIMARY_INTERNAL_ID AS clientPsPrimaryInternalId,
                CLIENT_PS_ADDPAYER_INTERNAL_ID AS clientPsAddpayerInternalId,
                CAST(CLENT_PS_INSURANCE_NO AS STRING) AS clentPsInsuranceNo,
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etlUpdatedTimestamp,
                CAST(ISVALIDRECORD AS BOOL) AS isValidRecord,
                CONCAT(
                    CAST(CLIENT_PS_EFFPERIOD_INTERNAL_ID AS STRING),
                    CAST(CLIENT_PS_PRIMARY_INTERNAL_ID AS STRING),
                    CAST(CLIENT_PS_ADDPAYER_INTERNAL_ID AS STRING)
                ) AS csUniqueId
            FROM `{$tableRef}`
            WHERE CONCAT(
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                CONCAT(
                    CAST(CLIENT_PS_EFFPERIOD_INTERNAL_ID AS STRING),
                    CAST(CLIENT_PS_PRIMARY_INTERNAL_ID AS STRING),
                    CAST(CLIENT_PS_ADDPAYER_INTERNAL_ID AS STRING)
                )
            ) > @combinedString
            ORDER BY combinedString
            LIMIT @limit
            SQL;
    }

    public function getRecord($identifier) : array | DataItem
    {
        // TODO: Implement getRecord() method.
    }
}
