<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Platforms extends Model
{

    public function hasBots($limit = null, $platformId = null)
    {
        $query = $this->with(['bots' => function($query) use($limit){
            if($limit) {
              $query->take(5);
            }
        }])->whereHas('bots');

        if($platformId) {
          $query =  $query->where('id', $platformId);
        }

        return $query;
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
