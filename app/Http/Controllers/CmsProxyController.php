<?php

namespace App\Http\Controllers;

use App\Services\CmsClient;
use Illuminate\Http\Request;

class CmsProxyController extends Controller
{
    private CmsClient $cmsClient;

    public function __construct(CmsClient $cmsClient)
    {
        $this->cmsClient = $cmsClient;
    }

    /**
     * Generic proxy for CMS API requests
     * CSRF protection is automatically applied by Laravel's web middleware
     *
     * @return \Illuminate\Http\Response
     */
    public function proxy(Request $request)
    {
        $endpoint = $request->input('endpoint');
        $params = $request->except(['endpoint', '_token']);
        $method = $request->method();

        $response = match ($method) {
            'GET' => $this->cmsClient->get($endpoint, $params),
            'POST' => $this->cmsClient->post($endpoint, $params),
            default => abort(405, 'Method not allowed'),
        };

        return response($response->body())
            ->header('Content-Type', $response->header('Content-Type'))
            ->header('Cache-Control', 'public, max-age=86400')
            ->setStatusCode($response->status());
    }

    /**
     * Proxy for 2D depiction - used by img tags
     * No CSRF protection (called directly by browsers via img src)
     * Rate limited via route middleware to prevent abuse
     *
     * @return \Illuminate\Http\Response
     */
    public function depict2D(Request $request)
    {
        $params = $request->only(['smiles', 'height', 'width', 'toolkit', 'CIP']);

        $response = $this->cmsClient->get('depict/2D', $params);

        return response($response->body())
            ->header('Content-Type', $response->header('Content-Type') ?? 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
