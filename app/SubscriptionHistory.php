<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
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
