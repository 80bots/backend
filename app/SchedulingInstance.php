<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstance extends Model
{
    // protected $tabel = 'scheduling_instances';

   	public static function findByUserId($user_id) {
        return self::with('userInstances')->where('user_id' , $user_id);
    }

    public static function findByUserInstanceId($id, $user_id){
   	    return self::where('user_instances_id', $id)->where('user_id', $user_id)->with('schedulingInstanceDetails');
    }

    public static function findScheduling($type)
    {	
        // Check type start or stop
    	$where = 'utc_start_time';
    	if($type == 'stop')
    	{
    		$where = 'utc_end_time';
    	}
    	
        // Get Current Time   
    	$time = date('H:i');
       
        // Get start and edit time data with active scheduling
     	return self::with('userInstances')->where($where , $time)->where('status', 'active')->get();
    }

    public function userInstances()
    {
        return $this->belongsTo('App\UserInstances','user_instances_id');
    }

    public function schedulingInstanceDetails(){
   	    return $this->hasMany('App\SchedulingInstancesDetails','scheduling_instances_id','id');
    }
}
