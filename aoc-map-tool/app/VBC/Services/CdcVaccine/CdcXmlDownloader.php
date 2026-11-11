<?php

namespace App\VBC\Services\CdcVaccine;

use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

class CdcXmlDownloader
{
    public function __construct(private HttpFactory $http)
    {
    }

    /**
     * @throws CdcSyncException on a transport error, a non-2xx response or an empty body
     */
    public function download(string $url): string
    {
        try {
            $response = $this->http
                ->timeout((int) config('cdc_vaccines.http_timeout'))
                ->retry((int) config('cdc_vaccines.http_retries'), 1000, throw: false)
                ->get($url);
        } catch (Throwable $e) {
            throw new CdcSyncException("CDC download failed for {$url}: {$e->getMessage()}", 0, $e);
        }

        if (!$response->successful()) {
            throw new CdcSyncException("CDC download failed for {$url}: HTTP {$response->status()}");
        }

        $body = trim($response->body());

        if ($body === '') {
            throw new CdcSyncException("CDC download returned an empty body for {$url}");
        }

        return $body;
    }
}
