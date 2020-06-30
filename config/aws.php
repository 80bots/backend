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
    /**
     * IAM User 80bots ( Access key ID - AKIAQOGPXKZ2M7RQB6GR / Secret key - 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 )
     */
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID', 'AKIAQOGPXKZ2MCMXDOUU'),
        'secret' => env('AWS_SECRET_ACCESS_KEY', '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'),
    ],
    'region' => env('AWS_REGION', 'us-east-2'),
    'version' => 'latest',
    'bucket' => env('AWS_BUCKET', '80bots'),
    'instance_cloudfront' => env('AWS_CLOUDFRONT_INSTANCES_HOST'),
    'screenshotsBucket' => env('AWS_SCREENSHOTS_BUCKET', '80bots-issued-screenshots'),
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],
    'image_id' => env('AWS_IMAGE_ID', 'ami-0f7ac8e6b58f47535'),
    'instance_type' => env('AWS_INSTANCE_TYPE', 't3.medium'),
    'volume_size' => env('AWS_VOLUME_SIZE', '32'),
    'instance_metadata' => env('AWS_INSTANCE_METADATA', 'http://169.254.169.254/latest/meta-data/'),
    'instance_ignore' =>  ['SaaS', 'kabas', 'kabas2'],
    'owners' => ['030500410996'],
    'quota' => [
        'code_t3_medium' => 'L-D54D8763'
    ],
    'services' => [
        'ec2' => [
            'code' => 'ec2'
        ]
    ],
    'iam' => [
        'user'  => 'saas-s3',
        'group' => 'saas-s3',
        'access_key' => env('AWS_IAM_S3_ACCESS_KEY', 'AKIAQOGPXKZ2F3RBT65W'),
        'secret_key' => env('AWS_IAM_S3_SECRET_KEY', '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'),
    ],
    'ports' => [
        'access_user' => [
            6002,
            22
        ]
    ],
    'streamer' => [
        'folder' => 'streamer-data'
    ]
];
