<?php

namespace App\Helper;

use Carbon\Carbon;

class CommonHelper
{
    public static function convertTimeZone($time, $timezone, $format='Y-m-d H:i:s')
    {
        return Carbon::createFromFormat($format, $time, 'UTC')->setTimezone($timezone);
    }
}
