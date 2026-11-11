<?php

namespace App\Kantime\Common;

use App\Kantime\Exception\KantimeSyncException;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Job;
use Google\Cloud\BigQuery\QueryResults;

final readonly class BigQueryConnection implements ConnectionInterface
{
    public function __construct(private readonly BigQueryClient $client, private readonly BigQueryConfig $config)
    {
    }

    /**
     * Execute raw SQL query and return QueryResults.
     */
    public function query(string $sql, array $params = []): QueryResults
    {
        $queryConfig = $this->client->query($sql)->useLegacySql(false);

        if (!empty($params)) {
            $queryConfig = $queryConfig->parameters($params);
        }

        /** @var Job $job */
        $job = $this->client->startQuery($queryConfig);
        $job->waitUntilComplete();

        if (!$job->isComplete()) {
            throw new KantimeSyncException('BigQuery job did not complete successfully.');
        }

        $results = $job->queryResults();

        if (!$results->isComplete()) {
            throw new KantimeSyncException('BigQuery results are not complete.');
        }

        return $results;
    }

    /**
     * Execute query and stream results row-by-row using Generator.
     *
     * Note: BigQuery is paginated (not a true server-side cursor), but this
     * iteration is memory-friendly because pages are fetched lazily.
     */
    public function cursor(string $sql, array $params = []): \Generator
    {
        // Important: pass params through so named parameters like @offset/@limit work.
        $results = $this->query($sql, $params);

        foreach ($results as $row) {
            yield $this->rowToArray($row);
        }
    }

    /**
     * Convert associative PHP array into BigQuery named parameters.
     */
    private function buildParameters(array $params): array
    {
        $formatted = [];

        foreach ($params as $name => $value) {
            $formatted[] = [
                'name' => (string) $name,
                'parameterType' => $this->inferType($value),
                'parameterValue' => $this->parameterValue($value),
            ];
        }

        return $formatted;
    }

    private function inferType(mixed $value): array
    {
        return match (true) {
            is_int($value)   => ['type' => 'INT64'],
            is_float($value) => ['type' => 'FLOAT64'],
            is_bool($value)  => ['type' => 'BOOL'],
            $value instanceof \DateTimeInterface => ['type' => 'TIMESTAMP'],
            default          => ['type' => 'STRING'],
        };
    }

    private function parameterValue(mixed $value): array
    {
        if ($value instanceof \DateTimeInterface) {
            return ['value' => $value->format('Y-m-d\\TH:i:sP')];
        }

        return ['value' => is_scalar($value) ? (string) $value : json_encode($value)];
    }

    private function rowToArray($row): array
    {
        $data = [];

        foreach ($row as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    public function getClient(): BigQueryClient
    {
        return $this->client;
    }

    public function tableRef(string $tableName) : string
    {
        // BigQuery table refs should be fully qualified: `project.dataset.table`.
        $project = $this->config->projectId();
        $dataset = $this->config->dataset();

        if ($dataset === '') {
            return $tableName;
        }

        $prefix = $project !== '' ? ($project . '.') : '';

        return $prefix . $dataset . '.' . $tableName;
    }
}
