<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    public static function CalCredit(){
        $upTime = env('UP_TIME_MINUTES');
        $credit_score = env('CRADIT_UP_TIME');

        $credit = round(($credit_score * 10) / $upTime,2);
        return $credit;
    }

    public static function CalUsedCredit(){

    }
}
