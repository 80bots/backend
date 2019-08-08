<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\InstanceHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\UserInstanceCollection;
use App\Services\Aws;
use App\UserInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotInstancesController extends AppController
{
    const PAGINATE = 1;
    const SYNC_LIMIT = 5;

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return UserInstanceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $resource = UserInstance::ajax();

            // TODO: Add Filters

            switch ($request->input('list')) {
                case 'my_bots':
                    $resource->with('user')->findByUserId(Auth::id());
                    break;
                default:
                    $resource->with('user');
                    break;
            }

            return new UserInstanceCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    public function syncInstances()
    {
        try {

            Log::info('Sync started at ' . date('Y-m-d h:i:s'));

            $aws    = new Aws;
            $limit  = self::SYNC_LIMIT;
            $token  = '';

            do
            {
                $instancesByStatus = $aws->sync($limit, $token);
                $token = $instancesByStatus['nextToken'] ?? '';

                InstanceHelper::syncInstances($instancesByStatus['data']);

            } while(! empty($instancesByStatus['nextToken']));

            return $this->success([], __('admin.instances.success_sync'));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
