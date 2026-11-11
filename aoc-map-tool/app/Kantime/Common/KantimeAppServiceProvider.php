<?php

namespace App\Kantime\Common;

use App\Kantime\Repository\KantimeRepositoryInterface;
use App\Services\Utils\AwsSecrets;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\ServiceProvider;

class KantimeAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $map = config('kantime.repositories', []);
        foreach ($map as $serviceClass => $impl) {
            $this->app
                ->when($serviceClass)
                ->needs(KantimeRepositoryInterface::class)
                ->give(fn ($app) => $app->make($impl));
        }

        $this->app->scoped(BigQueryClient::class, function () {
            $cfg = config('kantime.bigquery', []);

            $opts = [
                'projectId' => $cfg['project_id'] ?? null,
                'location'  => $cfg['location'] ?? 'US',
            ];

            $secretArn = config('kantime.secrets.bigquery_arn');
            if (!empty($secretArn)) {
                $secrets = app(AwsSecrets::class);

                $keyFile = $secrets->getJson($secretArn);
                if (!empty($keyFile)) {
                    $opts['keyFile'] = $keyFile;
                    if (!empty($keyFile['project_id'])) {
                        $opts['projectId'] = $keyFile['project_id'];
                    }
                }
            }
            if (empty($opts['keyFile']) && !empty($cfg['key_file'])) {
                $opts['keyFilePath'] = $cfg['key_file'];
            }

            return new BigQueryClient($opts);
        });

        $this->app->singleton(BigQueryConfig::class, function () {
            return new BigQueryConfig(
                config('kantime.bigquery.project_id'),
                config('kantime.bigquery.dataset'),
            );
        });

        $this->app->singleton(KantimeSyncConfig::class, function () {
            return new KantimeSyncConfig(config('kantime.sync.debug', false));
        });

        $this->app->scoped(BigQueryConnection::class);

        $this->app->singleton(KantimeScheduler::class);

        $this->app->singleton(JobQueueConfig::class, fn () => new JobQueueConfig(
            daasQueue: config('kantime.queue.daas', 'daas-sync'),
            overlapTtlSeconds: (int) config('kantime.sync.overlap_ttl_seconds', 3600),
            defaultLimit: (int) config('kantime.sync.default_limit', 1000),
            daasConnection: (string) config('kantime.sources.daas.connection', 'daas'),
        ));
    }

    public function boot(): void
    {
    }
}
