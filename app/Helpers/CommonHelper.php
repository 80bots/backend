<?php

namespace App\Helpers;

use Carbon\Carbon;
use function Aws\map;

class CommonHelper
{
    public static function convertTimeZone($time, $timezone, $format='Y-m-d H:i:s')
    {
        $time = Carbon::parse($time);
        return Carbon::createFromFormat($format, $time, 'UTC')->setTimezone($timezone);
    }

    /**
     * @return float
     */
    public static function calculateCredit()
    {
        return round((config('app.credit') * 10) / config('app.up_time'),2);
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

    /**
     * @param $upTime
     * @return float|int
     */
    public static function calculateUsedCredit($upTime)
    {
        return $upTime > 0 ? round($upTime * (float)config('app.credit') / (float)config('app.up_time'), 2) : 0;
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
}
