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
}
