<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
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
        return $this->belongsToMany(Like::class, 'like_post');
    }

    public function dislikes()
    {
        return $this->belongsToMany(Dislike::class, 'dislike_post');
    }
}
