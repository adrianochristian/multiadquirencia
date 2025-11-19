<?php

namespace App\Services\Subacquirers;

use App\Models\Subacquirer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseSubacquirerService
{
    protected Subacquirer $subacquirer;
    protected string $baseUrl;

    public function __construct(Subacquirer $subacquirer)
    {
        $this->subacquirer = $subacquirer;
        $this->baseUrl = $subacquirer->base_url;
    }

    /**
     * Make HTTP request to subacquirer API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            Log::info("Subacquirer Request", [
                'subacquirer' => $this->subacquirer->code,
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'headers' => $headers,
            ]);

            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->$method($url, $data);

            $responseData = $response->json();

            Log::info("Subacquirer Response", [
                'subacquirer' => $this->subacquirer->code,
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            if ($response->failed()) {
                throw new \Exception("Request failed with status {$response->status()}");
            }

            return [
                'success' => true,
                'data' => $responseData,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Subacquirer Request Error", [
                'subacquirer' => $this->subacquirer->code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Normalize status from subacquirer to internal status
     *
     * @param string $status
     * @return string
     */
    abstract protected function normalizeStatus(string $status): string;
}
