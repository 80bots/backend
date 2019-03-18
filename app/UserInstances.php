<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;

class UserInstances extends Model
{
    protected $hidden = [
    ];


    public static function findByUserId($user_id) {
        return self::where('user_id' , $user_id);
    }

    public function userInstanceDetails()
    {
        return $this->hasMany('App\UserInstancesDetails');
    }

    public function userInstanceDetail()
    {
        return $this->hasOne('App\UserInstancesDetails');
    }

    public static function deffTime($start_date, $end_date){

        $start_date = new DateTime($start_date);
        $end_date = new DateTime($end_date);
        $interval = date_diff($start_date, $end_date);
//        $min = $interval->format('%i');

        $minutes = $interval->days * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;
        return $minutes;
    }
}
