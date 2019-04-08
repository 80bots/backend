<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInstances extends BaseModel
{
    protected $hidden = [
    ];

    use SoftDeletes;

    public static function findByUserId($user_id) {
        return self::where('user_id' , $user_id);
    }

    public static function findByInstanceId($instanceId)
    {
        return self::where('aws_instance_id', $instanceId);
    }

    public static function findRunningInstanceByUserId($id)
    {
        return self::where('status', 'running')->where('user_id', $id)->get();
    }

    public function userInstanceDetails()
    {
        return $this->hasMany('App\UserInstancesDetails');
    }

    public function userInstanceDetail()
    {
        return $this->hasOne('App\UserInstancesDetails');
    }
}
