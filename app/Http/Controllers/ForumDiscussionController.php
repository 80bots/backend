<?php

namespace App\Http\Controllers;

use Auth;
use App\DiscussionLikes;
use App\DiscussionDislikes;
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

        $dislikedQuery = DiscussionDislikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);
        if($dislikedQuery->count()) {
            $dislike = $dislikedQuery->first();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'reason' => 'removed dislike',
                    'object' => $dislike,
                ]
            ]);
        }

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

    /**
     * create the dislike by authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function dislikeDiscussion(Int $discussion_id)
    {
        $user_id = Auth::user()->id;
        $likedQuery = DiscussionLikes::where('user_id',$user_id)->where('discussion_id',$discussion_id); 
        if($likedQuery->count()) {
            $like = $likedQuery->first();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'reason' => 'liked already',
                    'object' => $like,
                ]
            ]);
        }

        $dislikedQuery = DiscussionDislikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);
        if($dislikedQuery->count()) {
            $dislike = $dislikedQuery->first();
            $dislike->delete();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'reason' => 'removed dislike',
                    'object' => $dislike,
                ]
            ]);
        }

        
        
        $discussion = Models::discussion()->find($discussion_id);
        if($discussion->popularity > 10) $discussion->popularity = $discussion->popularity - 10;
        $discussion->save();
        $dislike = DiscussionDislikes::create([
            'discussion_id' => $discussion_id,
            'user_id' => $user_id
        ]);
        
        return response()->json([
            'message' => 'success',
            'data' => [
                'like' => $dislike,
            ]
        ]);
    }
}