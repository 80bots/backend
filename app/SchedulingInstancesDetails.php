<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstancesDetails extends Model
{
    protected $table = 'scheduling_instances_details';

    protected $fillable = [
        'scheduling_instances_id',
        'day',
        'selected_time',
        'time_zone',
        'cron_data',
        'schedule_type',
        'status',
    ];

    public static function findBySchedulingInstancesId($id)
    {
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
