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
                    $isDelete = $disk->delete($bot->s3_path . '.zip');
                    if($isDelete) Log::info('Update or Create files s3: Zip file delete from s3!');
                }

                Storage::put($bot->s3_path . '/src/' . $bot->path, $custom_script);
                Storage::put($bot->s3_path . '/src/package.json', $custom_package_json);
                Storage::put($bot->s3_path . '/_metadata.json', $bot);
                Storage::put($bot->s3_path . '.zip', '');

                if (Storage::exists($bot->s3_path . '.zip')) {
                    Log::info('Update or Create files s3: Folder scripts in local created ' . $bot->s3_path . '.zip');
                    // Create zip file with folder in local storage
                    $file_content = ZipHelper::createZip($bot->s3_path);
                    // Create new zip file for storage s3
                    $disk->put($bot->s3_path . '.zip', $file_content);
                }
                Storage::deleteDirectory('scripts');
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }



    /**
     * Creates or updates files by script in S3 storage.
     *
     * @param object $botInstance
     * @param object $disk
     * @param string|null $custom_script
     * @param string|null $custom_package_json
     * @return void
     */
    public static function updateOrCreateFilesS3BotInstance(object $botInstance, object $disk, $custom_script, $custom_package_json)
    {
        try {
            if($botInstance->s3_path !== null) {
                if ($disk->exists($botInstance->s3_path . '.zip')) {
                    $isDelete = $disk->delete($botInstance->s3_path . '.zip');
                    if($isDelete) Log::info('Update or Create files s3: Zip file delete from s3!');
                }

                Storage::put($botInstance->s3_path . '/src/' . $botInstance->path, $custom_script);
                Storage::put($botInstance->s3_path . '/src/package.json', $custom_package_json);
                Storage::put($botInstance->s3_path . '/_metadata.json', $botInstance);
                Storage::put($botInstance->s3_path . '.zip', '');

                if (Storage::exists($botInstance->s3_path . '.zip')) {
                    Log::info('Update or Create files s3: Folder scripts in local created ' . $botInstance->s3_path . '.zip');
                    // Create zip file with folder in local storage
                    $file_content = ZipHelper::createZip($botInstance->s3_path);
                    // Create new zip file for storage s3
                    $disk->put($botInstance->s3_path . '.zip', $file_content);
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
                Storage::deleteDirectory('scripts');
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
}
