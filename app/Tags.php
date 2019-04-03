<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    //
    public static function findByName($name)
    {
        return self::where('name' , $name)->first();
    }
}
