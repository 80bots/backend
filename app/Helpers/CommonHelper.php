<?php

namespace App\Helpers;

use Carbon\Carbon;

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
        $upTime = config('app.up_time');
        $remaining_credits = config('app.credit');
        return round(($remaining_credits * 10) / $upTime,2);
    }

    /**
     *
     *
     * @param $startTime
     * @param $endTime
     * @return int
     */
    public static function diffTime($startTime, $endTime)
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
}
