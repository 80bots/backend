<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";

    protected $fillable = [
        'user_id',
        'instance_id',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id');
    }
}
