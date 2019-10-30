<?php

namespace App;

class Message extends BaseModel
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

    public function scopeIsModerated($query)
    {
        return $query->where('moderation', true);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author_id');
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
