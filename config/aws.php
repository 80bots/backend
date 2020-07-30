<?php

use Aws\Laravel\AwsServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. This file
    | is published to the application config directory for modification by the
    | user. The full set of possible options are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    */

    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'region' => env('AWS_REGION'),
    'version' => 'latest',
    'bucket' => env('AWS_BUCKET'),
    'instance_cloudfront' => env('AWS_CLOUDFRONT_INSTANCES_HOST'),
    'screenshotsBucket' => env('AWS_SCREENSHOTS_BUCKET'),
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],
    'image_id' => env('AWS_IMAGE_ID'),
    'instance_type' => env('AWS_INSTANCE_TYPE'),
    'volume_size' => env('AWS_VOLUME_SIZE'),
    'instance_metadata' => env('AWS_INSTANCE_METADATA'),
    'instance_ignore' => ['SaaS', 'kabas', 'kabas2'],
    'owners' => ['030500410996'],
    'quota' => [
        'code_t3_medium' => 'L-D54D8763'
    ],
    'services' => [
        'ec2' => [
            'code' => 'ec2'
        ]
    ],
    'ports' => [
        'access_user' => [
            6080,
            6002,
            22
        ],
    ],
    'streamer' => [
        'folder' => 'streamer-data'
    ]
];
