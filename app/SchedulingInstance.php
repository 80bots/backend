<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstance extends Model
{
    protected $tabel = 'scheduling_instances';


   	public static function findByUserId($user_id) {
        return self::where('user_id' , $user_id);
    }

}
