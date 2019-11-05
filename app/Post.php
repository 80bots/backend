<?php

namespace App;

class Post extends BaseModel
{
    const STATUS_DRAFT      = 'draft';
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const TYPE_BOT          = 'bot';
    const TYPE_POST         = 'post';

    protected $table = "posts";

    protected $fillable = [
        'author_id',
        'bot_id',
        'title',
        'slug',
        'content',
        'status',
        'type'
    ];

    public function likes()
    {
        return $this->belongsToMany(Like::class, 'like_post');
    }

    public function dislikes()
    {
        return $this->belongsToMany(Dislike::class, 'dislike_post');
    }

    public function bot()
    {
        return $this->belongsTo(Bot::class,'bot_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'post_id', 'id');
    }
}
