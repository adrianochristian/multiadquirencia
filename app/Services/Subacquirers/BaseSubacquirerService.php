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
            $maskedRequestData = $this->maskSensitive($data);
            $url = $this->baseUrl . $endpoint;

            Log::info("Subacquirer Request", [
                'subacquirer' => $this->subacquirer->code,
                'method' => $method,
                'url' => $url,
                'data' => $maskedRequestData,
                'headers' => $headers,
            ]);

            $response = Http::timeout((int) config('subacquirers.http_timeout', 5))
                ->withHeaders($headers)
                ->$method($url, $data);

            $responseData = $response->json();

            Log::info("Subacquirer Response", [
                'subacquirer' => $this->subacquirer->code,
                'status' => $response->status(),
                'response' => is_array($responseData) ? $this->maskSensitive($responseData) : $responseData,
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
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
                continue;
            }

            if (in_array($key, ['document', 'payer_cpf', 'holder_document'], true)) {
                $data[$key] = $this->maskDocument((string) $value);
            }
        }

        return $data;
    }

    protected function maskDocument(string $document): string
    {
        $lastDigits = substr($document, -4);

        return '***' . $lastDigits;
    }

    /**
     * Normalize status from subacquirer to internal status
     *
     * @param string $status
     * @return string
     */
    abstract protected function normalizeStatus(string $status): string;
}
