<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSwaggerDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-api-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('rest:documentation');
        $publicPaths = [
            '/api/auth/login',
            '/api/auth/register',
        ];
        $this->modifyOpenAPIJsonFile($publicPaths);

    }

    public function modifyOpenAPIJsonFile($filterPaths = [], $filePath = 'vendor/rest/openapi.json')
    {
        $jsonFilePath = public_path($filePath);

        if (! File::exists($jsonFilePath)) {
            abort(404, 'File not found');
        }

        $jsonData = json_decode(File::get($jsonFilePath), true);

        if (isset($jsonData['paths'])) {
            foreach ($jsonData['paths'] as $pathKey => &$path) {
                if (! in_array($pathKey, $filterPaths)) {
                    foreach ($jsonData['paths'][$pathKey] as $operationKey => &$operation) {
                        $operation['security'] = [['sanctum' => []]];
                    }
                }
            }
        }

        if (isset($jsonData['components']['securitySchemes'])) {
            foreach ($jsonData['components']['securitySchemes'] as &$scheme) {
                if (isset($scheme['flows'])) {
                    unset($scheme['flows']);
                }
                if (isset($scheme['openIdConnectUrl'])) {
                    unset($scheme['openIdConnectUrl']);
                }
            }
        }

        $updatedJsonContents = json_encode($jsonData, JSON_PRETTY_PRINT);

        File::put($jsonFilePath, $updatedJsonContents);
    }
}
