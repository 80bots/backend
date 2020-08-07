<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanceSessionsHistory extends BaseModel
{
    const STATUS_FAILED     = 'failed';
    const STATUS_SUCCEED    = 'succeed';

    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';

    protected $table = 'instance_sessions_history';

    protected $fillable = [
        'scheduling_instances_id',
        'user_id',
        'schedule_type',
        'cron_data',
        'current_time_zone',
        'status',
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class,'scheduling_instances_id');
    }
}
