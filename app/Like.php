<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
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
