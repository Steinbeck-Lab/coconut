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
            '/api/v1/auth/login',
            '/api/v1/auth/register',
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
                if (! in_array($path, $filterPaths)) {
                    foreach ($jsonData['paths'][$pathKey] as $operationKey => &$operation) {
                        $operation['security'] = [['sanctum' => []]];
                    }
                }
            }
        }

        $jsonData['components']['securitySchemes'] = [
            'sanctum' => [
                'type' => 'apiKey',
                'scheme' => 'Bearer',
                'description' => 'Enter token in format (Bearer \<token\>)',
                'name' => 'Authorization',
                'in' => 'header',
            ],
        ];

        $updatedJsonContents = json_encode($jsonData, JSON_PRETTY_PRINT);

        File::put($jsonFilePath, $updatedJsonContents);
    }
}
