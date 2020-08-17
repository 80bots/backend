<?php

namespace App;

/**
 * @method static isDefault()
 */
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

    /**
     * @param $query
     * @return array
     */
    public function scopeIsDefault($query)
    {
        return $query->where('default', '=', true);
    }
}
