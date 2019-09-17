<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\PostCollection;
use App\Http\Resources\Admin\PostResource;
use App\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

    public function store(Request $request)
    {
        try {

            switch ($request->input('status')) {
                case Post::STATUS_DRAFT:
                case Post::STATUS_ACTIVE:
                case Post::STATUS_INACTIVE:
                    $status = $request->input('status');
                    break;
                default:
                    $status = Post::STATUS_DRAFT;
                    break;
            }

            switch ($request->input('type')) {
                case Post::TYPE_BOT:
                case Post::TYPE_POST:
                    $type = $request->input('type');
                    break;
                default:
                    $type = Post::TYPE_BOT;
                    break;
            }

            $post = Post::create([
                'author_id' => Auth::id(),
                'title'     => $request->input('title') ?? 'Without title',
                'bot_id'    => $request->input('bot_id'),
                'slug'      => '',
                'content'   => $request->input('content'),
                'status'    => $status,
                'type'      => $type
            ]);

            if (! empty($post)) {
                return $this->success();
            }

            return $this->error(__('admin.server_error'), __('admin.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('admin.not_found'), __('admin.posts.not_found'));
            }

            $update = $post->update($request->input('update'));

            if ($update) {
                return $this->success((new PostResource($post))->toArray($request));
            }

            return $this->error(__('admin.server_error'), __('admin.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function delete(Request $request, $id)
    {
        try {

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('admin.not_found'), __('admin.bots.not_found'));
            }

            if ($post->delete()) {
                return $this->success();
            }

            return $this->error(__('admin.server_error'), __('admin.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
