<?php

namespace App\Helpers;

use App\Services\BotParser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class S3BucketHelper
{
    /**
     * Creates or updates files by script in S3 storage.
     *
     * @param object $bot
     * @param object $disk
     * @param string|null $custom_script
     * @param string|null $custom_package_json
     * @return void
     */
    public static function updateOrCreateFilesS3(object $bot, object $disk, $custom_script, $custom_package_json)
    {
        try {
            if($bot->s3_path !== null) {
                if ($disk->exists($bot->s3_path . '.zip')) {
                    $disk->delete($bot->s3_path . '.zip');
                }

                Storage::put($bot->s3_path . '/src/' . $bot->path, $custom_script);
                Storage::put($bot->s3_path . '/src/package.json', $custom_package_json);
                Storage::put($bot->s3_path . '/.env', S3BucketHelper::generateEnvFile());
                Storage::put($bot->s3_path . '/startup.sh', S3BucketHelper::generateShellFile());
                Storage::put($bot->s3_path . '/_metadata.json', $bot);
                Storage::put($bot->s3_path . '.zip', '');

                if (Storage::exists($bot->s3_path . '.zip')) {
                    $file_content = ZipHelper::createZip($bot->s3_path);
                    $disk->put($bot->s3_path . '.zip', $file_content);
                }

                Storage::deleteDirectory('scripts');
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Delete script files in S3 storage.
     *
     * @param string $folder_name
     * @return void
     */
    public static function deleteFilesS3(string $folder_name)
    {
        try {
            if($folder_name !== null) {
                $disk = Storage::disk('s3');
                $disk->delete($folder_name . '.zip');
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Get script and package.json in storage S3.
     *
     * @param string $folder_name
     * @return array
     */
    public static function getFilesS3(string $folder_name)
    {
        try {
            $unZip = ZipHelper::unZip($folder_name);
            if($unZip) {
                $array = [];
                $files = Storage::files($folder_name . '/src');
                foreach ($files as $file) {
                    if (Str::contains($file,'/package.json')) {
                        $array = Arr::add($array, 'custom_package_json', Storage::get($file));
                    } elseif (Str::contains($file,'.custom.js')) {
                        $array = Arr::add($array, 'custom_script', Storage::get($file));
                    }
                }
                Storage::deleteDirectory($folder_name);
                Log::info(print_r($array, true));
                return $array;
            }
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
