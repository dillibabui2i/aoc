<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Exception\MethodNotImplementedException;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class PaymentSourceRepository implements KantimeRepositoryInterface
{

    const TABLE_NAME = 'daas_dw_paymentsourcemaster_vw';
    private $tableRef;

    public function __construct(private readonly BigQueryConnection $connection)
    {
        $this->tableRef = $this->connection->tableRef(self::TABLE_NAME);
    }


    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $query = <<<SQL
            SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(PS_PAYERSOURCEID AS STRING)) AS combinedstring,
                PS_PAYERSOURCEID AS payersource_id,
                AGENCY_ID AS agency_id,
                CAST(PS_NAME AS STRING) AS name,
                PS_IS_CHILDCARE AS is_childcare,
                PS_IS_EPISODICAL AS is_episodical,
                PS_PROGRAM_ID AS program_id,
                CAST(S_PROGRAM_TYPE AS STRING) AS program_type,
                PS_PAYERGROUP_ID AS payergroup_id,
                CAST(PS_PAYERGROUP_NAME AS STRING) AS payergroup_name,
                CAST(PS_CARETYPE AS STRING) AS caretype
            FROM `$this->tableRef`
            WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(PS_PAYERSOURCEID AS STRING)) > @combined_string
            ORDER BY combinedstring
            LIMIT @limit
            SQL;

        $params = [
            'combined_string' => (string) $offset,
            'limit' => $limit,
        ];

        foreach ($this->connection->cursor($query, $params) as $row) {
            yield $row;
        }
    }

    public function getRecord($identifier) : array | DataItem
    {
        throw new MethodNotImplementedException('Method not implemented');
    }
}
