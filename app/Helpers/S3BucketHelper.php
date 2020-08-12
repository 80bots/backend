<?php

namespace App\Helpers;

use App\Services\BotParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;;

class S3BucketHelper
{
    /**
     * Store a custom-bot in storage S3.
     *
     * @param $bot
     * @param $custom_script
     * @param $custom_package_json
     * @return void
     */
    public static function putFilesS3($bot, $custom_script, $custom_package_json)
    {
        try {
            if($bot->s3_path !== null) {
                Storage::put($bot->s3_path . '/src/' . $bot->path, $custom_script);
                Storage::put($bot->s3_path . '/src/package.json', $custom_package_json);
                Storage::put($bot->s3_path . '/.env', S3BucketHelper::generateEnvFile());
                Storage::put($bot->s3_path . '/startup.sh', S3BucketHelper::generateShellFile());
                Storage::put($bot->s3_path . '/_metadata.json', $bot);

//                $disk = Storage::disk('s3');
//                $disk->put($bot->s3_path . '/' . $bot->path, $custom_script);
//                $disk->put($bot->s3_path . '/package.json', $custom_package_json);
//                $disk->put($bot->s3_path . '/_metadata.json', $bot);
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Store a custom-bot in storage S3.
     *
     * @param $bot
     * @param $custom_script
     * @param $custom_package_json
     * @return void
     */
    public static function updateFilesS3($bot, $custom_script, $custom_package_json)
    {
        try {
            if($bot->s3_path !== null) {
                $disk = Storage::disk('s3');
                $disk->deleteDirectory($bot->s3_path);
                $disk->put($bot->s3_path . '/' . $bot->path, $custom_script);
                $disk->put($bot->s3_path . '/package.json', $custom_package_json);
                $disk->put($bot->s3_path . '/_metadata.json', $bot);
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Store a custom-bot in storage S3.
     *
     * @param $folder_name
     * @return void
     */
    public static function deleteFilesS3($folder_name)
    {
        try {
            if($folder_name !== null) {
                $disk = Storage::disk('s3');
                $disk->deleteDirectory($folder_name);
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Store a custom-bot in storage S3.
     *
     * @param $folder_name
     * @return array
     */
    public static function getFilesS3($folder_name)
    {
        try {
            $disk = Storage::disk('s3');
            $array = [];
            $files = $disk->files($folder_name);
            foreach ($files as $file) {
                if (Str::contains($file,'/package.json')) {
                    $array = Arr::add($array, 'custom_package_json', $disk->get($file));
                } elseif (Str::contains($file,'.custom.js')) {
                    $array = Arr::add($array, 'custom_script', $disk->get($file));
                }
            }
            return $array;
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * @param string $script
     * @return false|string|null
     */
    public static function extractParamsFromScript(string $script)
    {
        try {
            $result = BotParser::getBotInfo($script);
            $i = 0;
            foreach($result['params'] as $key => $val) {
                $val->order = $i;
                $result['params']->$key = $val;
                $i++;
            }
            return $result && $result['params'] ? json_encode($result['params']) : null;
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * @return string
     */
    public static function generateEnvFile()
    {
        try {
            $API_HOST                       = config('bot_instance.api_url');
            $SOCKET_HOST                    = config('bot_instance.socket_url');
            $AWS_ACCESS_KEY_ID              = config('aws.credentials.key');
            $AWS_SECRET_ACCESS_KEY          = config('aws.credentials.secret');
            $AWS_BUCKET                     = config('aws.bucket');
            $AWS_CLOUDFRONT_INSTANCES_HOST  = str_ireplace('https://', '', config('aws.instance_cloudfront'));
            $AWS_REGION                     = config('aws.region');
            return "SOCKET_SERVER_HOST={$SOCKET_HOST}
API_URL={$API_HOST}
AWS_ACCESS_KEY_ID={$AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY={$AWS_SECRET_ACCESS_KEY}
AWS_BUCKET={$AWS_BUCKET}
AWS_CLOUDFRONT_INSTANCES_HOST={$AWS_CLOUDFRONT_INSTANCES_HOST}
AWS_REGION={$AWS_REGION}";
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * @return string
     */
    public static function generateShellFile()
    {
        try {
            return "#!bin/bash";
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }
}
