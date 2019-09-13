<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\PostCollection;
use App\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Throwable;

class PostController extends Controller
{
    const PAGINATE = 1;

    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->query('search');
            $sort   = $request->query('sort');
            $order  = $request->query('order') ?? 'asc';

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

            $bots   = (new PostCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
