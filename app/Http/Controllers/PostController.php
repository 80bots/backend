<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;
use Validator;

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
        $this->middleware('api.admin')->except('showBySlug');
    }

    /**
     * @param Request $request
     * @return JsonResponse
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
     * @return PostResource|JsonResponse
     */
    public function show(Request $request, $id)
    {
        try{
            $post = Post::find($id);
            if (empty($post)) {
                return $this->notFound(__('admin.not_found'), __('admin.posts.not_found'));
            }
            return $this->success((new PostResource($post))->toArray($request));
        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return PostResource|JsonResponse
     */
    public function showBySlug(Request $request)
    {
        try{

            $slug = $request->query('slug');

            $query = Post::query();

            if (Auth::check() && Auth::user()->isAdmin()) {
                $post = $query->findBySlug($slug)->first();
            } else {
                $post = $query->isActive()->findBySlug($slug)->first();
            }

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            return $this->success((new PostResource($post))->toArray($request));

        } catch (Throwable $throwable){
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {

            $rules = $this->getPostRules($request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error('Transferred data isn\'t valid', $validator->errors());
            }

            $slug = $request->input('slug');
            $data = $request->except(['slug']);

            $data = array_merge($data, [
                'author_id' => Auth::id(),
            ]);

            if ($data['type'] === Post::TYPE_PAGE) {
                $data = array_merge($data, [
                    'slug' => $slug
                ]);
            } else {
                $data = array_merge($data, [
                    'slug' => CommonHelper::slugify($data['title']),
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
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {

            $rules = $this->getPostRules($request);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->error('Transferred data isn\'t valid', $validator->errors());
            }

            $post = Post::find($id);

            if (empty($post)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.posts.not_found'));
            }

            $slug   = $request->input('slug');
            $update = $request->except(['slug']);

            if ($update['type'] === Post::TYPE_PAGE) {
                $update = array_merge($update, [
                    'slug' => $slug
                ]);
            } else {
                $update = array_merge($update, [
                    'slug' => CommonHelper::slugify($update['title'])
                ]);
            }

            if ($post->update($update)) {
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
     * @return JsonResponse
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

    /**
     * @param Request $request
     * @return array
     */
    private function getPostRules(Request $request): array
    {
        $type = $request->input('type');

        $rules = [
            'title'     => 'required|max:255',
            'content'   => 'required',
            'status'    => [
                'required',
                Rule::in(Post::STATUSES),
            ],
            'type'      => [
                'required',
                Rule::in(Post::TYPES),
            ],
        ];

        if ($type === Post::TYPE_PAGE) {
            $rules = array_merge($rules, [
                'slug' => 'required'
            ]);
        }

        return $rules;
    }
}
