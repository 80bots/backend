<?php

namespace App\Http\Controllers\Admin;

use App\Bot;
use App\Helpers\CommonHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\BotCollection;
use App\Http\Resources\Admin\BotResource;
use App\Http\Resources\Admin\PlatformCollection;
use App\Platform;
use App\Tag;
use App\User;
use App\UserInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BotsController extends AppController
{
    const PAGINATE = 1;

    public $limit;

    /**
     * Display a listing of the resource.
     *
     * @return BotCollection|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {

            $resource = Bot::ajax();

            // TODO: Add Filters

            return new BotCollection($resource->paginate(self::PAGINATE));

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

            $bot->fill([
                'name'                  => $request->input('name'),
                'platform_id'           => $this->getPlatformId($request->input('platform')),
                'description'           => $request->input('description'),
                'aws_ami_image_id'      => $request->input('aws_ami_image_id'),
                'aws_ami_name'          => $request->input('aws_ami_name'),
                'aws_instance_type'     => $request->input('aws_instance_type'),
                'aws_startup_script'    => $request->input('aws_startup_script'),
                'aws_custom_script'     => $request->input('aws_custom_script'),
                'aws_storage_gb'        => $request->input('aws_storage_gb'),
            ]);

            if ($bot->save()) {
                $this->addTagsToBot($bot, $request->input('tags'));
                $this->addUsersToBot($bot, $request->input('users'));
                return $this->success([
                    'id' => $bot->id ?? null
                ], __('admin.bots.success_update'));
            }

            return $this->error(__('admin.server_error'), __('admin.bots.not_updated'));
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

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function changeStatus(Request $request)
    {
        try{

            $update = Bot::where('id', '=', $request->id ?? null)
                ->update(['status' => $request->input('status')]);

            if($update){
                return response()->json([
                    'error'     => false,
                    'message'   => ''
                ]);
            }

            return response()->json([
                'error'     => true,
                'message'   => 'Status Change Fail Please Try Again'
            ]);

        } catch (Throwable $throwable){
            return response()->json([
                'error'     => true,
                'message'   => $throwable->getMessage()
            ]);
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

        $userInstances = UserInstance::findByUserId($userId)->get();
        $bots = Bot::all();

        if (! $userInstances->count()) {
          session()->flash('error', 'Instance Not Found');
        }

        return view('admin.instance.my-bots', compact('userInstances', 'bots'));
    }

    /**
     * @param Bot $bot
     * @param string|null $input
     */
    private function addTagsToBot(Bot $bot, ?string $input): void
    {
        if (! empty($bot) && ! empty($input)) {

            $bot->tags()->detach();

            $tags = CommonHelper::explodeByComma($input);

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
     * @param string|null $input
     */
    private function addUsersToBot(Bot $bot, ?string $input): void
    {
        if (! empty($bot) && ! empty($input)) {
            $bot->users()->detach();
            $emails = CommonHelper::explodeByComma($input);
            $users  = User::whereIn('email', $emails)->pluck('id')->toArray();
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
