<?php

namespace App\Services\FirstAdvantage;

use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Class PackageManager
 * @package App\Services\FirstAdvantage\PackageManager
 */
class PackageManager
{
    private const NO_RESPONSE_ERROR = 'Can not fetch packages for account %s';

    private string $accountId;
    private string $packageUrl;
    private array $response;

    /**
     * @param string $accountId
     * @return $this
     */
    public function setAccount(string $accountId): self
    {
        $this->packageUrl = sprintf(config('services.first-advantage.packages.url'), $accountId);
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function fetch(): self
    {
        $response = Http::get($this->packageUrl)->json();

        if (!is_array($response) || empty($response) || !isset($response['package_details'])) {
            throw new Exception(sprintf(self::NO_RESPONSE_ERROR, $this->accountId));
        }

        $this->response = $response;

        return $this;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->response['package_details'];
    }

    /**
     * @return array
     */
    public function getNames(): array
    {
        return array_column($this->get(), 'package_id');
    }

    /**
     * @return array
     */
    public function getIds(): array
    {
        return array_column($this->get(), 'name');
    }
}
