<?php

namespace App;

use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotInstance extends BaseModel
{
    use SoftDeletes;

    const STATUS_PENDING    = 'pending';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';
    const STATUS_RESTART    = 'restart';

    const ORDER_FIELDS      = [
        'region' => [
            'entity'    => QueryHelper::ENTITY_AWS_REGION,
            'field'     => 'name'
        ],
        'launched_by' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'tag_user_email'
        ],
        'name' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'tag_name'
        ],
        'uptime' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES_UPTIME,
            'field'     => 'total_up_time'
        ],
        'status' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'aws_status'
        ],
        'launched_at' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'start_time'
        ],
        'ip' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'aws_public_ip'
        ],
        'bot_name' => [
            'entity'    => QueryHelper::ENTITY_BOT,
            'field'     => 'name'
        ],
    ];

    protected $table = "bot_instances";

    protected $fillable = [
        'user_id',
        'bot_id',
        'tag_name',
        'tag_user_email',
        'aws_instance_id',
        'aws_public_ip',
        'aws_region_id',
        'up_time',
        'total_up_time',
        'cron_up_time',
        'is_in_queue',
        'aws_status',
        'status',
        'start_time',
        'last_notification',
    ];

    /**
     * @return string
     */
    public function getBaseS3DirAttribute()
    {
        return InstanceHelper::DATA_STREAMER_FOLDER . '/' . $this->tag_name;
    }

    /**
     * @param $query
     * @param $userId
     * @return array
     */
    public function scopeFindByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param $query
     * @param $instanceId
     * @return array
     */
    public function scopeFindByInstanceId($query, $instanceId)
    {
        return $query->where('aws_instance_id', '=', $instanceId);
    }

    /**
     * @param $query
     * @param $id
     * @return array
     */
    public function scopeFindRunningInstanceByUserId($query, $id)
    {
        return $query->where('aws_status', self::STATUS_RUNNING)->where('user_id', $id)->get();
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeFindRunningInstance($query)
    {
        return $query->where('aws_status', self::STATUS_RUNNING);
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeFindTerminated($query)
    {
        return $query->where('aws_status', '=', self::STATUS_TERMINATED);
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeFindNotTerminated($query)
    {
        return $query->where('aws_status', '!=', self::STATUS_TERMINATED);
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeFindPending($query)
    {
        return $query->where('aws_status', '=', self::STATUS_PENDING);
    }

    /**
     * @param $query
     * @return array
     */
    public function scopeEmptyData($query)
    {
        return $query->where('aws_status', '=', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('tag_name')
                    ->orWhere('tag_name', '=', '');
            });
    }

    /**
     * @return void
     */
    public function setAwsStatusPending()
    {
        $this->update(['aws_status' => BotInstance::STATUS_PENDING]);
    }

    /**
     * @return void
     */
    public function setAwsStatusTerminated()
    {
        $this->update(['aws_status' => BotInstance::STATUS_TERMINATED]);
    }

    /**
     * @return void
     */
    public function setAwsStatusRunning()
    {
        $this->update(['aws_status' => BotInstance::STATUS_RUNNING]);
    }

    /**
     * @return void
     */
    public function setAwsStatusStopped()
    {
        $this->update(['aws_status' => BotInstance::STATUS_STOPPED]);
    }

    /**
     * @return bool
     */
    public function isAwsStatusTerminated()
    {
        return $this->aws_status === self::STATUS_TERMINATED;
    }

    /**
     * @return bool
     */
    public function isNotAwsStatusTerminated()
    {
        return $this->aws_status !== self::STATUS_TERMINATED;
    }

    /**
     * @return HasOne
     */
    public function about()
    {
        return $this->hasOne(AboutInstance::class, 'instance_id','id');
    }

    /**
     * @return HasMany
     */
    public function details()
    {
        return $this->hasMany(BotInstancesDetails::class, 'instance_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function oneDetail()
    {
        return $this->hasOne(BotInstancesDetails::class, 'instance_id', 'id')->latest();
    }

    /**
     * @return BelongsTo
     */
    public function bot()
    {
        return $this->belongsTo(Bot::class,'bot_id');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(AwsRegion::class, 'aws_region_id');
    }

    /**
     * @return HasMany
     */
    public function s3Objects()
    {
        return $this->hasMany(S3Object::class, 'instance_id', 'id');
    }

    /**
     * @return void
     */
    public function clearPublicIp()
    {
        $this->update(['aws_public_ip' => null]);
    }
}
