<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Self_;

class SchedulingInstance extends Model
{
    // protected $tabel = 'scheduling_instances';

   	public static function findByUserId($user_id) {
        return self::with('userInstances.bots')->where('user_id' , $user_id);
    }

    public static function findByUserInstanceId($id, $user_id){
   	    return self::where('user_instances_id', $id)->where('user_id', $user_id)->with('schedulingInstanceDetails');
    }

    public static function findScheduling($type)
    {
        return self::where('status', '=', 'active')->with(['schedulingInstanceDetails' => function ($query) use ($type) {
            $query->where('schedule_type', '=', $type);
        }, 'userInstances']);
    }

    public function userInstances()
    {
        return $this->belongsTo('App\UserInstances','user_instances_id');
    }

    public function schedulingInstanceDetails(){
   	    return $this->hasMany('App\SchedulingInstancesDetails','scheduling_instances_id','id');
    }

    public function schedulingInstanceHistory(){
        return $this->hasMany('App\InstanceSessionsHistory','scheduling_instances_id');
    }
}
