<?php

namespace App;

class Dislike extends BaseModel
{
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'dislike_post');
    }

    public function messages()
    {
        return $this->belongsToMany(Post::class, 'dislike_message');
    }
}
