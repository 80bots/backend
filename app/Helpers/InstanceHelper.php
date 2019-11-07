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
use phpDocumentor\Reflection\Types\Self_;
use Throwable;

class InstanceHelper
{
    const LIMIT_S3_LIST_OBJECTS = 1000;
    const LIMIT_S3_OBJECTS_INFO = 2;
    const DATA_STREAMER_FOLDER  = "streamer-data";

    /**
     * @param SchedulingInstancesDetails $detail
     * @param int $currentTime
     * @return bool
     */
    public static function isScheduleInstance(SchedulingInstancesDetails $detail, int $currentTime): bool
    {
        try {
            $tz = CarbonTimeZone::create($detail->time_zone);
            $ct = Carbon::createFromFormat('D h:i A', "{$detail->day} {$detail->selected_time}", $tz);
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
        $instancesIds   = [];
        $insertHistory  = [];

        foreach ($schedulers as $scheduler) {

            if (! empty($scheduler->details)) {

                foreach ($scheduler->details as $detail) {

                    $currentTime = Carbon::parse($now->format('D h:i A'))
                        ->setTimezone($detail->time_zone)
                        ->getTimestamp();

                    if (self::isScheduleInstance($detail, $currentTime)) {

                        if (! empty($scheduler->instance->aws_instance_id)) {

                            $tz = CarbonTimeZone::create($detail->time_zone);

                            array_push($insertHistory, [
                                'scheduling_instances_id'   => $scheduler->id,
                                'user_id'                   => $scheduler->user_id,
                                'schedule_type'             => $detail->schedule_type,
                                'cron_data'                 => $detail->cron_data,
                                'current_time_zone'         => $tz->toRegionName(),
                                'selected_time'             => $detail->selected_time,
                            ]);

                            array_push($instancesIds, $scheduler->instance->aws_instance_id);
                        }
                    }
                }
            }
        }

        if (! empty($insertHistory)) {
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

        $availableStatuses = [
            BotInstance::STATUS_RUNNING,
            BotInstance::STATUS_STOPPED,
            BotInstance::STATUS_TERMINATED
        ];

        foreach ($instancesByStatus as $statusKey => $instances) {

            if (in_array($statusKey, $availableStatuses)) {

                foreach ($instances as $key => $instance) {

                    $user = User::where('email', '=', $instance['tag_user_email'])->first();

                    if (! empty($user)) {

                        $status = $statusKey === 'stopping' ? BotInstance::STATUS_STOPPED : $statusKey;

                        $instanceId = $instance['aws_instance_id'] ?? null;

                        $botInstance = $user->instances()->where('aws_instance_id', '=', $instanceId)->first();

                        if (! empty($botInstance)) {
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

    private static function syncInstancesUpdateStatus(BotInstance $botInstance, string $status, array $instance, string $currentDate): void
    {
        $oldDetail = $botInstance->details()->latest()->first();

        if ($botInstance->aws_status === BotInstance::STATUS_STOPPED && $status === BotInstance::STATUS_RUNNING) {

            $detail = $oldDetail->replicate([
                'end_time', 'total_time'
            ]);

            $detail->fill([
                'start_time'                => $instance['aws_launch_time'],
                'aws_public_dns'            => $instance['aws_public_ip'],
                'aws_instance_type'         => $instance['aws_instance_type'],
                'aws_image_id'              => $instance['aws_image_id'],
                'aws_security_group_id'     => $instance['aws_security_group_id'],
                'aws_security_group_name'   => $instance['aws_security_group_name'],
            ]);

            $detail->save();
        } else {
            $detail = $oldDetail;
        }

        $botInstance->update([
            'aws_public_ip' => $instance['aws_public_ip'],
            'aws_status'    => $status
        ]);

        $detail->update([
            'aws_instance_type'         => $instance['aws_instance_type'],
            'aws_image_id'              => $instance['aws_image_id'],
            'aws_security_group_id'     => $instance['aws_security_group_id'],
            'aws_security_group_name'   => $instance['aws_security_group_name'],
            'aws_public_dns'            => $instance['aws_public_dns'],
        ]);

        if ($status === BotInstance::STATUS_TERMINATED) {

            if ($botInstance->region->created_instances > 0) {
                $botInstance->region->decrement('created_instances');
            }

            // TODO: Check whether old status was 'running'
            self::updateUpTime($botInstance, $detail, $currentDate);
        }

        unset($oldDetail, $detail);
    }

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

        if (empty($reservationObj) || empty($reservationObj[0])) {
            return false;
        }

        $state = $reservationObj[0]['Instances'][0]['State']['Name'];

        return $state === 'terminated';
    }

    /**
     * @param BotInstance $instance
     * @return array
     */
    public static function getListInstancesDates(BotInstance $instance): array
    {
        $dates = [];

        if (! empty($instance->created_at)) {
            $created    = Carbon::parse($instance->created_at);
            $now        = Carbon::now();

            $diffTime = $created->diffInDays($now);

            for ($i = $diffTime; $i >= 0; $i--) {
                if ($i==0) {
                    array_push($dates, $created->toDateString());
                } else {
                    array_push($dates, $created->copy()->addDays($i)->toDateString());
                }
            }

            unset($created, $now, $diffTime);
        }

        return $dates;
    }

    /**
     * @param Aws $aws
     * @param array $keys
     * @return array
     */
    public static function getListLinksToS3Objects(Aws $aws, array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            array_push($result, $aws->getPresignedLink($aws->getS3Bucket(), $key));
        }

        return $result;
    }

    /**
     * @param string $type
     * @return string
     */
    public static function getTypeS3Object(?string $type): ?string
    {
        switch ($type) {
            case S3Object::TYPE_SCREENSHOTS:
            case S3Object::TYPE_IMAGES:
            case S3Object::TYPE_LOGS:
            case S3Object::TYPE_JSON:
            case S3Object::TYPE_ENTITY:
                return $type;
            default:
                return null;
        }
    }

    public static function getThumbnailPathByTypeS3Object(string $type): string
    {
        switch ($type) {
            case S3Object::TYPE_IMAGES:
                return 'output/images/thumbnail.jpg';
            default:
                return 'output/screenshots/thumbnail.jpg';
        }
    }

    /**
     * @param BotInstance $instance
     * @param string $type
     * @param string $date
     */
    public static function saveS3Objects(BotInstance $instance, string $type, string $date)
    {
        $credentials = [
            'key'    => config('aws.iam.access_key'),
            'secret' => config('aws.iam.secret_key')
        ];

        $aws = new Aws;
        $aws->s3Connection('', $credentials);

        $next   = '';

        $folder = config('aws.streamer.folder');

        $prefix = "{$folder}/{$instance->tag_name}/{$type}/{$date}";

        $links  = [];

        do {

            $result = $aws->getS3ListObjects($aws->getS3Bucket(), self::LIMIT_S3_LIST_OBJECTS, $prefix, $next);

            if ($result->hasKey('IsTruncated') && $result->get('IsTruncated')) {
                if ($result->hasKey('NextContinuationToken')) {
                    $next = $result->get('NextContinuationToken');
                }
            } else {
                $next = '';
            }

            if ($result->hasKey('Contents')) {

                $contents = collect($result->get('Contents'))->map(function ($item, $key) {
                    return $item['Key'];
                });

                if ($contents->isNotEmpty()) {
                    $links = array_merge(
                        $links,
                        InstanceHelper::getListLinksToS3Objects($aws, $contents->toArray())
                    );
                }
            }

        } while (! empty($next));

        if (! empty($links)) {

            $objects = [];

            foreach ($links as $link) {

                $data = [
                    'instance_id'   => $instance->id ?? null,
                    'folder'        => $date,
                    'link'          => $link,
                    'expires'       => Carbon::now()->addHour()->toDateTimeString(),
                    'type'          => $type
                ];

                array_push($objects, $data);
            }

            S3Object::insert($objects);
        }
    }

    /**
     * @param BotInstance $instance
     */
    public static function saveS3Logs(BotInstance $instance)
    {
        $credentials = [
            'key'    => config('aws.iam.access_key'),
            'secret' => config('aws.iam.secret_key')
        ];

        $aws = new Aws;
        $aws->s3Connection('', $credentials);

        try {

            $folder = config('aws.streamer.folder');

            $init = $aws->getPresignedLink($aws->getS3Bucket(), "{$folder}/{$instance->tag_name}/logs/INIT.log");
            $work = $aws->getPresignedLink($aws->getS3Bucket(), "{$folder}/{$instance->tag_name}/logs/WORK.log");

            if (!empty($init) && !empty($work)) {

                $objects = [
                    [
                        'instance_id'   => $instance->id ?? null,
                        'link'          => $init,
                        'expires'       => Carbon::now()->addHour()->toDateTimeString(),
                        'type'          => S3Object::TYPE_LOGS
                    ],
                    [
                        'instance_id'   => $instance->id ?? null,
                        'link'          => $work,
                        'expires'       => Carbon::now()->addHour()->toDateTimeString(),
                        'type'          => S3Object::TYPE_LOGS
                    ]
                ];

                S3Object::insert($objects);
            }

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }

    /**
     * @param Aws $aws
     * @param string $prefix
     * @param string $date
     * @param string $nowDate
     * @param string $yesterdayDate
     * @return array
     */
    public static function getDateInfo(Aws $aws, string $prefix, string $date, string $nowDate, string $yesterdayDate): array
    {
        $result = $aws->getS3ListObjects($aws->getS3Bucket(), 2, $prefix);

        if (! $result->hasKey('Contents')) {
            return [];
        }

        $contents = collect($result->get('Contents'))->map(function ($item, $key) {
            return [
                'key'       => $item['Key'],
                'modified'  => $item['LastModified']->getTimestamp()
            ];
        })->filter(function ($item, $key) use ($prefix) {
            return $item['key'] !== "{$prefix}/" ;
        });

        if ($contents->count() === 0) {
            return [];
        }

        $thumbnail = $contents->first();

        if ($date === $nowDate) {
            $name = 'Today';
        } elseif ($date === $yesterdayDate) {
            $name = 'Yesterday';
        } else {
            $name = $date;
        }

        if (! empty($thumbnail['key'])) {
            $info       = pathinfo($thumbnail['key']);
            $thumbnail  = $aws->getPresignedLink($aws->getS3Bucket(), $thumbnail['key']);
        } else {
            $thumbnail  = '';
            $info       = '';
        }

        return [
            "name"      => $name,
            "thumbnail" => [
                'url'   => $thumbnail,
                'name'  => $info['filename'] ?? ''
            ]
        ];
    }

    public static function getObjectByPath($instanceId, string $path): S3Object
    {
        $pathInfo   = pathinfo(trim($path, '/'));
        $parentPath = $pathInfo['dirname'];
        $filename   = $pathInfo['filename'];
        $entity     = !empty($pathInfo['extension']) ? S3Object::ENTITY_FILE : S3Object::ENTITY_FOLDER;
        $type       = self::getTypeS3ObjectByExtension($pathInfo['extension'] ?? null, $path);

        if($parentPath === '.') {
            $object = S3Object::firstOrCreate([
                'instance_id'   => $instanceId,
                'path'          => $path,
                'name'          => $filename,
                'entity'        => $entity,
                'type'          => $type
            ]);
        } else {
            $object = S3Object::wherePath($path)
                ->whereInstanceId($instanceId)
                ->whereEntity($entity)
                ->first();

            if (! $object) {
                $parent = self::getObjectByPath($instanceId, $parentPath);
                $object = $parent->children()->create([
                    'instance_id'   => $instanceId,
                    'path'          => $path,
                    'name'          => $filename,
                    'entity'        => $entity,
                    'type'          => $type
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
                if (strpos($path, 'screenshots') !== false){
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
     * @param BotInstance $instance
     * @param S3Object $folder
     * @param string $type
     * @param int $limit
     * @param int $offset
     */
    public static function updateObjectsOldLinks(BotInstance $instance, string $folder, string $type, int $limit, int $offset): void
    {
        $expires = Carbon::now()->addMinutes(10)->toDateTimeString();

        $credentials = [
            'key'    => config('aws.iam.access_key'),
            'secret' => config('aws.iam.secret_key')
        ];

        $aws = new Aws;
        $aws->s3Connection('', $credentials);

        $objects = $instance->s3Objects()
            ->where('path', 'like', "{$folder}/{$type}/%")
            ->where('entity', '=', S3Object::ENTITY_FILE)
            ->where('name', '!=', 'thumbnail')
            ->where(function ($query) use ($expires) {
                $query->where('expires', '<=', $expires)
                    ->orWhereNull('link');
            })
            ->latest()
            ->skip($offset)
            ->take($limit)
            ->get();

        foreach ($objects as $object) {
            $prefix = "{$instance->baseS3Dir}/{$object->path}";
            $object->update([
                'expires'   => Carbon::now()->addHour()->toDateTimeString(),
                'link'      => $aws->getPresignedLink($aws->getS3Bucket(), $prefix)
            ]);
        }

        unset($expires, $credentials, $aws, $objects);
    }

    public static function getFreshLink(S3Object $object): string
    {
        $credentials = [
            'key'    => config('aws.iam.access_key'),
            'secret' => config('aws.iam.secret_key')
        ];

        $aws = new Aws;
        $aws->s3Connection('', $credentials);
        $base = $object->instance->baseS3Dir;
        $key = "{$base}/{$object->path}";
        return $aws->getPresignedLink($aws->getS3Bucket(), $key);
    }


    /**
     * @param Aws $aws
     * @param string|null $ip
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function createAwsKeyAndGroup(Aws $aws, ?string $ip): ?array
    {
        $keyPair        = $aws->createKeyPair(config('aws.bucket'));
        $tagName        = $aws->createTagName();
        $securityGroup  = $aws->createSecretGroup($ip);

        if (empty($keyPair) || empty($tagName) || empty($securityGroup)) {
            return null;
        }

        return [
            'tagName'       => $tagName,
            'keyPairName'   => $keyPair['keyName'],
            'keyPairPath'   => $keyPair['path'],
            'groupId'       => $securityGroup['securityGroupId'],
            'groupName'     => $securityGroup['securityGroupName'],
        ];
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

        if($withTrashed) {
            $query->withTrashed();
        }

        if (! Auth::user()->isAdmin()) {
            $query->where('user_id', '=', Auth::id());
        }

        return $query->first();
    }

    public static function changeInstanceStatus($status, $id): bool
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

        $user   = User::find(Auth::id());
        $aws    = new Aws;

        //
        $instance->clearPublicIp();

        try {

            $describeInstancesResponse = $aws->describeInstances(
                [$instance->aws_instance_id ?? null],
                $instance->region->code
            );

            if (! $describeInstancesResponse->hasKey('Reservations') || self::checkTerminatedStatus($describeInstancesResponse)) {
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
