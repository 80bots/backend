<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInstances extends BaseModel
{
    protected $hidden = [
    ];

    protected $guarded = [];

    use SoftDeletes;

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
