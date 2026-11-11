<?php

namespace App\Services\FirstAdvantage;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirstAdvantageService
{

    private ?string $baseUrl = null;

    private const TEXAS_DEFAULT = "texas";

    private const TEXAS_PACKAGE = "Texas Package";

    private const MCKINNEY_PACKAGE = "Corporate Screening";

    public function __construct()
    {
        if (config("FI_MOCK_ENABLED")) {
            $this->baseUrl = "https://private-anon-dac750aaf5-fadvrestapi.apiary-mock.com/api/v1.1";
        } else {
            $env = $this->getEnv();
            $this->baseUrl = config("firstadvantage.{$env}.url");
        }
    }

    private function getEnv(): ?string
    {
        return env("APP_ENV");
    }

    /**
     * Retrieves Account ID
     *
     * @param string $packageConfig
     * @return string|null
     */
    public function getAccountID(string $packageConfig): ?string
    {
        $env = $this->getEnv();

        return config("firstadvantage.{$env}.account.{$packageConfig}");
    }

    /**
     * Retrieves api key
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        $env = $this->getEnv();

        return config("firstadvantage.{$env}.apiKey");
    }


    /**
     * Creates a candidate
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/candidate/get-candidate-by-id/retrieve-candidate.html
     *
     * @param array $data
     * @return mixed
     * @throws RequestException
     */
    public function createCandidate(array $data): mixed
    {
        try {
            $url = $this->endpoint("candidate");
            Log::channel('first_advantage')->error('First Advantage -> createCandidate -> [REQUEST]', [
                'url' => $url,
                'headers' => [
                    'Authorization' => $this->getApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'body' => $data,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])->post($url, $data)->throw();

            $output = $response->json();

            Log::channel('first_advantage')->error('First Advantage -> createCandidate -> [RESPONSE]', [
                'url' => $url,
                'response' => $output,
            ]);

            return $output;
          } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> createCandidate -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'method' => 'createCandidate',
                'response' => $this->getExceptionBody($e)
            ]);

            $errorBody = $this->getExceptionBody($e);

            return [
                'error' => true,
                'code' => $e->getCode(),
                'message' => $this->getExcepctionMessage($errorBody)
            ];
        }
    }

    /**
     * Retrieves a candidate by id
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/candidate/get-candidate-by-id/retrieve-candidate.html
     *
     * @param string $id
     * @return mixed
     * @throws RequestException
     */
    public function retrieveCandidateById(string $id): mixed
    {
        try {
            $url = $this->endpoint("candidate/" . $id);

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])->get($url)->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> retrieveCandidateByID -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'retrieveCandidateById',
                'response' => $this->getExceptionBody($e)
            ]);

            throw $e;
        }
    }

    /**
     * Creates an order
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/order/orders/orders.html
     *
     * @param array $data
     * @return mixed
     * @throws RequestException
     */
    public function createOrder(array $data): mixed
    {
        try {

            $url = $this->endpoint("orders");

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])
            ->post($this->endpoint($url), $data)->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> createOrder -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'createOrder',
                'response' => $this->getExceptionBody($e)
            ]);

            throw $e;
        }
    }

    /**
     * Order adjudicate by id
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/order/orders-adjudicate-by-id/ordersadjudicatebyid.html
     *
     * @param string $id
     * @return mixed
     * @throws RequestException
     */
    public function orderAdjucidateById(string $id): mixed
    {
        try {

            $url = $this->endpoint("orders");

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])->post($this->endpoint($url . "/" . $id . "/adjudicate"))->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> orderAdjudicatedByID -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'orderAdjucidateById',
                'response' => $this->getExceptionBody($e)
            ]);

            throw $e;
        }
    }

    /**
     * The recruiter initiates a request for the candidate to complete an application.
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/invite/invite.html
     *
     * @param array $data
     * @return mixed
     * @throws RequestException
     */
    public function invite(array $data): mixed
    {
        try {
            $url = $this->endpoint('invite');

            $env = $this->getEnv();
            $postbackUrl = config("firstadvantage.{$env}.webhook");

            $data['notification_url'] = $postbackUrl;
            $data['cdc_notification_url'] = $postbackUrl;

            Log::channel('first_advantage')->error('First Advantage -> invite -> [REQUEST]', [
                'url' => $url,
                'headers' => [
                    'Authorization' => $this->getApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'body' => $data,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])->post($url, $data)->throw();

            $output = $response->json();

            Log::channel('first_advantage')->error('First Advantage -> invite -> [RESPONSE]', [
                'url' => $url,
                'response' => $output,
            ]);

            return $output;
        } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> invite -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'invite',
                'response' => $this->getExceptionBody($e)
            ]);

            $errorBody = $this->getExceptionBody($e);

            return [
                'error' => true,
                'code' => $e->getCode(),
                'message' => $this->getExcepctionMessage($errorBody)
            ];
        }
    }

    /**
     * List all the packages
     *
     * @see https://jsapi.apiary.io/apis/fadvrestapi/reference/invite/invite.html
     *
     * @param string $packageName
     * @return mixed
     * @throws RequestException
     */
    public function packages(?string $packageName = null): mixed
    {
        try {

            $packageConfig = $packageName ?? self::TEXAS_DEFAULT;

            $url = $this->endpoint('accounts/' .$this->getAccountID($packageConfig) . '/packages');

            $response = Http::withHeaders([
                'Authorization' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ])->get($url, )->throw();

            $output = $response->json();

            $nameFilter = (
                $packageName === self::TEXAS_DEFAULT ||
                $packageName === null
            ) ? self::TEXAS_PACKAGE : self::MCKINNEY_PACKAGE;

            $package = collect($output['package_details'])
                ->where('name', $nameFilter)
                ->first() ?? null;

            return $package;
        } catch (RequestException $e) {
            Log::channel('first_advantage')->error('First Advantage -> packages -> [EXCEPTION]', [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'packages',
                'response' => $this->getExceptionBody($e)
            ]);

            $errorBody = $this->getExceptionBody($e);

            return [
                'error' => true,
                'code' => $e->getCode(),
                'message' => $this->getExcepctionMessage($errorBody)
            ];
        }
    }

    protected function endpoint(string $url): string
    {
        return $this->baseUrl . "/" . $url;
    }

    private function getExceptionBody(RequestException $exception): mixed
    {
        return $exception->response->json();
    }

    private function getExcepctionMessage(array $exceptionError): string
    {
        return $exceptionError['message'] ?? 'First Advantage internal error';
    }
}
