<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscussionLikes extends Model
{
    protected $table = "discussion_likes";
    protected $fillable = [
        'user_id',
        'discussion_id'
    ];

    public function user() {
        return $this->belongsTo('App\User','user_id');
    }
}
