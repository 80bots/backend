<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\InstanceSessionsHistoryCollection;
use App\InstanceSessionsHistory;
use Illuminate\Http\Request;
use Throwable;

class InstanceSessionHistoriesController extends AppController
{
    const PAGINATE = 1;

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return InstanceSessionsHistoryCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $resource = InstanceSessionsHistory::ajax();

            // TODO: Add Filters
            $resource->with('schedulingInstance.userInstance');

            return new InstanceSessionsHistoryCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
