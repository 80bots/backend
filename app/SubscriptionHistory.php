<?php

namespace App;

class SubscriptionHistory extends BaseModel
{
    protected $table = "subscription_histories";

    protected $fillable = [
        'user_id',
        'stripe_plan',
        'stripe_subscription_id',
        'price',
        'credits'
    ];
}
