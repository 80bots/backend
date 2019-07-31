<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bots extends Model
{
    protected $fillable = [
        'bot_name',
        'description',
        'aws_ami_image_id',
        'aws_ami_name',
        'aws_instance_type',
        'aws_startup_script',
        'aws_custom_script',
        'aws_storage_gb',
        'platform_id'
    ];

    public function platform()
    {
        return $this->belongsTo('App\Platforms');
    }

    public function botTags()
    {
        return $this->hasMany('App\BotTags');
    }
}
