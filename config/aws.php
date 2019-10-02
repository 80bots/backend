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
        'key'    => env('AWS_ACCESS_KEY_ID', 'AKIAIO7MFUMEZ33ZDXKA'),
        'secret' => env('AWS_SECRET_ACCESS_KEY', '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'),
    ],
    'region' => env('AWS_REGION', 'us-east-2'),
    'version' => 'latest',
    'bucket' => env('AWS_BUCKET', '80bots'),
    'screenshotsBucket' => env('AWS_SCREENSHOTS_BUCKET', '80bots-issued-screenshots'),
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],
    'image_id' => env('AWS_IMAGE_ID', 'ami-0d36f50797a16e927'),
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
    ]
];
