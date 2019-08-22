<?php

namespace App\Helpers;

use App\AwsRegion;
use App\Bot;
use App\InstanceSessionsHistory;
use App\SchedulingInstancesDetails;
use App\User;
use App\UserInstance;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

    /**
     * @param EloquentCollection|null $details
     * @return array
     */
    public static function getSchedulingDetails(?EloquentCollection $details): array
    {
        if (empty($details)) {
            return [];
        }

        return $details->map(function ($object) {
            return [
                'id'            => $object->id ?? null,
                'day'           => $object->day ?? '',
                'time'          => $object->selected_time ? (new Carbon($object->selected_time))->format('H:i') : '',
                'cron_data'     => $object->cron_data ?? '',
                'type'          => $object->schedule_type ?? '',
                'status'        => $object->status ?? '',
                'created_at'    => $object->created_at->format('Y-m-d H:m:i') ?? '',
            ];
        })->toArray();
    }

    /**
     * @param Collection $instancesByStatus
     * @param AwsRegion $region
     */
    public static function syncInstances(Collection $instancesByStatus, AwsRegion $region): void
    {
        $awsInstancesIn = $instancesByStatus->collapse()->map(function ($item, $key) {
            return $item['aws_instance_id'];
        })->toArray();

        foreach ($instancesByStatus as $status => $instances) {
            foreach ($instances as $key => $instance) {
                $bot = Bot::where('aws_ami_image_id', $instance['aws_ami_id'])->first();

                if (! empty($bot)) {
                    $instance['bot_id'] = $bot->id;
                }

                if ($status === 'stopped' || $status === 'stopping') {
                    $status = 'stopped';
                }

                $userInstance = UserInstance::where('aws_instance_id' , $instance['aws_instance_id'])->first();

                if (! empty($userInstance)) {

                    $fill = [
                        'status'            => $status,
                        'tag_name'          => $instance['tag_name'] ?? '',
                        'tag_user_email'    => $instance['tag_user_email'] ?? '',
                    ];

                    if($status === 'running') {
                        $fill['is_in_queue'] = 0;
                    }

                    $userInstance->fill($fill);

                    if ($userInstance->save() && $status === 'terminated') {
                        $awsRegion = AwsRegion::find($userInstance->aws_region_id ?? null);
                        if ($awsRegion->created_instances > 0) {
                            $awsRegion->decrement('created_instances');
                        }
                    }

                } else {

                    if ($status !== 'terminated') {

                        Log::info($instance['aws_instance_id'] . ' has not been recorded while launch or manually launched from the aws');

                        $admin = User::onlyAdmins()->first();

                        if (!empty($admin)) {

                            $instance['user_id'] = $admin->id;
                            $instance['status'] = $status;
                            if ($status == 'running') {
                                $instance['is_in_queue'] = 0;
                            }
                            $instance['aws_region_id'] = $region->id ?? null;

                            $userInstance = UserInstance::create($instance);

                        } else {
                            Log::info($instance['aws_instance_id'] . ' cannot be synced');
                        }
                    }
                }
            }
        }

        self::deleteUserInstances($awsInstancesIn);

        Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
    }

    /**
     * @param array $awsInstancesIn
     */
    public static function deleteUserInstances(array $awsInstancesIn): void
    {
        UserInstance::where(function($query) use($awsInstancesIn) {
            $query->whereNotIn('aws_instance_id', $awsInstancesIn)
                ->orWhere('aws_instance_id', null)
                ->orWhere('status', 'terminated');
        })->whereNotIn('status', ['running', 'stopped'])
            ->delete();

        UserInstance::where(function($query) {
            $query->where('is_in_queue', 1)
                ->orWhereIn('status', ['running', 'stopped']);
        })->where('updated_at', '<' , Carbon::now()->subMinutes(10)->toDateTimeString())
            ->delete();
    }
}
