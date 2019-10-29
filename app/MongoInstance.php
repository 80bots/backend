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
        'aws_region_id',
        'used_credit',
        'total_up_time',
        'params',
        'details'
    ];

//    public function instance()
//    {
//        return $this->belongsTo(BotInstance::class, 'instance_id', 'id');
//    }
}
