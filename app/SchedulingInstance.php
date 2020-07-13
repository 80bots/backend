<?php

namespace App;

use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulingInstance extends BaseModel
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const ORDER_FIELDS      = [
        'status' => [
            'entity'    => QueryHelper::ENTITY_SCHEDULING,
            'field'     => 'status'
        ],
        'instance_id' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'aws_instance_id'
        ],
        'bot_name' => [
            'entity'    => QueryHelper::ENTITY_BOT_INSTANCES,
            'field'     => 'tag_name'
        ],
        'user' => [
            'entity'    => QueryHelper::ENTITY_USER,
            'field'     => 'email'
        ],
    ];

    protected $table = 'scheduling_instances';

    protected $fillable = [
        'user_id',
        'instance_id',
        'status',
    ];

    /**
     * @param $query
     * @param $user_id
     * @return array
     */
   	public function scopeFindByUserId($query, $user_id)
    {
        return $query->with('instance.bot')->where('scheduling_instances.user_id' , $user_id);
    }

    /**
     * @param $query
     * @param $instanceId
     * @param $userId
     * @return array
     */
    public function scopeFindByUserInstanceId($query, $instanceId, $userId)
    {
   	    return $query->where('instance_id', '=', $instanceId)
            ->where('user_id', '=', $userId)
            ->with('details');
    }

    /**
     * @param $query
     * @param $id
     * @return array
     */
    public function scopeByInstanceId($query, $id)
    {
        return $query->where('instance_id', $id)->with('details');
    }

    /**
     * @param $query
     * @param $status
     * @return array
     */
    public function scopeScheduling($query, $status)
    {
        return $query->where('status', '=', 'active')
            ->with(['details' => function ($query) use ($status) {
                $query->where('status', '=', $status);
            }, 'instance']);
    }

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
    public function instance()
    {
        return $this->belongsTo(BotInstance::class,'instance_id');
    }

    /**
     * @return HasMany
     */
    public function details()
    {
   	    return $this->hasMany(SchedulingInstancesDetails::class,'scheduling_id','id');
    }

    /**
     * @return HasMany
     */
    public function history()
    {
        return $this->hasMany(InstanceSessionsHistory::class,'scheduling_id');
    }
}
