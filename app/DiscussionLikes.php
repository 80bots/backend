<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DiscussionLikes extends Model
{
    protected $table = "discussion_likes";
    protected $fillable = [
        'user_id',
        'discussion_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

    public function getDecayedValueOfLike()
    {
        $result = DB::selectOne(DB::raw('SELECT (10 * EXP( -('.config('chatter.discussions_hotness.decay_rate').') * time_to_sec(timediff(NOW(), \'' . $this->created_at . '\')) / 3600 )) AS newpopularity'));
        return $result->newpopularity ?? 0;
    }
}
