<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Platforms extends Model
{

    public static function findWithBots(){
        return self::with(['bots' => function($query){
            $query->take(5);
        }])->whereHas('bots');
    }

    public static function findBotsWithPlatformId($id){
        return self::with(['bots'])->where('id', $id);
    }

    public static function findByName(string $platform_name)
    {
        return self::where('name',$platform_name)->first();
    }

    public function bots()
    {
        return $this->hasMany('App\Bots','platform_id');
    }
}
