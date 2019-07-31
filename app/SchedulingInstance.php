<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstance extends Model
{
    protected $table = 'scheduling_instances';

    protected $fillable = [
        'user_id',
        'user_instances_id',
        'status',
    ];

   	public static function findByUserId($user_id)
    {
        return self::with('userInstances.bots')->where('user_id' , $user_id);
    }

    public static function findByUserInstanceId($id, $user_id)
    {
   	    return self::where('user_instances_id', $id)->where('user_id', $user_id)->with('details');
    }

    public function scopeScheduling($query, $type)
    {
        return $query->where('status', '=', 'active')
            ->with(['details' => function ($query) use ($type) {
                $query->where('schedule_type', '=', $type);
            }, 'userInstances']);
    }

    public function userInstances()
    {
        return $this->belongsTo('App\UserInstances','user_instances_id');
    }

    public function details()
    {
   	    return $this->hasMany('App\SchedulingInstancesDetails','scheduling_instances_id','id');
    }

    public function history()
    {
        return $this->hasMany('App\InstanceSessionsHistory','scheduling_instances_id');
    }
}
