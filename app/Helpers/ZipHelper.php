<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class ZipHelper
{

    /**
     * Creating a zip archive.
     *
     * @param string $s3_path
     * @return bool|string
     */
    public static function createZip(string $s3_path)
    {
        try {
            $zip_file = storage_path('app/' . $s3_path . '.zip');

            $zip = new ZipArchive();
            $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $path       = storage_path('app/' . $s3_path);
            $directory  = new RecursiveDirectoryIterator($path);
            $files      = new RecursiveIteratorIterator($directory);

            foreach ($files as $name => $file)
            {
                if (!$file->isDir()) {
                    $filePath     = $file->getRealPath();
                    $relativePath = str_ireplace($path, '', $filePath);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            return Storage::get($s3_path . '.zip');
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }

    /**
     * Unarchived zip file.
     *
     * @param string $s3_path
     * @return bool
     */
    public static function unZip(string $s3_path)
    {
        try {
            $disk   = Storage::disk('s3');
            $files  = $disk->get($s3_path . '.zip');

            Storage::put($s3_path . '.zip', $files);
            $zip_file   = storage_path('app/' . $s3_path . '.zip');
            $zip        = new ZipArchive;
            $res        = $zip->open($zip_file);
            if ($res === TRUE) {
                $path = storage_path('app/' . $s3_path);
                $zip->extractTo($path);
                $zip->close();
                Log::info('Unzip!');
                Storage::delete($s3_path . '.zip');
                return true;
            } else {
                return false;
            }
        } catch (Throwable $throwable) {
            Log::error("Throwable: {$throwable->getMessage()}");
        }
    }
}
