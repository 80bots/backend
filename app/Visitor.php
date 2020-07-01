<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends BaseModel
{
    protected $table = "visitors";

    protected $fillable = [
        'user_id',
        'ip'
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
