<?php

namespace App\Helpers;

use Carbon\Carbon;
use phpDocumentor\Reflection\Types\Object_;
use function Aws\map;

class CommonHelper
{
    public static function convertTimeZone($time, $timezone, $format='Y-m-d H:i:s')
    {
        $time = Carbon::parse($time)->format($format);
        return Carbon::createFromFormat($format, $time, 'UTC')->setTimezone($timezone);
    }

    /**
     *
     *
     * @param $startTime
     * @param $endTime
     * @return int
     */
    public static function diffTimeInMinutes($startTime, $endTime)
    {
        $start  = Carbon::parse($startTime);
        $end    = Carbon::parse($endTime);
        return $end->diffInMinutes($start);
    }

    public static function diffTimeInHours($startTime, $endTime)
    {
        $start  = Carbon::parse($startTime);
        $end    = Carbon::parse($endTime);
        return $end->diffInHours($start);
    }

    /**
     * @param string $string
     * @return array
     */
    public static function explodeByComma(string $string): array
    {
        return collect(explode(',', rtrim($string,',')))
            ->map(function ($item, $key) {
                return trim($item);
            })
            ->toArray();
    }

    /**
     * @param $meta
     * @return array
     */
    public static function getPaginateInfo($meta = null): array
    {
        return [
            'page'  => $meta->current_page ?? 1,
            'total' => $meta->total ?? 0,
        ];
    }

    public static function slugify(string $text): string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}
