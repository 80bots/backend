<?php

namespace App\Http\Controllers;

use App\AwsAmi;
use App\AwsRegion;
use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Common\Instances\InstanceController;
use App\Http\Resources\RegionCollection;
use App\Http\Resources\RegionResource;
use App\Jobs\SyncBotInstances;
use App\Services\Aws;
use App\Bot;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\BotResource;
use App\Http\Resources\BotInstaResource;
use Illuminate\Support\Facades\Log;
use App\BotInstance;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\BotInstanceUpdateRequest;
use App\Helpers\S3BucketHelper;
use Illuminate\Support\Str;
use App\Helpers\GeneratorID;
use App\Jobs\UpdateScriptRestartBot;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BotInstanceController extends InstanceController
{
    const PAGINATE = 1;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function regions(Request $request)
    {
        $limit  = $request->query('limit') ?? self::PAGINATE;
        $search = $request->input('search');
        $sort   = $request->input('sort');
        $order  = $request->input('order') ?? 'asc';

        $resource = AwsRegion::onlyEc2();

        if (! empty($search)) {
            $resource->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }

        $resource->when($sort, function ($query, $sort) use ($order) {
            if (! empty(AwsRegion::ORDER_FIELDS[$sort])) {
                return QueryHelper::orderAwsRegion($query, AwsRegion::ORDER_FIELDS[$sort], $order);
            } else {
                return $query->orderBy('name', 'asc');
            }
        }, function ($query) {
            return $query->orderBy('name', 'asc');
        });

        $regions    = (new RegionCollection($resource->paginate($limit)))->response()->getData();
        $meta       = $regions->meta ?? null;

        $response = [
            'data'  => $regions->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateRegion(Request $request, $id)
    {
        try {
            $update = $request->input('update');
            $region = AwsRegion::find($id);

            if (empty($region)) {
                return $this->notFound(__('user.not_found'), __('user.regions.not_found'));
            }

            $update = $region->update([
                'default_image_id' => $update['default_ami'] ?? ''
            ]);

            if ($update) {
                return $this->success(
                    (new RegionResource($region))->toArray($request),
                    __('user.regions.update_success')
                );
            } else {
                return $this->error(__('user.error'), __('user.regions.update_error'));
            }
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function amis(Request $request)
    {
        $region = $request->query('region');

        if (! empty($region)) {
            $amis = AwsAmi::where('aws_region_id', '=', $region)
                ->pluck('name', 'image_id')
                ->toArray();
            $result = [];
            foreach ($amis as $id => $name) {
                $result[] = ['id' => $id, 'name' => $name];
            }
            return $this->success([
                'data' => $result
            ]);
        }

        return $this->error(__('user.server_error'), __('user.parameters_incorrect'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function syncInstances(Request $request)
    {
        try {
            dispatch(new SyncBotInstances($request->user()));
            return $this->success([], __('user.instances.success_sync'));
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return ResponseFactory|JsonResponse|Response
     */
    public function getInstancePemFile(Request $request)
    {
        $instance = $request->query('instance');

        if (! empty($instance)) {

            try {

                $instance = BotInstance::find($instance);

                if (! empty($instance)) {

                    $details    = $instance->details()->latest()->first();
                    $aws        = new Aws;

                    $describeInstancesResponse = $aws->describeInstances(
                        [$instance->aws_instance_id ?? null],
                        $instance->region->code
                    );

                    if (! $describeInstancesResponse->hasKey('Reservations') || InstanceHelper::checkTerminatedStatus($describeInstancesResponse)) {

                        $instance->setAwsStatusTerminated();

                        if ($instance->region->created_instances > 0) {
                            $instance->region->decrement('created_instances');
                        }

                        InstanceHelper::cleanUpTerminatedInstanceData($aws, $details);

                        return $this->error(__('user.error'), __('user.instances.key_pair_not_found'));

                    } else {

                        $aws->s3Connection();

                        $result = $aws->getKeyPairObject($details->aws_pem_file_path ?? '');

                        if (empty($result)) {
                            return $this->error(__('user.error'), __('user.access_denied'));
                        }

                        $body = $result->get('Body');

                        if (! empty($body)) {
                            return response($body)->header('Content-Type', $result->get('ContentType'));
                        }

                        return $this->error(__('user.error'), __('user.error'));
                    }
                }

            } catch (Throwable $throwable){
                return $this->error(__('user.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('user.error'), __('user.parameters_incorrect'));
    }

    public function show(Request $request, $id)
    {
        Log::debug("get botinstancde id {$id}");
        try{

            $botInstance = BotInstance::findOrFail($id);
            if(!$botInstance) {
                Log::debug("Invalid botinstance id : {$id}");
                $this->error('Not found', __('botinstance.not_found'));
            }
            

           // Log::debug("botInstance    {$botInstance}");
            if(!$botInstance->path || !$botInstance->s3_path){
               // Log::debug("path is null find script from bot table  {$botInstance->bot_id} ");
                $bot = Bot::findOrFail($botInstance->bot_id);
                ///Log::debug("bot {$bot}");
                if(!$bot) {
                    $this->error('Not found', __('bots.not_found'));
                }
                $parameters = $bot->parameters;
                $path = $bot->path;
                $s3_path  = $bot->s3_path;
                $botInstance->parameters = $parameters;
                $botInstance->path = $path;
                $botInstance->s3_path = $s3_path;

                Log::debug("botInstance {$botInstance}");
                //return $this->success((new BotResource($bot))->toArray($request));
                return $this->success((new BotInstaResource($botInstance))->toArray($request));
            }else {
                Log::debug("botInstance {$botInstance}");
                Log::debug("path is not null fetch bot data from botinstance table");
                return $this->success((new BotInstaResource($botInstance))->toArray($request));
            }

            
        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

     /**
     * Update the specified resource in storage.
     *
     * @param BotInstanceUpdateRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function updateInstance(BotInstanceUpdateRequest $request, $id)
    {
        //Log::debug("update botinstance {$id}");
        try{
            $botInstance        = BotInstance::find($id);

            if (empty($botInstance)) {
                return $this->notFound(__('user.not_found'), __('user.bots.not_found'));
            }
            Log::debug("botId  {$botInstance->bot_id}");
            $bot = Bot::find($botInstance->bot_id);
            $data                   = $request->validated();
            Log::debug("validated data ". json_encode($data));
            $updateData             = $data['update'];
            Log::debug("updateData ". json_encode($updateData));
            $custom_script          = $updateData['aws_custom_script'];
            Log::debug("custom_script ". $custom_script);
            $path                   = $updateData['path'] ?? null;
            $parameters             = $updateData['parameters'] ?? null;
            
            $folderName             = $botInstance->s3_path;
            Log::debug("folderName {$folderName}");
            if(empty($folderName)){
                $random                 = GeneratorID::generate();
                $folderName             = "scripts/{$random}";
            }
            Log::debug("folderName {$folderName}");
            $name                   = $bot->name;
            if(!empty($custom_script)) {
                Log::debug("folderName {$folderName}");
                $parameters = S3BucketHelper::extractParamsFromScript($custom_script);
                Log::debug("parameters2 {$parameters}");
            }
            
            Log::debug("name {$name}  path {$path}");
            if(empty($path)) {
                Log::debug("path is null");
                $path = Str::slug($name, '_') . '.custom.js';
                Log::debug("path  {$path} ");
            }
            Log::debug(" botInstance {$botInstance} ");
            $botInstance->parameters = $parameters;
            $botInstance->path = $path;
            $botInstance->s3_path = $folderName;
            Log::debug(" botInstance {$botInstance} ");
           
            if ($botInstance->save()) {
              
                S3BucketHelper::updateOrCreateFilesS3BotInstance(
                    $botInstance,
                    Storage::disk('s3'),
                    $custom_script,
                    $updateData['aws_custom_package_json']
                );
                Log::debug("Script updated");
                return $this->success((new BotInstaResource($botInstance))->toArray($request));
            }else{
                Log::debug("bot instance not saved");

            }
        } catch (Throwable $throwable){
            Log::debug("Error while updating botinstance {$throwable->getMessage()}");
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param $id 
     * @param $params
     * @return JsonResponse
     */
    public function restart(Request $request)
    {
        Log::debug("Restart Bot Instance Start!". json_encode($request->input('params')));
        $botInstanceId = null;
        try{
            $instanceId = $request->input('id');
            Log::debug("botInstanceId {$instanceId}");
            $botInstance = BotInstance::find($instanceId);
            if(!$botInstance){
                return $this->error(__('keywords.not_found'), __('keywords.botinstance.not_found'));
            }
            $params = collect($request->input('params'));
            Log::debug("params+++++ " . print_r($params));
            if ($params->isEmpty()) {
                return $this->error(__('keywords.error'), __('keywords.instance.parameters_incorrect'));
            }
            $user = User::find(Auth::id()); // Get "App\User" object
            foreach ($params as $param) {
                Log::debug("param ". json_encode($param));
                dispatch(new UpdateScriptRestartBot( $botInstance, $user, $param, $request->ip()));
            }
            return $this->success([
                'instance_id' => $botInstanceId ?? null
            ], __('keywords.instance.restart_success'));


        } catch (Throwable $throwable){
            Log::debug("Error while restarting bot instance {$throwable->getMessage()}");
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }
}
