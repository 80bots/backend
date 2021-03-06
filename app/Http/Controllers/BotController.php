<?php

namespace App\Http\Controllers;

use App\Bot;
use App\Helpers\GeneratorID;
use App\Helpers\S3BucketHelper;
use App\Http\Requests\BotCreateRequest;
use App\Http\Requests\BotUpdateRequest;
use App\Http\Resources\BotCollection;
use App\Http\Resources\BotResource;
use App\Http\Resources\TagCollection;
use App\Jobs\SyncLocalBots;
use App\Tag;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $search     = $request->input('search');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';

            $resource = Bot::query();

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
     * Store a newly created resource in storage.
     *
     * @param BotCreateRequest $request
     * @return JsonResponse
     */
    public function store(BotCreateRequest $request)
    {
        try{
            $data                   = $request->validated();
            $name                   = $data['name'];
            $path                   = $data['path'] ?? null;
            $custom_script          = $data['aws_custom_script'];
            $parameters             = $data['parameters'] ?? null;

            $random                 = GeneratorID::generate();
            $folderName             = "scripts/{$random}";

            if(!empty($custom_script)) {
                $parameters = S3BucketHelper::extractParamsFromScript($custom_script);
            }

            if(empty($path)) {
                $path = Str::slug($name, '_') . '.custom.js';
            }

            $bot = Bot::create([
                'name'              => $name,
                'description'       => $data['description'],
                'parameters'        => $parameters,
                'path'              => $path,
                's3_path'           => $folderName,
                'type'              => $data['type'],
            ]);

            if (empty($bot)) {
                return $this->error(__('user.server_error'), __('user.bots.error_create'));
            }

            S3BucketHelper::updateOrCreateFilesS3(
                $bot,
                Storage::disk('s3'),
                $custom_script,
                $data['aws_custom_package_json'],
            );

            $this->addTagsToBot($bot, $data['tags']);
            $this->addUsersToBot($bot, $data['users']);

            return $this->success([
                'id'                => $bot->id ?? null
            ], __('user.bots.success_create'));

        } catch(Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BotUpdateRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(BotUpdateRequest $request, $id)
    {
        Log::debug("+++++++++++++++++update+++++++++++++++  {$id}");
        try{
            $bot                    = Bot::find($id);

            Log::debug("bot {$bot}");

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }

            $data                   = $request->validated();
            $updateData             = $data['update'];
            $custom_script          = $updateData['aws_custom_script'];
            $name                   = $updateData['name'];
            $path                   = $updateData['path'] ?? null;
            $parameters             = $updateData['parameters'] ?? null;
            $tags                   = $updateData['tags'];
            $users                  = $updateData['users'];
            $folderName             = $bot->s3_path;

            if(!empty($custom_script)) {
                $parameters = S3BucketHelper::extractParamsFromScript($custom_script);
            }

            if(empty($path)) {
                $path = Str::slug($name, '_') . '.custom.js';
            }

            $bot->fill([
                'name'              => $name,
                'description'       => $updateData['description'],
                'parameters'        => $parameters,
                'path'              => $path,
                's3_path'           => $folderName,
                'status'            => $updateData['status'],
                'type'              => $updateData['type'],
            ]);

            if ($bot->save()) {

                S3BucketHelper::updateOrCreateFilesS3(
                    $bot,
                    Storage::disk('s3'),
                    $custom_script,
                    $updateData['aws_custom_package_json']
                );
                Log::debug("script updated to s3");
                if(!empty($tags)) $this->addTagsToBot($bot, $tags);
                Log::debug("addTagsToBot");
                if(!empty($users)) $this->addUsersToBot($bot, $users);
                Log::debug("addUsersToBot");
                return $this->success((new BotResource($bot))->toArray($request));
            }
        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        try{
            $bot = Bot::findOrFail($id);
            if(!$bot) {
                $this->error('Not found', __('bots.not_found'));
            }

            return $this->success((new BotResource($bot))->toArray($request));
        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Update status the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try{
            $bot = Bot::find($id);

            if (empty($bot)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }

            $bot->fill($request['update']);

            if ($bot->save()) {
                return $this->success((new BotResource($bot))->toArray($request));
            }
        } catch (Throwable $throwable) {
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
                S3BucketHelper::deleteFilesS3(
                    $bot->s3_path
                );
                return $this->success(null, __('user.bots.success_delete'));
            }

            return $this->error(__('user.error'), __('user.bots.not_deleted'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
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

            if (!empty($search)) {
                $resource->where('name', 'like', "%{$search}%");
            }

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
}
