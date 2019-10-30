<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MongoInstance extends Eloquent
{
    protected $connection = 'mongodb';

    protected $collection = 'instances';

    protected $fillable = [
        'instance_id',
        'tag_name',
        'tag_user_email',
        'bot_path',
        'bot_name',
        'aws_region',
        'aws_instance_type',
        'aws_storage_gb',
        'aws_image_id',
        'params'
    ];

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
    }
}
