<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstance extends Model
{
    // protected $tabel = 'scheduling_instances';

   	public static function findByUserId($user_id) {
        return self::where('user_id' , $user_id);
    }

    public static function findScheduling($type)
    {	
    	$where = 'start_time';
    	if($type == 'stop')
    	{
    		$where = 'end_time';
    	}
    	
    	$time = date('H:i');
    	return self::with('userInstances')->where($where , $time)->get();
    }

    public function userInstances()
    {
        return $this->belongsTo('App\UserInstances','user_instances_id');
    }
}
