<?php

namespace App\Http\Controllers;

use App\AwsAmi;
use App\AwsRegion;
use App\Helpers\InstanceHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Common\Instances\InstanceController;
use App\Http\Resources\Admin\RegionCollection;
use App\Http\Resources\Admin\RegionResource;
use App\Jobs\SyncBotInstances;
use App\Services\Aws;
use App\BotInstance;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
}
