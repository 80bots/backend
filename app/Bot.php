<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const TYPE_PUBLIC       = 'public';
    const TYPE_PRIVATE      = 'private';

    protected $table = "bots";

    protected $fillable = [
        'name',
        'platform_id',
        'description',
        'aws_ami_image_id',
        'aws_ami_name',
        'aws_instance_type',
        'aws_startup_script',
        'aws_custom_script',
        'aws_storage_gb',
        'status',
        'type'
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

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'bot_tag');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'bot_user');
    }
}
