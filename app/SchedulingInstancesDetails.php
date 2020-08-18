<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static updateOrCreate(array $array)
 * @method static whereIn(string $string, $input)
 * @method static where(string $string, string $string1, $param)
 */
class SchedulingInstancesDetails extends BaseModel
{
    const STATUS_RUNNING    = 'running';
    const STATUS_STOPPED    = 'stopped';

    protected $table = 'scheduling_instances_details';

    protected $fillable = [
        'scheduling_id',
        'day',
        'time',
        'time_zone',
        'status',
    ];

    /**
     * @param $query
     * @param $id
     * @return array
     */
    public function scopeFindBySchedulingInstancesId($query, $id)
    {
        return $query->where('scheduling_id', '=', $id);
    }

    /**
     * @return BelongsTo
     */
    public function schedulingInstance()
    {
        return $this->belongsTo(SchedulingInstance::class, 'scheduling_id', 'id');
    }
}
