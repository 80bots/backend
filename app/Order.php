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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function instance()
    {
        return $this->belongsTo(BotInstance::class, 'instance_id');
    }
}
