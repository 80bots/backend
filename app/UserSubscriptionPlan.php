<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSubscriptionPlan extends Model
{
    protected $table = 'user_subscription_plans';

    protected $fillable = [
        'user_id',
        'plans_id',
        'credit',
        'slug',
        'stripe_plan',
        'total_credit',
        'start_subscription',
        'expired_subscription',
        'auto_renewal',
        'status'
    ];
}
