<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AwsRegion extends Model
{
    const TYPE_EC2 = 'ec2';

    protected $table = "aws_regions";

    protected $fillable = [
        'code',
        'name',
        'type'
    ];

    public function scopeOnlyEc2($query)
    {
        $query->where('type', '=', self::TYPE_EC2);
    }
}
