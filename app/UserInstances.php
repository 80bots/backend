<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInstances extends BaseModel
{
    use SoftDeletes;

    protected $table = "bots";

    protected $fillable = [
        'tag_name',
        'tag_user_email',
        'user_id',
        'bot_id',
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

    protected $hidden = [
    ];

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

    public function userInstanceDetails()
    {
        return $this->hasMany('App\UserInstancesDetails');
    }

    public function userInstanceDetail()
    {
        return $this->hasOne('App\UserInstancesDetails');
    }

    public function bots()
    {
        return $this->belongsTo('App\Bots','bot_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    // public function schedulingInstance()
    // {
    //     return $this->hasMany('App\SchedulingInstance','user_instances_id');
    // }
}
