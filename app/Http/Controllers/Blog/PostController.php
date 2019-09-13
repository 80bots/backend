<?php

namespace App\Http\Controllers\Blog;

use App\Http\Resources\Blog\PostCollection;
use App\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Throwable;

class PostController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {
            $limit      = $request->query('limit') ?? self::PAGINATE;
            $search     = $request->input('search');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';

            $resource = Post::ajax();

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            }

            //
            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $posts  = (new PostCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $posts->meta ?? null;

            $response = [
                'data'  => $posts->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }
}
