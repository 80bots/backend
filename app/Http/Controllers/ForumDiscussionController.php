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
        $total = DiscussionLikes::where('discussion_id',$discussion_id)->count();

        $dislikedQuery = DiscussionDislikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);
        if($dislikedQuery->count()) {
            $dislike = $dislikedQuery->first();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'status' => 'Disliked Already',
                    'object' => $dislike,
                    'count' => $total
                ]
            ]);
        }

        $likedQuery = DiscussionLikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);

        if($likedQuery->count()) {
            $like = $likedQuery->first();
            $like->delete();
            $total = $total-1;
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'status' => 'Unliked',
                    'object' => $like,
                    'count' => $total
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
        $total = $total+1;
        return response()->json([
            'message' => 'success',
            'data' => [
                'status' => 'Liked',
                'object' => $like,
                'count' => $total
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
        $total = DiscussionDislikes::where('discussion_id',$discussion_id)->count();

        $likedQuery = DiscussionLikes::where('user_id',$user_id)->where('discussion_id',$discussion_id); 
        if($likedQuery->count()) {
            $like = $likedQuery->first();
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'status' => 'Liked Already',
                    'object' => $like,
                    'count' => $total
                ]
            ]);
        }

        $dislikedQuery = DiscussionDislikes::where('user_id',$user_id)->where('discussion_id',$discussion_id);
        if($dislikedQuery->count()) {
            $dislike = $dislikedQuery->first();
            $dislike->delete();
            $total = $total-1;
            return response()->json([
                'message' => 'failure',
                'data' => [
                    'status' => 'Removed Dislike',
                    'object' => $dislike,
                    'count' => $total
                ]
            ]);
        }
        
        $discussion = Models::discussion()->find($discussion_id);
        if($discussion->popularity >= 10) {
            $discussion->popularity = $discussion->popularity - 10;
        } else {
            $discussion->popularity = 0;
        }
        $discussion->save();
        $dislike = DiscussionDislikes::create([
            'discussion_id' => $discussion_id,
            'user_id' => $user_id
        ]);
        
        $total = $total+1;
        return response()->json([
            'message' => 'success',
            'data' => [
                'status' => 'Disliked',
                'object' => $dislike,
                'count' => $total
            ]
        ]);
    }
}