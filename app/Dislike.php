<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dislike extends Model
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
