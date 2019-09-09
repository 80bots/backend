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

    public static function diffTimeInHours($startTime, $endTime)
    {
        $start  = Carbon::parse($startTime);
        $end    = Carbon::parse($endTime);
        return $end->diffInHours($start);
    }

    /**
     * @param $upTime
     * @return int
     */
    public static function calculateUsedCredit($upTime): int
    {
        if ($upTime >= 0 && $upTime <= 60) {
            return 1;
        } elseif ($upTime > 60) {

            $now  = Carbon::now();
            $realHours = $now->diffInRealHours($now->copy()->addMinutes($upTime));
            $floatHours = $now->floatDiffInHours($now->copy()->addMinutes($upTime));

            if ($floatHours > $realHours) {
                return ($realHours+1) * intval(config('app.credit'));
            } else {
                return $realHours * intval(config('app.credit'));
            }

        } else {
            return 0;
        }
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
}
