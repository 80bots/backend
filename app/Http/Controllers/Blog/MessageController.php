<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Message;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MessageController extends Controller
{
    const PAGINATE = 10;

    public function index(Request $request)
    {

    }

    public function store(Request $request)
    {
        try {

            if (! Auth::check()) {
                return $this->error(__('keywords.server_error'), __('auth.forbidden'));
            }

            $post = Post::find($request->input('post_id'));

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            $message = Message::create([
                'post_id'   => $request->input('post_id'),
                'parent_id' => $request->input('parent_id') ?? null,
                'author_id' => Auth::id(),
                'content'   => $request->input('content')
            ]);

            if (! empty($message)) {
                return $this->success();
            }

            return $this->error(__('keywords.server_error'), __('keywords.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    public function postMessages(Request $request, $postId)
    {
        try {

//            $resource = Message::where('post_id', '=', $postId)
//                ->where('status', '=', Message::STATUS_ACTIVE)
//                ->isModerated()
//                ->orderBy('created_at', 'asc');
//
//            $messages   = (new MessageCollection($resource->paginate(self::PAGINATE)))->response()->getData();
//            $meta       = $messages->meta ?? null;
//
//            $response = [
//                'data'  => $messages->data ?? [],
//                'total' => $meta->total ?? 0
//            ];
//
//            return $this->success($response);

            return $this->success();

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }
}
