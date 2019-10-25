<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstanceSessionsHistory extends Model
{
    const STATUS_FAILED     = 'failed';
    const STATUS_SUCCEED    = 'succeed';

    const TYPE_START        = 'start';
    const TYPE_STOP         = 'stop';

    protected $table = 'instance_sessions_history';

    protected $fillable = [
        'scheduling_instances_id',
        'user_id',
        'schedule_type',
        'selected_time',
        'cron_data',
        'current_time_zone',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class,'scheduling_instances_id');
    }
}
