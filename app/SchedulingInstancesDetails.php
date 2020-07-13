<?php

namespace App;

class SchedulingInstancesDetails extends BaseModel
{
    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';

    protected $table = 'scheduling_instances_details';

    protected $fillable = [
        'scheduling_id',
        'platform_time',
        'schedule_time',
        'time_zone',
        'cron_data',
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
