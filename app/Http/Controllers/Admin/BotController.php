<?php

namespace App\Http\Controllers\Admin;

use App\Bot;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\BotCollection;
use App\Http\Resources\Admin\BotResource;
use App\Http\Resources\Admin\PlatformCollection;
use App\Http\Resources\Admin\TagCollection;
use App\Jobs\SyncLocalBots;
use App\Platform;
use App\Tag;
use App\User;
use App\BotInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Support\Facades\Artisan;

class BotController extends AppController
{
    const PAGINATE = 1;

    public $limit;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return BotCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit      = $request->query('limit') ?? self::PAGINATE;
            $platform   = $request->input('platform'); // TODO: ???
            $search     = $request->input('search');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';

            $resource = Bot::ajax();

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('bots.name', 'like', "%{$search}%")
                    ->orWhere('bots.description', 'like', "%{$search}%");
            }

            //
            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(Bot::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBot($query, Bot::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('name', 'asc');
                }
            }, function ($query) {
                return $query->orderBy('name', 'asc');
            });

            $bots   = (new BotCollection($resource->paginate($limit)))->response()->getData();
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

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        try{

            $platforms = (new PlatformCollection(Platform::get()))->response()->getData();

            return $this->success([
                'platforms' => $platforms->data ?? []
            ]);

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try{

            $bot = Bot::create([
                'name'                  => $request->input('name'),
                'platform_id'           => $this->getPlatformId($request->input('platform')),
                'description'           => $request->input('description'),
                'aws_ami_image_id'      => $request->input('aws_ami_image_id'),
                'aws_ami_name'          => $request->input('aws_ami_name'),
                'aws_instance_type'     => $request->input('aws_instance_type'),
                'aws_startup_script'    => $request->input('aws_startup_script'),
                'aws_custom_script'     => $request->input('aws_custom_script'),
                'aws_storage_gb'        => $request->input('aws_storage_gb'),
                'type'                  => $request->input('type')
            ]);

            if (empty($bot)) {
                return $this->error(__('admin.server_error'), __('admin.bots.error_create'));
            }

            $this->addTagsToBot($bot, $request->input('tags'));
            $this->addUsersToBot($bot, $request->input('users'));

            return $this->success([
                'id' => $bot->id ?? null
            ], __('admin.bots.success_create'));

        } catch(Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return BotResource
     */
    public function show(Request $request, $id)
    {
        try{
            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('admin.not_found'), __('admin.bots.not_found'));
            }

            return new BotResource($bot);

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('admin.not_found'), __('admin.bots.not_found'));
            }

            $platforms  = (new PlatformCollection(Platform::get()))->response()->getData();
            $resource   = (new BotResource($bot))->response()->getData();

            return $this->success([
                'bot'       => $resource->data ?? null,
                'tags'      => implode(', ', $bot->tags()->pluck('name')->toArray()),
                'users'     => implode(', ', $bot->users()->pluck('email')->toArray()),
                'platforms' => $platforms->data ?? [],
            ]);

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('admin.not_found'), __('admin.bots.not_found'));
            }

            $active     = Bot::STATUS_ACTIVE;
            $inactive   = Bot::STATUS_INACTIVE;

            if (! empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status'             => "in:{$active},{$inactive}",
                    'update.name'               => 'string',
                    'update.aws_custom_script'  => 'string|nullable',
                    'update.description'        => 'string',
                    'update.platform'           => 'string',
                    'update.tags'               => 'array',
                    'update.type'               => 'in:private,public',
                    'update.users'              => 'array',
                ]);

                $updateData = $updateData['update'];

                $bot->fill($updateData);

                if ($bot->save()) {
                    if(!empty($updateData['tags'])) $this->addTagsToBot($bot, $updateData['tags']);
                    if(!empty($updateData['users'])) $this->addUsersToBot($bot, $updateData['users']);
                    return $this->success((new BotResource($bot))->toArray($request));
                }
            }

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('admin.not_found'), __('admin.bots.not_found'));
            }

            if ($bot->delete()) {
                return $this->success(null, __('admin.bots.success_delete'));
            }

            return $this->error(__('admin.error'), __('admin.bots.not_deleted'));

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function list($platformId = null)
    {
        if (! $platformId) {
          $this->limit = 5;
        }

        $platforms = new Platform;

        $platforms = $platforms->hasBots($this->limit, $platformId)->paginate(5);

        return view('admin.bots.list',compact('platforms'));
    }

    public function mineBots()
    {
        $userId = Auth::id();
        $instancesId = [];

        $userInstances = BotInstance::findByUserId($userId)->get();
        $bots = Bot::all();

        if (! $userInstances->count()) {
          session()->flash('error', 'Instance Not Found');
        }

        return view('admin.instance.my-bots', compact('userInstances', 'bots'));
    }

    public function getTags(Request $request) {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = Tag::where('status', '=', 'active');

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('name', 'like', "%{$search}%");
            }

            //
            if (!empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $bots   = (new TagCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncBots(Request $request)
    {
        try {
            dispatch(new SyncLocalBots($request->user()));
            return $this->success([], __('admin.instances.success_sync'));
        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Bot $bot
     * @param array|null $tags
     */
    private function addTagsToBot(Bot $bot, ?array $tags): void
    {
        if (! empty($bot) && ! empty($tags)) {

            $bot->tags()->detach();

            $tagsIds = [];

            foreach ($tags as $tag){

                $tagObj = Tag::findByName($tag);

                if (empty($tagObj)) {
                    $tagObj = Tag::create([
                        'name' => $tag
                    ]);
                }

                $tagsIds[] = $tagObj->id ?? null;
            }

            $bot->tags()->attach($tagsIds);
        }
    }

    /**
     * @param Bot $bot
     * @param array|null $input
     */
    private function addUsersToBot(Bot $bot, ?array $input): void
    {
        if (! empty($bot) && ! empty($input)) {
            $bot->users()->detach();
            $users  = User::whereIn('id', $input)->pluck('id')->toArray();
            $bot->users()->sync($users);
        }
    }

    /**
     * @param string|null $name
     * @return int|null
     */
    private function getPlatformId(?string $name): ?int
    {
        $platform = Platform::findByName($name)->first();

        if (empty($platform)) {
            $platform = Platform::create([
                'name' => $name
            ]);
        }

        return $platform->id ?? null;
    }
}
