<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\InstanceSessionsHistoryCollection;
use App\InstanceSessionsHistory;
use Illuminate\Http\Request;
use Throwable;

class InstanceSessionController extends AppController
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

            $limit = $request->query('limit') ?? self::PAGINATE;

            $resource = InstanceSessionsHistory::ajax();

            // TODO: Add Filters
            $resource->with('schedulingInstance.userInstance');

            $histories  = (new InstanceSessionsHistoryCollection($resource->paginate($limit)))->response()->getData();
            $meta       = $histories->meta ?? null;

            $response = [
                'data'  => $histories->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
