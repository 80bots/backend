<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    protected $table = "subscription_plans";

    protected $fillable = [
        'name',
        'price',
        'credit',
        'slug',
        'stripe_plan',
        'status'
    ];

    /**
     * Return only active plans
     * @param $query
     */
    public function scopeOnlyActive($query)
    {
        $query->where('status', '=', self::STATUS_ACTIVE);
    }
}
