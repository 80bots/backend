<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInstancesDetails extends Model
{

    protected $table = 'user_instances_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_instance_id',
        'start_time',
        'end_time',
        'total_time',
        'status',
    ];

    public function userInstance()
    {
        return $this->belongsTo(UserInstance::class);
    }
}
