<?php

namespace App;

class Like extends BaseModel
{
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'like_post');
    }

    public function messages()
    {
        return $this->belongsToMany(Post::class, 'like_message');
    }
}
