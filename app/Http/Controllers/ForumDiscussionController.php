<?php

namespace App\Http\Controllers;

use Auth;
use App\DiscussionLikes;
use Illuminate\Http\Request;
use DevDojo\Chatter\Models\Models;
use DevDojo\Chatter\Controllers\ChatterDiscussionController as ChatterDiscussionController;

class ForumDiscussionController extends ChatterDiscussionController
{
    /**
     * create the like by authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function likeDiscussion(Int $discussion_id)
    {
        $user_id = Auth::user()->id;
        $liked = DiscussionLikes::where('user_id',$user_id)->where('discussion_id',$discussion_id)->count();

        if($liked) {
            return response()->json([
                'message' => 'failed',
                'data' => [
                    'like' => 0,
                ]
            ]);
        }
        
        $discussion = Models::discussion()->find($discussion_id);
        $discussion->popularity = $discussion->popularity + 10;
        $discussion->save();
        $like = DiscussionLikes::create([
            'discussion_id' => $discussion_id,
            'user_id' => $user_id
        ]);
        
        return response()->json([
            'message' => 'success',
            'data' => [
                'like' => $like,
            ]
        ]);
    }

}