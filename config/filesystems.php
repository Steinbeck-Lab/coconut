<?php

$s3BaseUrl = rtrim(env('AWS_URL', 'https://s3.uni-jena.de'), '/');
$s3Bucket = env('AWS_BUCKET', 'coconut');
$s3UsePathStyle = filter_var(env('AWS_USE_PATH_STYLE_ENDPOINT', true), FILTER_VALIDATE_BOOLEAN);
$s3PublicUrl = $s3UsePathStyle ? $s3BaseUrl.'/'.$s3Bucket : $s3BaseUrl;
$s3DownloadsUrl = rtrim(env('AWS_DOWNLOADS_URL', 'https://coconut.s3.uni-jena.de/prod/downloads'), '/');
$s3Endpoint = env('AWS_ENDPOINT', 'https://s3.uni-jena.de');

$s3Disk = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => $s3Bucket,
    'url' => $s3PublicUrl,
    'downloads_url' => $s3DownloadsUrl,
    'endpoint' => $s3Endpoint,
    'use_path_style_endpoint' => $s3UsePathStyle,
    'throw' => false,
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => config('app.url').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => $s3Disk,

        'ceph' => $s3Disk,

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
