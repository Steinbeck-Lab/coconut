<?php

namespace App\Services\DOI;

use Config;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class DataCite implements DOIService
{
    protected Client $client;

    protected string $prefix;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => Config::get('doi.'.Config::get('doi.default').'.endpoint'),
            'auth' => [Config::get('doi.'.Config::get('doi.default').'.username'), Config::get('doi.'.Config::get('doi.default').'.secret')],
            'headers' => [
                'Accept' => 'application/vnd.api+json',
            ],
        ]);
        $this->prefix = Config::get('doi.'.Config::get('doi.default').'.prefix');
    }

    public function getDOIs()
    {
        $prefix = Config::get('doi.'.Config::get('doi.default').'.prefix');
        $response = $this->client->get('/dois?prefix='.$prefix);

        return $response->getBody();
    }

    public function getDOI($doi)
    {
        $response = $this->client->get('/dois/'.urlencode($doi));

        return $response->getBody();
    }

    /**
     * @param  string  $identifier  Legacy: CNPC id or full suffix e.g. coconut.cnpc0070.v2
     */
    public function createDOI($identifier, $metadata = [])
    {
        $suffix = $this->resolveSuffix($identifier);

        return $this->createDoiWithSuffix($suffix, $metadata);
    }

    public function createDoiWithSuffix(string $suffix, array $metadata = []): array
    {
        $doi = $this->prefix.'/'.$suffix;
        $attributes = [
            'doi' => $doi,
            'prefix' => $this->prefix,
            'suffix' => $suffix,
            'publisher' => Config::get('app.name'),
            'publicationYear' => now()->format('Y'),
            'language' => 'en',
        ];

        foreach ($metadata as $key => $value) {
            $attributes[$key] = $value;
        }

        $body = [
            'data' => [
                'type' => 'dois',
                'attributes' => $attributes,
            ],
        ];

        $response = $this->client->post('/dois', [RequestOptions::JSON => $body]);
        $contents = $response->getBody()->getContents();

        return json_decode($contents, true);
    }

    public function updateDOI($doi, $metadata = [])
    {
        $attributes = [];
        foreach ($metadata as $key => $value) {
            $attributes[$key] = $value;
        }

        $body = [
            'data' => [
                'type' => 'dois',
                'attributes' => $attributes,
            ],
        ];

        $response = $this->client->put('/dois/'.urlencode($doi), [RequestOptions::JSON => $body]);
        $contents = $response->getBody()->getContents();

        return json_decode($contents, true);
    }

    public function deleteDOI($doi)
    {
        $response = $this->client->delete('/dois/'.urlencode($doi));

        return $response->getBody();
    }

    public function getDOIActivity($doi)
    {
        $response = $this->client->get('/dois/'.urlencode($doi).'/activities');

        return $response->getBody();
    }

    protected function resolveSuffix(string $identifier): string
    {
        if (str_contains($identifier, '.')) {
            return $identifier;
        }

        return Config::get('app.name').'.'.$identifier;
    }
}
