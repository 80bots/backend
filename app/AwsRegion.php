<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AwsRegion extends Model
{
    const PERCENT_LIMIT = 0.9;

    const TYPE_EC2      = 'ec2';

    protected $table    = "aws_regions";

    protected $fillable = [
        'code',
        'name',
        'type',
        'limit',
        'created_instances',
        'default_image_id'
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

    public function scopeOnlyEc2($query)
    {
        $query->where('type', '=', self::TYPE_EC2);
    }

    public function scopeOnlyRegion($query, $region = '')
    {
        $region = empty($region) ? config('aws.region', 'us-east-2') : $region;
        $query->where('code', '=', $region);
    }

    public function amis()
    {
        return $this->hasMany(AwsAmi::class,'aws_region_id', 'id');
    }

    public function instances()
    {
        return $this->hasMany(BotInstance::class,'aws_region_id', 'id');
    }
}
