<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MessageController extends Controller
{
    const PAGINATE = 10;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {

            if (! Auth::check()) {
                return $this->error(__('keywords.server_error'), __('auth.forbidden'));
            }

            $message = Message::create([
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postMessages(Request $request)
    {
        try {

            return $this->success();

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }
}
