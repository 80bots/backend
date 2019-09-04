<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BotInstancesDetails extends Model
{

    protected $table = 'bot_instances_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'instance_id',
        'tag_name',
        'tag_user_email',
        'start_time',
        'end_time',
        'total_time',
        'aws_instance_id',
        'aws_instance_type',
        'aws_storage_gb',
        'aws_image_id',
        'aws_image_name',
        'aws_security_group_id',
        'aws_security_group_name',
        'aws_public_ip',
        'aws_public_dns',
        'aws_pem_file_path'
    ];

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }

    public function clearPublicIp()
    {
        $this->update(['aws_public_ip' => null]);
    }
}
