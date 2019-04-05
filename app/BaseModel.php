<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public static function DiffTime($start_time, $end_time)
    {
        $start_date = new DateTime($start_time);
        $end_date = new DateTime($end_time);
        $interval = date_diff($start_date, $end_date);

        $minutes = $interval->days * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;
        return $minutes;
    }

    public static function CalCredit(){
        $upTime = env('UP_TIME_MINUTES','60');
        $credit_score = env('CRADIT_UP_TIME','1');

        $credit = round(($credit_score * 10) / $upTime,2);
        return $credit;
    }

    public static function CalUsedCredit(){

    }
}
