<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AwsSetting extends Model
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
