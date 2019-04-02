<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bots extends Model
{
    protected $fillable = [
        'bot_name', 'aws_ami_image_id', 'aws_ami_name', 'aws_instance_type', 'aws_startup_script', 'aws_storage_gb'
    ];

    public function platform()
    {
        return $this->belongsTo('App\Platforms');
    }
}
