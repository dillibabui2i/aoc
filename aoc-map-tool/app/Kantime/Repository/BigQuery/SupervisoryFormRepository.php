<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Exception\MethodNotImplementedException;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class SupervisoryFormRepository extends AbstractCommonRepository
{

    const TABLE_NAME = 'daas_dw_supervisoryformmaster_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    SVF_ECHART_SUBFORMID AS echart_subformid,
                    SVF_ECHART_ID AS echart_id,
                    SCF_SCHEDULE_ID AS schedule_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS client_id,
                    CAST(SVF_FORM_DATE AS STRING) AS form_date,
                    CAST(SVF_FORM_STATUS AS STRING) AS form_status,
                    SVF_COMPLETEDBY_CGID AS completedby_cgid,
                    SVF_SUPERVISEE_CGID AS supervisee_cgid,
                    CAST(SVF_DISCIPLINE AS STRING) AS discipline,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SVF_ECHART_SUBFORMID AS STRING)) AS combinedstring,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SVF_ECHART_SUBFORMID AS STRING)) > ?
                ORDER BY combinedstring
                LIMIT ?
                SQL;
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getRecord($identifier): array|DataItem
    {
        throw new MethodNotImplementedException('Method not implemented');
    }


}
