<?php

namespace App\Http\Controllers\Blog;

use App\Http\Resources\Blog\MessageCollection;
use App\Message;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Throwable;

class MessageController extends Controller
{
    const PAGINATE = 10;

    public function index(Request $request)
    {

    }

    public function postMessages(Request $request, $postId)
    {
        try {

            $resource = Message::where('post_id', '=', $postId)
                ->where('status', '=', Message::STATUS_ACTIVE)
                ->isModerated()
                ->orderBy('created_at', 'asc');

            $messages   = (new MessageCollection($resource->paginate(self::PAGINATE)))->response()->getData();
            $meta       = $messages->meta ?? null;

            $response = [
                'data'  => $messages->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }
}
