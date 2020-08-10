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
     * Store a custom-bot in storage S3.
     *
     * @param $folder_name
     * @param $path
     * @param $custom_script
     * @param $custom_package_json
     * @return void
     */
    public static function putFilesS3($folder_name, $path, $custom_script, $custom_package_json )
    {
        try {
            if($folder_name !== null) {
                $disk = Storage::disk('s3');
                $disk->put($folder_name . '/' . $path, $custom_script);
                $disk->put($folder_name . '/package.json', $custom_package_json);
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Store a custom-bot in storage S3.
     *
     * @param $folder_name
     * @param $path
     * @param $custom_script
     * @param $custom_package_json
     * @return void
     */
    public static function updateFilesS3($folder_name, $path, $custom_script, $custom_package_json )
    {
        try {
            if($folder_name !== null) {
                $disk = Storage::disk('s3');
                $disk->deleteDirectory($folder_name);
                $disk->put($folder_name . '/' . $path, $custom_script);
                $disk->put($folder_name . '/package.json', $custom_package_json);
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
                if(Str::contains($file,'/package.json')) {
                    $array = Arr::add($array, 'custom_package_json', $disk->get($file));
                } else {
                    $array = Arr::add($array, 'custom_script', $disk->get($file));
                }
            }
            Log::info(print_r($array, true));
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
        $result = BotParser::getBotInfo($script);
        $i = 0;
        foreach($result['params'] as $key => $val) {
            $val->order = $i;
            $result['params']->$key = $val;
            $i++;
        }
        return $result && $result['params'] ? json_encode($result['params']) : null;
    }
}
