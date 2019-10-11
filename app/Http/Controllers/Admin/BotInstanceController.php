<?php

namespace App\Http\Controllers\Admin;

use App\AwsAmi;
use App\AwsRegion;
use App\Events\InstanceStatusUpdated;
use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\BotInstanceCollection;
use App\Http\Resources\Admin\RegionCollection;
use App\Http\Resources\Admin\BotInstanceResource;
use App\Http\Resources\Admin\RegionResource;
use App\Http\Resources\Admin\S3ObjectCollection;
use App\Jobs\SyncBotInstances;
use App\Jobs\SyncRegions;
use App\S3Object;
use App\Services\Aws;
use App\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BotInstanceController extends AppController
{
    const PAGINATE = 1;
    const SYNC_LIMIT = 5;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return BotInstanceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $list   = $request->input('list');
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            //$resource = BotInstance::withTrashed()->with(['oneDetail', 'user', 'region'])->ajax();
            $resource = BotInstance::withTrashed()->ajax();

            // TODO: Add Filters

            if ($list === 'my') {
                $resource->findByUserId(Auth::id());
            }

            //
            if (! empty($search)) {
                $resource->where('bot_instances.tag_name', 'like', "%{$search}%")
                    ->orWhere('bot_instances.tag_user_email', 'like', "%{$search}%");
            }

            //
            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(BotInstance::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderBotInstance($query, BotInstance::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('aws_status', 'asc')->orderBy('start_time', 'desc');
            });

            //$resource->dd();

            $instances  = (new BotInstanceCollection($resource->paginate($limit)))->response()->getData();
            $meta       = $instances->meta ?? null;

            $response = [
                'data'  => $instances->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $resource = BotInstance::withTrashed()->find($id);
        if(!empty($resource)) {
            return $this->success((new BotInstanceResource($resource))->toArray($request));
        } else {
            return $this->error('Not found', __('admin.bots.not_found'));
        }
    }

    public function regions(Request $request)
    {
        $limit  = $request->query('limit') ?? self::PAGINATE;
        $search = $request->input('search');
        $sort   = $request->input('sort');
        $order  = $request->input('order') ?? 'asc';

        $resource = AwsRegion::onlyEc2()->ajax();

        //
        if (! empty($search)) {
            $resource->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }

        //
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

    public function updateRegion(Request $request, $id)
    {
        try {
            $update = $request->input('update');
            $region = AwsRegion::find($id);

            if (empty($region)) {
                return $this->notFound(__('admin.not_found'), __('admin.regions.not_found'));
            }

            $update = $region->update([
                'default_image_id' => $update['default_ami'] ?? ''
            ]);

            if ($update) {
                return $this->success(
                    (new RegionResource($region))->toArray($request),
                    __('admin.regions.update_success')
                );
            } else {
                return $this->error(__('admin.error'), __('admin.regions.update_error'));
            }
        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function syncRegions(Request $request)
    {
        try {
            dispatch(new SyncRegions(Auth::user()));
            return $this->success([], __('admin.regions.success_sync'));
        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

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

        return $this->error(__('admin.server_error'), __('admin.parameters_incorrect'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncInstances(Request $request)
    {
        try {
            dispatch(new SyncBotInstances($request->user()));
            return $this->success([], __('admin.instances.success_sync'));
        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $instance = BotInstance::find($id);

            if (empty($instance)) {
                return $this->notFound(__('admin.not_found'), __('admin.instances.not_found'));
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
                                return $this->error(__('admin.server_error'), __('admin.instances.not_updated'));
                            }

                        default:
                            return $this->error(__('admin.server_error'), __('admin.instances.not_updated'));
                    }
                }

            }

            return $this->error(__('admin.server_error'), __('admin.instances.not_updated'));

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
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

                        return $this->error(__('admin.error'), __('admin.instances.key_pair_not_found'));

                    } else {

                        $aws->s3Connection();

                        $result = $aws->getKeyPairObject($details->aws_pem_file_path ?? '');

                        $body = $result->get('Body');

                        if (! empty($body)) {
                            return response($body)->header('Content-Type', $result->get('ContentType'));
                        }

                        return $this->error(__('admin.error'), __('admin.error'));
                    }
                }

            } catch (Throwable $throwable){
                return $this->error(__('admin.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('admin.error'), __('admin.parameters_incorrect'));
    }

    public function getS3Objects(Request $request)
    {
        $instance = BotInstance::find($request->query('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('admin.not_found'), __('admin.instances.not_found'));
        }

        // Remove links from DB, which will be expired soon
        S3Object::removeOldLinks($instance->id);

        $limit  = $request->query('limit') ?? self::PAGINATE;
        $type   = InstanceHelper::getTypeS3Object($request->query('type'));
        $date   = $request->query('date');

        $resource = $instance->s3Objects()
            ->where('folder', '=', $date)
            ->where('type', '=', $type);

        if ($resource->count() === 0) {
            InstanceHelper::saveS3Objects($instance, $type, $date);
        }

        $instances  = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta       = $instances->meta ?? null;

        $response = [
            'data'  => $instances->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    public function getS3Logs(Request $request)
    {
        $instance = BotInstance::find($request->query('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('admin.not_found'), __('admin.instances.not_found'));
        }

        // Remove links from DB, which will be expired soon
        S3Object::removeOldLinks($instance->id);

        $limit  = $request->query('limit') ?? self::PAGINATE;

        $resource = $instance->s3Objects()
            ->where('type', '=', S3Object::TYPE_LOGS);

        if ($resource->count() === 0) {
            InstanceHelper::saveS3Logs($instance);
        }

        $instances  = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta       = $instances->meta ?? null;

        $response = [
            'data'  => $instances->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }
}
