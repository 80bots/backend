<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Platforms extends Model
{

    public function hasBots($limit = null, $platformId = null, $status = false)
    {
        $query = $this->with(['bots' => function($query) use($limit){
            if($limit) {
              $query->take(5);
            }
        }]);

        if($status && $status == 'active') {
          $query = $query->whereHas('activeBots');
        } else {
          $query = $query->whereHas('bots');
        }

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

    public function activeBots()
    {
        return $this->bots()->where('status', 'active');
    }
}
