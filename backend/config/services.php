<?php

return [
    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
        'bucket' => env('AWS_BUCKET', 'khelsutra-storage'),
    ],
];
