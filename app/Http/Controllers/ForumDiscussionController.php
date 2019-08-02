<?php

namespace App\Http\Controllers;

use App\DiscussionDislikes;
use App\DiscussionLikes;
use DevDojo\Chatter\Controllers\ChatterDiscussionController as ChatterDiscussionController;
use DevDojo\Chatter\Models\Models;
use Illuminate\Support\Facades\Auth;

class ForumDiscussionController extends ChatterDiscussionController
{
    /**
     * create the like by authenticated user.
     *
     * @param int $discussion_id
     * @return \Illuminate\Http\Response
     */
    public function likeDiscussion(int $discussion_id)
    {
        $user_id = Auth::id();
        $total = DiscussionLikes::where('discussion_id',$discussion_id)->count();

        $dislikedQuery = DiscussionDislikes::where('user_id', $user_id)->where('discussion_id',$discussion_id);
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

        $discussion = Models::discussion()->find($discussion_id);

        if($likedQuery->count()) {
            $like = $likedQuery->first();
            $discussion->popularity = $discussion->popularity - $like->getDecayedValueOfLike();
            $discussion->save();
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
     * @param int $discussion_id
     * @return \Illuminate\Http\Response
     */
    public function dislikeDiscussion(int $discussion_id)
    {
        $user_id = Auth::id();
        $total = DiscussionDislikes::where('discussion_id', $discussion_id)->count();

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
