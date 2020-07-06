<?php

namespace App;

class SchedulingInstancesDetails extends BaseModel
{
    const TYPE_START        = 'start';
    const TYPE_STOP         = 'stop';

    protected $table = 'scheduling_instances_details';

    protected $fillable = [
        'schedule_type',
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
