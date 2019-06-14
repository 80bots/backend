<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstancesDetails extends Model
{
    public static function findBySchedulingInstancesId($id) {
        return self::where('scheduling_instances_id' , $id);
    }

    public static function findById($id)
    {
        return self::where('id', $id);
    }

    public function schedulingInstance()
    {
        return $this->belongsTo('App\SchedulingInstance');
    }
}
