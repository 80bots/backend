<?php

namespace App\Http\Controllers\Admin;

use App\AwsAmi;
use App\AwsRegion;
use App\Helpers\InstanceHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\BotInstanceCollection;
use App\Http\Resources\Admin\BotInstanceResource;
use App\Services\Aws;
use App\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

            $resource = BotInstance::withTrashed()->ajax();

            // TODO: Add Filters

            switch ($list) {
                case 'my':
                    $resource->with('user')->findByUserId(Auth::id());
                    break;
                default:
                    $resource->with('user');
                    break;
            }

            //
            if (! empty($search)) {
                $resource->where('tag_name', 'like', "%{$search}%")
                    ->orWhere('aws_instance_id', 'like', "%{$search}%");
            }

            //
            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

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

    public function regions(Request $request)
    {
        $regions = AwsRegion::onlyEc2()->pluck('name', 'code')->toArray();

        return $this->success([
            'regions' => $regions
        ]);
    }

    public function amis(Request $request)
    {
        $region = $request->query('region');

        if (! empty($region)) {
            $amis = AwsAmi::where('aws_region_id', '=', $region)
                ->pluck('name', 'image_id')
                ->toArray();
            return $this->success([
                'amis' => $amis
            ]);
        }

        return $this->error(__('admin.server_error'), __('admin.parameters_incorrect'));
    }

    public function syncInstances()
    {
        try {

            Artisan::call('instance:sync');

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

                if (!empty($instance)) {
                    $aws = new Aws;
                    $aws->s3Connection();

                    $result = $aws->getKeyPairObject($instance->aws_pem_file_path ?? '');

                    $body = $result->get('Body');

                    if (! empty($body)) {
                        return response($body)->header('Content-Type', $result->get('ContentType'));
                    }

                    return $this->error(__('admin.error'), __('admin.error'));
                }
            } catch (Throwable $throwable){
                return $this->error(__('admin.server_error'), $throwable->getMessage());
            }
        }

        return $this->error(__('admin.error'), __('admin.parameters_incorrect'));
    }
}
