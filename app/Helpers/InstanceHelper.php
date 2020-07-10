<?php

namespace App\Helpers;

use App\AwsRegion;
use App\Bot;
use App\BotInstance;
use App\BotInstancesDetails;
use App\DeleteSecurityGroup;
use App\InstanceSessionsHistory;
use App\Jobs\InstanceChangeStatus;
use App\S3Object;
use App\SchedulingInstancesDetails;
use App\Services\Aws;
use App\User;
use Aws\Result;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Nubs\RandomNameGenerator\All as AllRandomName;
use Nubs\RandomNameGenerator\Alliteration as AlliterationName;
use Nubs\RandomNameGenerator\Vgng as VideoGameName;
use Throwable;

class InstanceHelper
{
    const DATA_STREAMER_FOLDER = "streamer-data";

    /**
     * @param SchedulingInstancesDetails $detail
     * @param int $currentTime
     * @return bool
     */
    public static function isScheduleInstance(SchedulingInstancesDetails $detail, int $currentTime): bool
    {
        try {
            $ct = Carbon::createFromFormat('Y-m-d H:i', "{$detail->schedule_time}");
            Log::info(print_r($currentTime, true));
            Log::info(print_r($ct->getTimestamp(), true));

            return $currentTime === $ct->getTimestamp();
        } catch (Throwable $throwable) {
            Log::error("Throwable isScheduleInstance: {$throwable->getMessage()}");
            return false;
        }
    }

    /**
     * @param $schedulers
     * @param $now
     * @return array
     */
    public static function getScheduleInstancesIds($schedulers, $now): array
    {
        $instancesIds = [];
        $insertHistory = [];

        foreach ($schedulers as $scheduler) {

            if (!empty($scheduler->details)) {

                foreach ($scheduler->details as $detail) {

                    $currentTime = Carbon::parse($now->format('D h:i A'))
                        ->setTimezone($detail->time_zone)
                        ->getTimestamp();

                    if (self::isScheduleInstance($detail, $currentTime)) {

                        if (!empty($scheduler->instance->aws_instance_id)) {

                            $ct = Carbon::createFromFormat('Y-m-d H:i', "{$detail->schedule_time}");

                            array_push($insertHistory, [
                                'scheduling_instances_id' => $scheduler->id,
                                'user_id' => $scheduler->user_id,
                                'schedule_type' => $detail->status,
                                'cron_data' => $detail->cron_data,
                                'current_time_zone' => $detail->time_zone,
                                'selected_time' => $ct,
                            ]);

                            array_push($instancesIds, [
                                'instances_id' => $scheduler->instance_id,
                                'user_id' => $scheduler->user_id,
                                'status' => $detail->status,
                            ]);
                        }
                    }
                }
            }
        }

        if (!empty($insertHistory)) {
            //Save the session history
            InstanceSessionsHistory::insert($insertHistory);
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
                'id' => $object->id ?? null,
                'platform_time' => $object->platform_time ?? '',
                'schedule_time' => $object->schedule_time ?? '',
                'timezone' => $object->time_zone ?? '',
                'cron_data' => $object->cron_data ?? '',
                'type' => $object->status ?? '',
                'status' => $object->status ?? '',
                'created_at' => $object->created_at->format('Y-m-d H:m:i') ?? '',
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

        $availableStatuses = [
            BotInstance::STATUS_RUNNING,
            BotInstance::STATUS_STOPPED,
            BotInstance::STATUS_TERMINATED
        ];

        foreach ($instancesByStatus as $statusKey => $instances) {

            if (in_array($statusKey, $availableStatuses)) {

                foreach ($instances as $key => $instance) {

                    $user = User::where('email', '=', $instance['tag_user_email'])->first();

                    if (!empty($user)) {

                        $status = $statusKey === 'stopping' ? BotInstance::STATUS_STOPPED : $statusKey;

                        $instanceId = $instance['aws_instance_id'] ?? null;

                        $botInstance = $user->instances()
                            ->where('aws_instance_id', '=', $instanceId)
                            ->orWhere('tag_name', '=', $instance['tag_name'])
                            ->first();

                        if (!empty($botInstance)) {
                            self::syncInstancesUpdateStatus($botInstance, $status, $instance, $currentDate);
                        } else {

                            if ($status !== BotInstance::STATUS_TERMINATED) {
                                self::syncInstancesCreateBotInstance($region, $user, $instance, $status);
                            }
                        }
                    }

                    unset($user, $botInstance, $describeVolumes, $volumes, $newInstance);
                }
            }
        }

        Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
    }

    /**
     * @param BotInstance $botInstance
     * @param string $status
     * @param array $instance
     * @param string $currentDate
     * @return void
     */
    private static function syncInstancesUpdateStatus(BotInstance $botInstance, string $status, array $instance, string $currentDate): void
    {
        $oldDetail = $botInstance->details()->latest()->first();

        if ($botInstance->aws_status === BotInstance::STATUS_STOPPED && $status === BotInstance::STATUS_RUNNING) {

            $detail = $oldDetail->replicate([
                'end_time', 'total_time'
            ]);

            $detail->fill([
                'start_time' => $instance['aws_launch_time'],
                'aws_public_dns' => $instance['aws_public_ip'],
                'aws_instance_type' => $instance['aws_instance_type'],
                'aws_image_id' => $instance['aws_image_id'],
                'aws_security_group_id' => $instance['aws_security_group_id'],
                'aws_security_group_name' => $instance['aws_security_group_name'],
            ]);

            $detail->save();
        } else {
            $detail = $oldDetail;
        }

        $botInstance->update([
            'aws_public_ip' => $instance['aws_public_ip'],
            'aws_status' => $status
        ]);

        $detail->update([
            'aws_instance_type' => $instance['aws_instance_type'],
            'aws_image_id' => $instance['aws_image_id'],
            'aws_security_group_id' => $instance['aws_security_group_id'],
            'aws_security_group_name' => $instance['aws_security_group_name'],
            'aws_public_dns' => $instance['aws_public_dns'],
        ]);

        if ($status === BotInstance::STATUS_TERMINATED) {

            if ($botInstance->region->created_instances > 0) {
                $botInstance->region->decrement('created_instances');
            }

            self::updateUpTime($botInstance, $detail, $currentDate);
        }

        unset($oldDetail, $detail);
    }

    /**
     * @param AwsRegion $region
     * @param User $user
     * @param array $instance
     * @param string $status
     * @return void
     */
    private static function syncInstancesCreateBotInstance(AwsRegion $region, User $user, array $instance, string $status)
    {
        $aws = new Aws;
        $describeVolumes = $aws->describeVolumes($region->code ?? null, $instance['aws_volumes_params']);

        $bot = Bot::where('name', '=', $instance['tag_bot_name'])->first();

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
                        'user_id' => $user->id,
                        'bot_id' => $bot->id ?? null,
                        'tag_name' => $instance['tag_name'],
                        'tag_user_email' => $instance['tag_user_email'],
                        'aws_instance_id' => $instance['aws_instance_id'],
                        'aws_public_ip' => $instance['aws_public_ip'],
                        'aws_region_id' => $region->id ?? null,
                        'aws_status' => $status,
                        'start_time' => $instance['created_at']
                    ]);

                    $newInstance->details()->create([
                        'aws_instance_type' => $instance['aws_instance_type'],
                        'aws_storage_gb' => $volumeSize,
                        'aws_image_id' => $instance['aws_image_id'],
                        'aws_security_group_id' => $instance['aws_security_group_id'],
                        'aws_security_group_name' => $instance['aws_security_group_name'],
                        'aws_public_dns' => $instance['aws_public_dns'],
                        'aws_pem_file_path' => "keys/{$instance['aws_key_name']}.pem",
                        'is_in_queue' => 0,
                        'start_time' => $instance['created_at']
                    ]);

                    $newInstance->region->increment('created_instances');
                }
            }
        }
    }

    /**
     * @param BotInstance $instance
     * @param BotInstancesDetails $detail
     * @param string $currentDate
     */
    private static function updateUpTime(BotInstance $instance, BotInstancesDetails $detail, string $currentDate): void
    {
        $diffTime = CommonHelper::diffTimeInMinutes($detail->start_time, $currentDate);

        $detail->update([
            'end_time' => $currentDate,
            'total_time' => $diffTime
        ]);

        $upTime = $diffTime + $instance->total_up_time;

        $instance->update([
            'cron_up_time' => 0,
            'total_up_time' => $upTime,
            'up_time' => $upTime,
        ]);
    }

    /**
     * Clean up unused keys and security groups
     * @param Aws $aws
     * @param $details
     */
    public static function cleanUpTerminatedInstanceData(Aws $aws, $details): void
    {
        if (preg_match('/^keys\/(.*)\.pem$/s', $details->aws_pem_file_path ?? '', $matches)) {
            $aws->deleteKeyPair($matches[1]);
            $aws->deleteS3KeyPair($details->aws_pem_file_path ?? '');
        }
        DeleteSecurityGroup::create([
            'group_id' => $details->aws_security_group_id ?? '',
            'group_name' => $details->aws_security_group_name ?? '',
        ]);
    }

    /**
     * @param Result $describeInstancesResponse
     * @return bool
     */
    public static function checkTerminatedStatus(Result $describeInstancesResponse): bool
    {
        $reservationObj = $describeInstancesResponse->get('Reservations');

        if (empty($reservationObj) || empty($reservationObj[0])) {
            return false;
        }

        $state = $reservationObj[0]['Instances'][0]['State']['Name'];

        return $state === 'terminated';
    }

    public static function getObjectByPath($instanceId, string $path): S3Object
    {
        $path = trim($path, '/');
        $pathInfo = pathinfo($path);
        $parentPath = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $entity = !empty($pathInfo['extension']) ? S3Object::ENTITY_FILE : S3Object::ENTITY_FOLDER;
        $type = self::getTypeS3ObjectByExtension($pathInfo['extension'] ?? null, $path);

        if ($parentPath === '.') {
            $object = S3Object::firstOrCreate([
                'instance_id' => $instanceId,
                'path' => $path,
                'name' => $filename,
                'entity' => $entity,
                'type' => $type
            ]);
        } else {
            $object = S3Object::wherePath($path)
                ->whereInstanceId($instanceId)
                ->whereEntity($entity)
                ->first();

            if (!$object) {
                $parent = self::getObjectByPath($instanceId, $parentPath);
                $object = $parent->children()->create([
                    'instance_id' => $instanceId,
                    'path' => $path,
                    'name' => $filename,
                    'entity' => $entity,
                    'type' => $type
                ]);
            }
        }
        return $object;
    }

    private static function getTypeS3ObjectByExtension(?string $extension, string $path): string
    {
        switch ($extension) {
            case 'json':
                return S3Object::TYPE_JSON;
            case 'jpeg':
            case 'jpg':
            case 'png':
                if (strpos($path, 'screenshots') !== false) {
                    return S3Object::TYPE_SCREENSHOTS;
                } else {
                    return S3Object::TYPE_IMAGES;
                }
            case 'log':
                return S3Object::TYPE_LOGS;
            default:
                return S3Object::TYPE_ENTITY;
        }
    }

    /**
     * @param Aws $aws
     * @param string|null $ip
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function createAwsKeyAndGroup(Aws $aws, ?string $ip): ?array
    {
        $tagName = self::createTagName();
        $keyPair = $aws->createKeyPair(config('aws.bucket'));
        $securityGroup = $aws->createSecretGroup($ip);

        if (empty($keyPair) || empty($tagName) || empty($securityGroup)) {
            return null;
        }

        return [
            'tagName' => $tagName,
            'keyPairName' => $keyPair['keyName'],
            'keyPairPath' => $keyPair['path'],
            'groupId' => $securityGroup['securityGroupId'],
            'groupName' => $securityGroup['securityGroupName'],
        ];
    }

    /**
     * The random string with number
     * @return string
     */
    public static function createTagName(): string
    {
        $generator = new AllRandomName([
            new AlliterationName(),
            new VideoGameName()
        ]);

        return strtolower(preg_replace('/[^a-z\d]/ui', '', $generator->getName())) . rand(100, 999);
    }

    /**
     * @param string|null $id
     * @param bool $withTrashed
     * @return BotInstance|null
     */
    public static function getInstanceWithCheckUser(?string $id, $withTrashed = false): ?BotInstance
    {
        /** @var BotInstance $query */

        $query = BotInstance::where('id', '=', $id)
            ->orWhere('aws_instance_id', '=', $id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        Log::info('Auth::user() = ' . Auth::user());
        Log::info('Auth::id() = ' . Auth::id());

        if (!is_null(Auth::user())) {
            $query->where('user_id', '=', Auth::id());
        }

        return $query->first();
    }

    /**
     * @param $status
     * @param $id
     * @param $user_id
     * @return bool
     */
    public static function changeInstanceStatus($status, $id, $user_id): bool
    {
        $instance = self::getInstanceWithCheckUser($id);

        if (empty($instance)) {
            return false;
        }

        $instanceDetail = $instance->details()->latest()->first();

        if (empty($instanceDetail)) {
            return false;
        }

        if (empty($instance->aws_region_id)) {
            return false;
        }

        $user = User::find($user_id);
        $aws = new Aws;

        $instance->clearPublicIp();

        try {

            $describeInstancesResponse = $aws->describeInstances(
                [$instance->aws_instance_id ?? null],
                $instance->region->code
            );

            if (!$describeInstancesResponse->hasKey('Reservations') || self::checkTerminatedStatus($describeInstancesResponse)) {
                $instance->setAwsStatusTerminated();

                if ($instance->region->created_instances > 0) {
                    $instance->region->decrement('created_instances');
                }

                self::cleanUpTerminatedInstanceData($aws, $instanceDetail);
                return true;
            }

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return false;
        }

        $instance->setAwsStatusPending();

        dispatch(new InstanceChangeStatus($instance, $user, $instance->region, $status));

        return true;
    }
}
