<?php

namespace App\Helpers;

use App\AwsRegion;
use App\Bot;
use App\BotInstance;
use App\BotInstancesDetails;
use App\DeleteSecurityGroup;
use App\InstanceSessionsHistory;
use App\SchedulingInstancesDetails;
use App\Services\Aws;
use App\User;
use Aws\Result;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\Builder;
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
        $currentDate = Carbon::now()->toDateTimeString();

        foreach ($instancesByStatus as $statusKey => $instances) {

            foreach ($instances as $key => $instance) {

                $user   = User::where('email', '=', $instance['tag_user_email'])->first();
                $bot    = Bot::where('name', '=', $instance['tag_bot_name'])->first();

                if (! empty($user)) {

                    $status = $statusKey === 'stopping' ? BotInstance::STATUS_STOPPED : $statusKey;

                    $instanceId = $instance['aws_instance_id'] ?? null;

                    $botInstance = $user->instances()->where('aws_instance_id', '=', $instanceId)->first();

                    if (! empty($botInstance)) {

                        switch ($status) {
                            case BotInstance::STATUS_RUNNING:
                            case BotInstance::STATUS_STOPPED:
                            case BotInstance::STATUS_TERMINATED:

                                $botInstance->update(['aws_status' => $status]);

                                if ($status === BotInstance::STATUS_TERMINATED) {

                                    if ($botInstance->region->created_instances > 0) {
                                        $botInstance->region->decrement('created_instances');
                                    }

                                    $detail = $botInstance->details()->latest()->first();

                                    // TODO: Check whether old status was 'running'
                                    self::updateUpTime($botInstance, $detail, $currentDate);

                                    $botInstance->delete();
                                }

                                break;
                        }
                    } else {

                        if ($status !== BotInstance::STATUS_TERMINATED) {

                            $aws                = new Aws;
                            $describeVolumes    = $aws->describeVolumes($region->code ?? null, $instance['aws_volumes_params']);

                            if ($describeVolumes->hasKey('Volumes')) {

                                $volumes = collect($describeVolumes->get('Volumes'));

                                if ($volumes->isNotEmpty()) {

                                    $volumeSize = $volumes->filter(function ($value, $key) {
                                        return $value['Attachments'][0]['Device'] === '/dev/sda1';
                                    })->map(function ($item, $key) {
                                        return $item['Size'] ?? 0;
                                    })->first();

                                    if ($volumeSize > 0) {

                                        $newInstance = BotInstance::create([
                                            'user_id'           => $user->id,
                                            'bot_id'            => $bot->id ?? null,
                                            'tag_name'          => $instance['tag_name'],
                                            'tag_user_email'    => $instance['tag_user_email'],
                                            'aws_instance_id'   => $instance['aws_instance_id'],
                                            'aws_public_ip'     => $instance['aws_public_ip'],
                                            'aws_region_id'     => $region->id ?? null,
                                            'aws_status'        => $status,
                                            'start_time'        => $instance['created_at']
                                        ]);

                                        $newInstance->details()->create([
                                            'aws_instance_type'         => $instance['aws_instance_type'],
                                            'aws_storage_gb'            => $volumeSize,
                                            'aws_image_id'              => $instance['aws_image_id'],
                                            'aws_security_group_id'     => $instance['aws_security_group_id'],
                                            'aws_security_group_name'   => $instance['aws_security_group_name'],
                                            'aws_public_dns'            => $instance['aws_public_dns'],
                                            'aws_pem_file_path'         => "keys/{$instance['aws_key_name']}.pem",
                                            'is_in_queue'               => 0,
                                            'start_time'                => $instance['created_at']
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                unset($user, $botInstance, $describeVolumes, $volumes, $newInstance);
            }
        }

        unset($instancesByStatus);

        Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
    }

    private static function updateUpTime(BotInstance $instance, BotInstancesDetails $detail, string $currentDate): void
    {
        $diffTime = CommonHelper::diffTimeInMinutes($detail->start_time, $currentDate);

        $detail->update([
            'end_time'      => $currentDate,
            'total_time'    => $diffTime
        ]);

        $upTime = $diffTime + $instance->total_up_time;

        $instance->update([
            'cron_up_time'  => 0,
            'total_up_time' => $upTime,
            'up_time'       => $upTime,
            'used_credit'   => CommonHelper::calculateUsedCredit($upTime)
        ]);
    }

    /**
     * Clean up unused keys and security groups
     * @param Aws $aws
     * @param $details
     */
    public static function cleanUpTerminatedInstanceData(Aws $aws, $details): void
    {
        //
        if(preg_match('/^keys\/(.*)\.pem$/s', $details->aws_pem_file_path ?? '', $matches)) {
            $aws->deleteKeyPair($matches[1]);
            $aws->deleteS3KeyPair($details->aws_pem_file_path ?? '');
        }
        DeleteSecurityGroup::create([
            'group_id'      => $details->aws_security_group_id ?? '',
            'group_name'    => $details->aws_security_group_name ?? '',
        ]);
    }

    /**
     * @param Result $describeInstancesResponse
     * @return bool
     */
    public static function checkTerminatedStatus(Result $describeInstancesResponse): bool
    {
        $reservationObj = $describeInstancesResponse->get('Reservations');
        return empty($reservationObj) || $reservationObj[0]['Instances'][0]['State']['Name'] === 'terminated';
    }
}
