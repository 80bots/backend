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

   	public function scopeFindByUserId($query, $user_id)
    {
        return $query->with('userInstance.bots')->where('user_id' , $user_id);
    }

    public static function findByUserInstanceId($id, $user_id)
    {
   	    return self::where('user_instances_id', $id)->where('user_id', $user_id)->with('details');
    }

    public function scopeByInstanceId($query, $id)
    {
        return $query->where('user_instances_id', $id)->with('details');
    }

    public function scopeScheduling($query, $type)
    {
        return $query->where('status', '=', 'active')
            ->with(['details' => function ($query) use ($type) {
                $query->where('schedule_type', '=', $type);
            }, 'userInstances']);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function userInstance()
    {
        return $this->belongsTo(UserInstances::class,'user_instances_id');
    }

    public function details()
    {
   	    return $this->hasMany(SchedulingInstancesDetails::class,'scheduling_instances_id','id');
    }

    public function history()
    {
        return $this->hasMany(InstanceSessionsHistory::class,'scheduling_instances_id');
    }
}
