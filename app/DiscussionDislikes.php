<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscussionDislikes extends Model
{
    protected $table = "discussion_dislikes";
    protected $fillable = [
        'user_id',
        'discussion_id'
    ];

    public function user() {
        return $this->belongsTo('App\User','user_id');
    }

    public function discussion() {
        return $this->belongsTo('DevDojo\Chatter\Models','discussion_id');
    }
}
