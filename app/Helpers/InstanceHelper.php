<?php


namespace App\Helpers;


use App\SchedulingInstancesDetails;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class InstanceHelper
{
    /**
     * @param SchedulingInstancesDetails $detail
     * @param int $currentTime
     * @return bool
     */
    public static function isScheduleInstance(SchedulingInstancesDetails $detail, int $currentTime)
    {
        $tz = CarbonTimeZone::create($detail->time_zone);
        $ct = Carbon::createFromFormat('D h:i A', "{$detail->day} {$detail->selected_time}", $tz);
        return $currentTime === $ct->getTimestamp();
    }
}
