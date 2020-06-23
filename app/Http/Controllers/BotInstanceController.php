<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\BotInstance;
use App\Events\InstanceStatusUpdated;
use App\Helpers\ApiResponse;
use App\Helpers\QueryHelper;
use App\Http\Resources\BotInstanceCollection;
use App\Http\Resources\BotInstanceResource;
use App\Services\Aws;
use App\Services\GitHub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BotInstanceController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = BotInstance::withTrashed()->findByUserId(Auth::id());

            if (! empty($search)) {
                $resource->where('bot_instances.tag_name', 'like', "%{$search}%")
                    ->orWhere('bot_instances.tag_user_email', 'like', "%{$search}%");
            }

            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(BotInstance::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBotInstance($query, BotInstance::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
            });

            $bots   = (new BotInstanceCollection($resource->paginate($limit)))->response()->getData();
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
    public function regions(Request $request)
    {
        $regions = AwsRegion::onlyEc2()->pluck('id', 'name')->toArray();
        $result = [];

        foreach ($regions as $name => $id) {
            array_push($result, ['name' => $name, 'id' => $id]);
        }

        return $this->success([
            'data' => $result
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function create()
    {
        return $this->success();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        return $this->success();
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $id) {
        $resource = BotInstance::withTrashed()->find($id);
        if(!empty($resource)) {
            return $this->success((new BotInstanceResource($resource))->toArray($request));
        } else {
            return $this->error('Not found', __('admin.bots.not_found'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param BotInstance $userInstances
     * @return JsonResponse
     */
    public function edit(BotInstance $userInstances)
    {
        return $this->success();
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
        try {

            $instance = BotInstance::where([
                ['id', '=', $id],
                ['user_id', '=', Auth::id()]
            ])->first();

            if (empty($instance)) {
                return $this->notFound(__('user.not_found'), __('user.instances.not_found'));
            }

            $running    = BotInstance::STATUS_RUNNING;
            $stopped    = BotInstance::STATUS_STOPPED;
            $terminated = BotInstance::STATUS_TERMINATED;

            if (! empty($request->input('update'))) {
                $updateData = $request->validate([
                    'update.status' => "in:{$running},{$stopped},{$terminated}"
                ]);

                foreach ($updateData['update'] as $key => $value) {
                    switch ($key) {
                        case 'status':

                            if ($this->changeStatus($value, $id)) {

                                $instance = new BotInstanceResource(BotInstance::withTrashed()
                                    ->where('id', '=', $id)->first());

                                broadcast(new InstanceStatusUpdated(Auth::id()));

                                return $this->success($instance->toArray($request));
                            } else {
                                return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                            }

                            break;
                        default:
                            return $this->error(__('user.server_error'), __('user.instances.not_updated'));
                            break;
                    }
                }

            }

            return $this->error(__('user.server_error'), __('user.instances.not_updated'));

        } catch (Throwable $throwable){
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  BotInstance  $userInstances
     * @return JsonResponse
     */
    public function destroy(BotInstance $userInstances)
    {
        return $this->success();
    }

    /**
     * @param Request $request
     * @param $id
     * @return ApiResponse
     */
    public function reportIssue(Request $request, $id)
    {
        $instance = BotInstance::withTrashed()->find($id);
        $aws = new Aws();
        if(!empty($instance)) {
            $resource = new BotInstanceResource($instance);
            if(!empty($request->screenshots)) {
                $instanceId = $resource->toArray($request)['instance_id'];
                $botName = $resource->toArray($request)['bot_name'];
                $urls = $aws->uploadScreenshots($instanceId, $request->screenshots);

                $body = "User: {$request->user()->email}\nInstance ID: {$instanceId}\nBot Name: {$botName}
                \nMessage: {$request->message}";

                if(!empty($urls)) {
                    $screenshots = '';
                    for($i = 0; $i < count($urls); $i++) {
                        $screenshots = $screenshots . " ![{$request->screenshots[$i]->getClientOriginalName()}]({$urls[$i]})";
                    }
                    $body = $body . "\n{$screenshots}";
                }

                GitHub::createIssue('Issue Report', $body);
            }
            return $this->success([]);
        } else {
            return $this->error('Not found', __('admin.bots.not_found'));
        }
    }
}
