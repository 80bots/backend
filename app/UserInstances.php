<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInstances extends Model
{
    protected $hidden = [
    ];


    public static function findByUserId($user_id) {
        return self::where('user_id' , $user_id);
    }
}
