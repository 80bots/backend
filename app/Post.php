<?php

namespace App;

class Post extends BaseModel
{
    const STATUS_DRAFT      = 'draft';
    const STATUS_ACTIVE     = 'active';
    const STATUS_INACTIVE   = 'inactive';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE
    ];

    const TYPE_PAGE         = 'page';
    const TYPE_POST         = 'post';

    const TYPES = [
        self::TYPE_PAGE,
        self::TYPE_POST
    ];

    protected $table = "posts";

    protected $fillable = [
        'author_id',
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

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'post_id', 'id');
    }

    public function scopeFindBySlug($query, $slug, $type)
    {
        return $query->where('slug', '=', $slug)
            ->where('type', '=', $type);
    }

    public function scopeIsActive($query)
    {
        return $query->where('status', '=', self::STATUS_ACTIVE);
    }

    public function isPage(): bool
    {
        return $this->type === self::TYPE_PAGE;
    }

    public function isPost(): bool
    {
        return $this->type === self::TYPE_POST;
    }
}
