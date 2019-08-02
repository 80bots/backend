<?php

namespace App\Helpers;

use App\InstanceSessionsHistory;
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

    /**
     * @param $schedulers
     * @param $now
     * @return array
     */
    public static function getScheduleInstancesIds($schedulers, $now): array
    {
        $instancesIds = [];

        foreach ($schedulers as $scheduler) {

            $userInstance = $scheduler->userInstances ?? null;

            if (! empty($userInstance) && ! empty($scheduler->details)) {

                foreach ($scheduler->details as $detail) {

                    $currentTime = Carbon::parse($now->format('D h:i A'))
                        ->setTimezone($detail->time_zone)
                        ->getTimestamp();

                    if (self::isScheduleInstance($detail, $currentTime)) {

                        $tz = CarbonTimeZone::create($detail->time_zone);

                        //Save the session history
                        InstanceSessionsHistory::create([
                            'scheduling_instances_id'   => $scheduler->id,
                            'user_id'                   => $scheduler->user_id,
                            'schedule_type'             => $detail->schedule_type,
                            'cron_data'                 => $detail->cron_data,
                            'current_time_zone'         => $tz->toRegionName(),
                            'selected_time'             => $detail->selected_time,
                        ]);

                        if (! empty($userInstance->aws_instance_id)) {
                            array_push($instancesIds, $userInstance->aws_instance_id);
                        }
                    }
                }
            }
        }

        return $instancesIds;
    }
}
