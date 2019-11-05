<?php

namespace App;

class AwsSetting extends BaseModel
{
    protected $table = "aws_settings";

    protected $fillable = [
        'image_id',
        'type',
        'storage',
        'script',
        'default'
    ];

    public function scopeIsDefault($query)
    {
        return $query->where('default', '=', true);
    }
}
