<?php

namespace App\Services\Utils;

use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class AwsSecrets
{
    /** @var array<string, string|null> */
    private array $memory = [];

    public function __construct(
        private readonly SecretsManagerClient $client,
        private readonly CacheRepository $cache,
    ) {}

    public function getString(string $secretId, int $ttlSeconds = 3600): ?string
    {
        // Per-request/job memory cache
        if (array_key_exists($secretId, $this->memory)) {
            return $this->memory[$secretId];
        }

        $cacheKey = 'aws_secrets_manager:' . sha1($secretId);

        $value = $this->cache->remember($cacheKey, $ttlSeconds, function () use ($secretId) {
            $result = $this->client->getSecretValue(['SecretId' => $secretId]);

            // Most secrets are SecretString; keep SecretBinary support just in case
            if (!empty($result['SecretString'])) {
                return (string) $result['SecretString'];
            }

            if (!empty($result['SecretBinary'])) {
                return base64_decode((string) $result['SecretBinary']) ?: null;
            }

            return null;
        });

        return $this->memory[$secretId] = $value;
    }

    /** @return array<string,mixed> */
    public function getJson(string $secretId, int $ttlSeconds = 3600): array
    {
        $json = $this->getString($secretId, $ttlSeconds);
        if ($json === null || $json === '') {
            return [];
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function forget(string $secretId): void
    {
        unset($this->memory[$secretId]);
        $this->cache->forget('aws_secrets_manager:' . sha1($secretId));
    }
}
