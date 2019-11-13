<?php

namespace App;

use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class BotInstance extends BaseModel
{
    use SoftDeletes, HybridRelations;

    const STATUS_PENDING    = 'pending';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';

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
        'used_credit',
        'up_time',
        'total_up_time',
        'cron_up_time',
        'is_in_queue',
        'aws_status',
        'status',
        'start_time'
    ];

    public function scopeFindByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFindByInstanceId($query, $instanceId)
    {
        return $query->where('aws_instance_id', '=', $instanceId);
    }

    public function scopeFindRunningInstanceByUserId($query, $id)
    {
        return $query->where('aws_status', self::STATUS_RUNNING)->where('user_id', $id)->get();
    }

    public function scopeFindRunningInstance($query)
    {
        return $query->where('aws_status', self::STATUS_RUNNING);
    }

    public function scopeFindTerminated($query)
    {
        return $query->where('aws_status', '=', self::STATUS_TERMINATED);
    }

    public function scopeFindNotTerminated($query)
    {
        return $query->where('aws_status', '!=', self::STATUS_TERMINATED);
    }

    public function scopeFindPending($query)
    {
        return $query->where('aws_status', '=', self::STATUS_PENDING);
    }

    public function scopeEmptyData($query)
    {
        return $query->where('aws_status', '=', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('tag_name')
                    ->orWhere('tag_name', '=', '');
            });
    }

    public function setAwsStatusPending()
    {
        $this->update(['aws_status' => BotInstance::STATUS_PENDING]);
    }

    public function setAwsStatusTerminated()
    {
        $this->update(['aws_status' => BotInstance::STATUS_TERMINATED]);
    }

    public function setAwsStatusRunning()
    {
        $this->update(['aws_status' => BotInstance::STATUS_RUNNING]);
    }

    public function setAwsStatusStopped()
    {
        $this->update(['aws_status' => BotInstance::STATUS_STOPPED]);
    }

    public function isAwsStatusTerminated()
    {
        return $this->aws_status === self::STATUS_TERMINATED;
    }

    public function isNotAwsStatusTerminated()
    {
        return $this->aws_status !== self::STATUS_TERMINATED;
    }

    public function mongodb()
    {
        return $this->hasOne(MongoInstance::class, 'instance_id','id');
    }

    public function details()
    {
        return $this->hasMany(BotInstancesDetails::class, 'instance_id', 'id');
    }

    public function oneDetail()
    {
        return $this->hasOne(BotInstancesDetails::class, 'instance_id', 'id')->latest();
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class,'bot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function region()
    {
        return $this->belongsTo(AwsRegion::class, 'aws_region_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class,'instance_id', 'id');
    }

    public function creditsUsage()
    {
        return $this->hasMany(CreditUsage::class, 'instance_id', 'id');
    }

    public function s3Objects()
    {
        return $this->hasMany(S3Object::class, 'instance_id', 'id');
    }

    public function clearPublicIp()
    {
        $this->update(['aws_public_ip' => null]);
    }
}
