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
        $likedQuery = DiscussionLikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);

        if($likedQuery->count()) {
            $like = $likedQuery->first();
            $like->delete();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'reason' => 'unlike',
                    'object' => $like,
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