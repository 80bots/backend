<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    protected $table = "messages";

    protected $fillable = [
        'post_id',
        'parent_id',
        'author_id',
        'content',
        'status',
        'moderation'
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

    public function likes()
    {
        return $this->belongsToMany(Like::class, 'like_message');
    }

    public function dislikes()
    {
        return $this->belongsToMany(Dislike::class, 'dislike_message');
    }
}
