<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotInstancesDetails extends BaseModel
{

    protected $table = 'bot_instances_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'instance_id',
        'start_time',
        'end_time',
        'total_time',
        'aws_instance_type',
        'aws_storage_gb',
        'aws_image_id',
        'aws_image_name',
        'aws_security_group_id',
        'aws_security_group_name',
        'aws_public_dns',
        'aws_pem_file_path'
    ];

    /**
     * @return BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }
}
