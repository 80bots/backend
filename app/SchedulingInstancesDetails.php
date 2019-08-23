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
        'scheduling_id',
        'day',
        'selected_time',
        'time_zone',
        'cron_data',
        'schedule_type',
        'status',
    ];

    public function scopeFindBySchedulingInstancesId($query, $id)
    {
        return $query->where('scheduling_id', '=', $id);
    }

    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class, 'scheduling_id', 'id');
    }
}
