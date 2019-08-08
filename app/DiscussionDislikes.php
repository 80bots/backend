<?php

namespace App;

use DevDojo\Chatter\Models\Models;
use Illuminate\Database\Eloquent\Model;

class DiscussionDislikes extends Model
{
    protected $table = "discussion_dislikes";
    protected $fillable = [
        'user_id',
        'discussion_id'
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

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function discussion()
    {
        return $this->belongsTo(Models::class,'discussion_id');
    }
}
