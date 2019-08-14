<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InstanceSessionsHistory extends Model
{
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

    /**
     * Creation of an object for further applying with filters
     *
     * @param $query
     * @return mixed
     */
    public function scopeAjax($query)
    {
        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class,'scheduling_instances_id');
    }
}
