<?php

namespace App;

use App\Helpers\QueryHelper;

class AwsRegion extends BaseModel
{
    const PERCENT_LIMIT = 0.9;

    const TYPE_EC2      = 'ec2';

    const ORDER_FIELDS      = [
        'name' => [
            'entity'    => QueryHelper::ENTITY_AWS_REGION,
            'field'     => 'name'
        ],
        'code' => [
            'entity'    => QueryHelper::ENTITY_AWS_REGION,
            'field'     => 'code'
        ],
        'limit' => [
            'entity'    => QueryHelper::ENTITY_AWS_REGION,
            'field'     => 'limit'
        ],
        'used_limit' => [
            'entity'    => QueryHelper::ENTITY_AWS_REGION,
            'field'     => 'created_instances'
        ],
    ];

    protected $table    = "aws_regions";

    protected $fillable = [
        'code',
        'name',
        'type',
        'limit',
        'created_instances',
        'default_image_id'
    ];

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
