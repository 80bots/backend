<?php

namespace App\Http\Controllers;

use App\Bot;
use App\BotInstance;
use App\Helpers\GeneratorID;
use App\Http\Resources\BotCollection;
use App\Http\Resources\BotResource;
use App\Http\Resources\PlatformCollection;
use App\Http\Resources\TagCollection;
use App\Jobs\SyncLocalBots;
use App\Platform;
use App\Services\BotParser;
use App\Tag;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class BotController extends AppController
{
    const PAGINATE = 1;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit      = $request->query('limit') ?? self::PAGINATE;
            $platform   = $request->input('platform');
            $search     = $request->input('search');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';

            $resource = Bot::query();

            if (! empty($platform)) {
                $resource->whereHas('platform', function (Builder $query) use ($platform) {
                    $query->where('name', '=', $platform);
                });
            }

            if (! empty($search)) {
                $resource->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }

            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $bots   = (new BotCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('auth.forbidden'), $throwable->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return JsonResponse
     */
    public function create()
    {
        try{

            $platforms = (new PlatformCollection(Platform::get()))->response()->getData();

            return $this->success([
                'platforms' => $platforms->data ?? []
            ]);

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try{
            $content = $request['aws_custom_script'];
            $path = $request['path'];
            $name = $request['name'];
            $platform = $request['platform'];
            $random = GeneratorID::generate();
            $folderName = "{$random}_custom_bot";

            $parameters = $this->extractParamsFromScript($content);

            if(!$path) {
                $path = Str::slug($name, '_') . '.custom.js';
            }

            if($platform){
                $platform = $this->getPlatformId($request->input('platform'));
            }

            $bot = Bot::create([
                'platform_id'               => $platform,
                'name'                      => $name,
                'description'               => $request->input('description'),
                'parameters'                => $parameters,
                'path'                      => $path,
                'aws_custom_script'         => $request->input('aws_custom_script'),
                'aws_custom_package_json'   => $request->input('aws_custom_package_json'),
                'type'                      => $request->input('type'),
                's3_folder_name'            => $folderName,
            ]);

            if (empty($bot)) {
                return $this->error(__('user.server_error'), __('user.bots.error_create'));
            }

            $this->addTagsToBot($bot, $request->input('tags'));
            $this->addUsersToBot($bot, $request->input('users'));

            return $this->success([
                'id' => $bot->id ?? null
            ], __('user.bots.success_create'));

        } catch(Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return BotResource|JsonResponse
     */
    public function show(Request $request, $id)
    {
        try{
            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }

            return new BotResource($bot);

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param $id
     * @return JsonResponse
     */
    public function edit($id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
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
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }

            $active     = Bot::STATUS_ACTIVE;
            $inactive   = Bot::STATUS_INACTIVE;

            if (! empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status'                     => "in:{$active},{$inactive}",
                    'update.name'                       => 'string',
                    'update.description'                => 'string|nullable',
                    'update.aws_custom_script'          => 'string|nullable',
                    'update.aws_custom_package_json'    => 'json|nullable',
                    'update.platform'                   => 'string|nullable',
                    'update.tags'                       => 'array',
                    'update.type'                       => 'in:private,public',
                    'update.users'                      => 'array',
                ]);

                $updateData = $updateData['update'];

                $name = $request['update.name'];

                if(! empty($request['update.aws_custom_script'])) {
                    $updateData['parameters'] =  $parameters = $this->extractParamsFromScript($updateData['aws_custom_script']);
                    $updateData['path'] = Str::slug($name, '_') . '.custom.js';
                }

                if(! empty($request['update.platform'])){
                    $updateData['platform_id'] = $this->getPlatformId($updateData['platform']);
                }

                $bot->fill($updateData);

                if ($bot->save()) {
                    if(!empty($updateData['tags'])) $this->addTagsToBot($bot, $updateData['tags']);
                    if(!empty($updateData['users'])) $this->addUsersToBot($bot, $updateData['users']);
                    return $this->success((new BotResource($bot))->toArray($request));
                }
            }

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse|Response
     */
    public function destroy($id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }

            if ($bot->delete()) {
                return $this->success(null, __('user.bots.success_delete'));
            }

            return $this->error(__('user.error'), __('user.bots.not_deleted'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param null $platformId
     * @return Application|Factory|View
     */
    public function list($platformId = null)
    {
        if (! $platformId) {
            $this->limit = 5;
        }

        $platforms = new Platform;

        $platforms = $platforms->hasBots($this->limit, $platformId)->paginate(5);

        return view('user.bots.list', compact('platforms'));
    }

    /**
     * @return Application|Factory|View
     */
    public function mineBots()
    {
        $userId = Auth::id();

        $userInstances = BotInstance::findByUserId($userId)->get();
        $bots = Bot::all();

        if (! $userInstances->count()) {
            session()->flash('error', 'Instance Not Found');
        }

        return view('user.instance.my-bots', compact('userInstances', 'bots'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTags(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = Tag::where('status', '=', 'active');

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
     * @return JsonResponse
     */
    public function syncBots(Request $request)
    {
        try {
            dispatch(new SyncLocalBots($request->user()));
            return $this->success([], __('user.instances.success_sync'));
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
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

    /**
     * @param string $script
     * @return false|string|null
     */
    private function extractParamsFromScript (string $script) {
        $result = BotParser::getBotInfo($script);
        $i = 0;
        foreach($result['params'] as $key => $val) {
            $val->order = $i;
            $result['params']->$key = $val;
            $i++;
        }
        return $result && $result['params'] ? json_encode($result['params']) : null;
    }
}
