<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwsAmi extends BaseModel
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
     * @return BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(AwsRegion::class, 'aws_region_id');
    }
}
