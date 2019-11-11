<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PostController extends Controller
{
    const PAGINATE = 1;

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('api.admin')->except('show');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->query('search');
            $sort   = $request->query('sort');
            $order  = $request->query('order') ?? 'asc';

            $resource = Post::query();

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            }

            //
            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            } else {
                $resource->orderBy('created_at', 'desc');
            }

            $posts  = (new PostCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $posts->meta ?? null;

            $response = [
                'data'  => $posts->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return PostResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try{

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            return new PostResource($post);

        } catch (Throwable $throwable){
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {

            $url        = $request->input('url');
            $status     = $request->input('status');
            $type       = $request->input('type');
            $title      = $request->input('title');
            $content    = $request->input('content');

            if (! in_array($status, Post::STATUSES)) {
                $status = Post::STATUS_DRAFT;
            }

            if (! in_array($type, Post::TYPES)) {
                $status = Post::TYPE_POST;
            }

            $data = [
                'author_id' => Auth::id(),
                'title'     => $title ?? 'Without title',
                'slug'      => CommonHelper::slugify($title),
                'content'   => $content,
                'status'    => $status,
                'type'      => $type
            ];

            if (! empty($url) && $type === Post::TYPE_PAGE) {
                $data = array_merge($data, [
                    'url'  => $url,
                    'slug' => $url,
                ]);
            }

            $post = Post::create($data);

            if (! empty($post)) {
                return $this->success();
            }

            return $this->error(__('keywords.server_error'), __('keywords.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            $data = $request->input('update');
            $data['slug'] = CommonHelper::slugify($data['title'] ?? '');

            $update = $post->update($data);

            if ($update) {
                return $this->success((new PostResource($post))->toArray($request));
            }

            return $this->error(__('keywords.server_error'), __('keywords.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        try {

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            if ($post->delete()) {
                return $this->success();
            }

            return $this->error(__('keywords.server_error'), __('keywords.server_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    public function showBySlug(Request $request, $slug)
    {

    }
}
