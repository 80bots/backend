<?php

namespace App;

class Visitor extends BaseModel
{
    protected $table = "visitors";

    protected $fillable = [
        'user_id',
        'ip'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
