<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CmsClient
{
    private string $internalUrl;

    private string $publicUrl;

    private ?string $authToken;

    public function __construct()
    {
        $this->internalUrl = config('services.cheminf.internal_api_url');
        $this->publicUrl = config('services.cheminf.api_url');
        $this->authToken = config('services.cheminf.internal_token');
    }

    /**
     * Make a GET request to the CMS API
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function get(string $endpoint, array $params = [])
    {
        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Make a POST request to the CMS API
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Make the actual HTTP request
     *
     * @return \Illuminate\Http\Client\Response
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $request = Http::timeout(120);

        // Add authentication token if available
        if ($this->authToken) {
            $request = $request->withHeaders([
                'Authorization' => 'Bearer '.$this->authToken,
            ]);
        }

        $url = $this->internalUrl.ltrim($endpoint, '/');

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Get the public URL for the CMS API (for frontend use)
     */
    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }
}
