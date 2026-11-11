<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class FirstAdvantageService
{
    private $baseUrl = null;

    public function __construct()
    {
        $this->baseUrl = (env("APP_ENV") === "local") ?
            (
                config("FI_MOCK_ENABLED") === true ?
                "https://private-anon-dac750aaf5-fadvrestapi.apiary-mock.com/api/v1.1" : # MOCK URL
                "https://rest-uat.fadv.com/api/v1.1" # Test env
            ) :
            "prod-url"; # Production env
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
            $response = Http::post($url, $data)->throw();

            return $response->json();
        } catch (RequestException $e) {
            $message = "Internal First Advantage request error: " . $e->getMessage();

            Log::error($message, [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'createCandidate',
                'response' => optional($e->response)?->body(),
            ]);

            throw $e;
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
            $response = Http::get($url)->throw();

            return $response->json();
        } catch (RequestException $e) {
            $message = "Internal First Advantage request error: " . $e->getMessage();

            Log::error($message, [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'retrieveCandidateById',
                'response' => optional($e->response)?->body(),
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
            $response = Http::post($this->endpoint($url), $data)->throw();

            return $response->json();
        } catch (RequestException $e) {
            $message = "Internal First Advantage request error: " . $e->getMessage();

            Log::error($message, [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'createOrder',
                'response' => optional($e->response)?->body(),
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
            $response = Http::post($this->endpoint($url . "/" . $id))->throw();

            return $response->json();
        } catch (RequestException $e) {
            $message = "Internal First Advantage request error: " . $e->getMessage();

            Log::error($message, [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'createOrder',
                'response' => optional($e->response)?->body(),
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

            $url = $this->endpoint("invite");
            $response = Http::post($this->endpoint($url), $data)->throw();

            return $response->json();
        } catch (RequestException $e) {
            $message = "Internal First Advantage request error: " . $e->getMessage();

            Log::error($message, [
                'url' => $url,
                'code' => $e->getCode(),
                'method' => 'invite',
                'response' => optional($e->response)?->body(),
            ]);

            throw $e;
        }
    }

    protected function endpoint(string $url): string
    {
        return $this->baseUrl . "/" . $url;
    }
}
