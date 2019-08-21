<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AwsAmi extends Model
{
    const VISIBILITY_PUBLIC     = 'public';
    const VISIBILITY_PRIVATE    = 'private';

    protected $table = "aws_amis";

    protected $fillable = [
        'aws_region_id',
        'name',
        'description',
        'image_id',
        'architecture',
        'source',
        'image_type',
        'owner',
        'visibility',
        'status',
        'ena_support',
        'hypervisor',
        'root_device_name',
        'root_device_type',
        'sriov_net_support',
        'virtualization_type',
        'creation_date'
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

    public function region()
    {
        return $this->belongsTo(AwsRegion::class, 'aws_region_id');
    }
}
