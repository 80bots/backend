<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

class UserInstance extends BaseModel
{
    use SoftDeletes;

    const STATUS_PENDING    = 'pending';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';

    protected $table = "user_instances";

    protected $fillable = [
        'tag_name',
        'tag_user_email',
        'user_id',
        'bot_id',
        'aws_region_id',
        'used_credit',
        'up_time',
        'temp_up_time',
        'cron_up_time',
        'aws_instance_id',
        'aws_ami_id',
        'aws_ami_name',
        'aws_security_group_id',
        'aws_security_group_name',
        'aws_public_ip',
        'aws_public_dns',
        'aws_pem_file_path',
        'status',
        'is_in_queue',
        'created_at',
        'updated_at'
    ];

    /**
     * Creation of an object for further applying with filters
     *
     * @param $query
     * @return mixed
     */
    public function scopeAjax($query)
    {
        return $query;
    }

    public function scopeFindByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function findByInstanceId($instanceId)
    {
        return self::where('aws_instance_id', $instanceId);
    }

    public static function findRunningInstanceByUserId($id)
    {
        return self::where('status', 'running')->where('user_id', $id)->get();
    }

    public static function findRunningInstance()
    {
        return self::where('status', 'running')->get();
    }

    public function details()
    {
        return $this->hasMany(UserInstancesDetails::class, 'user_instance_id', 'id');
    }

    public function bots()
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

    // public function schedulingInstance()
    // {
    //     return $this->hasMany('App\SchedulingInstance','user_instances_id');
    // }
}
