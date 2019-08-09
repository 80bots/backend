<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstancesDetails extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const TYPE_START        = 'start';
    const TYPE_STOP         = 'stop';

    protected $table = 'scheduling_instances_details';

    protected $fillable = [
        'scheduling_instance_id',
        'day',
        'selected_time',
        'time_zone',
        'cron_data',
        'schedule_type',
        'status',
    ];

    public static function findBySchedulingInstancesId($id)
    {
        return self::where('scheduling_instance_id' , $id);
    }

    public static function findById($id)
    {
        return self::where('id', $id);
    }

    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class, 'scheduling_instance_id');
    }
}
