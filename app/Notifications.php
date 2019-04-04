<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    public static function CalCredit(){
        $upTime = env('UP_TIME_MINUTES','60');
        $credit_score = env('CRADIT_UP_TIME','1');

        $credit = round(($credit_score * 10) / $upTime,2);
        return $credit;
    }

    public static function CalUsedCredit(){

    }
}
