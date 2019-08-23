<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SchedulingInstance extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    protected $table = 'scheduling_instances';

    protected $fillable = [
        'user_id',
        'instance_id',
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

   	public function scopeFindByUserId($query, $user_id)
    {
        return $query->with('instance.bot')->where('user_id' , $user_id);
    }

    public function scopeFindByUserInstanceId($query, $instanceId, $userId)
    {
   	    return $query->where('instance_id', '=',$instanceId)
            ->where('user_id', '=', $userId)
            ->with('details');
    }

    public function scopeByInstanceId($query, $id)
    {
        return $query->where('instance_id', $id)->with('details');
    }

    public function scopeScheduling($query, $type)
    {
        return $query->where('status', '=', 'active')
            ->with(['details' => function ($query) use ($type) {
                $query->where('schedule_type', '=', $type);
            }, 'instance']);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function instance()
    {
        return $this->belongsTo(BotInstance::class,'instance_id');
    }

    public function details()
    {
   	    return $this->hasMany(SchedulingInstancesDetails::class,'scheduling_id','id');
    }

    public function history()
    {
        return $this->hasMany(InstanceSessionsHistory::class,'scheduling_id');
    }
}
