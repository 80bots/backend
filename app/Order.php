<?php

namespace App;

class Order extends BaseModel
{
    protected $table = "orders";

    protected $fillable = [
        'user_id',
        'instance_id',
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
