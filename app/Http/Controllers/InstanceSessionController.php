<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstanceSessionsHistoryCollection;
use App\InstanceSessionsHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InstanceSessionController extends AppController
{
    const PAGINATE = 1;

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return InstanceSessionsHistoryCollection|JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = InstanceSessionsHistory::query();

            $resource->with(['schedulingInstance.instance', 'user']);

            if (! empty($search)) {
                $resource->whereHas('user', function (Builder $query) use ($search) {
                    $query->where('email', 'like', "%{$search}%");
                })->orWhereHas('schedulingInstance.instance', function (Builder $query) use ($search) {
                    $query->where('aws_instance_id', 'like', "%{$search}%");
                });
            }

            if (! empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $histories  = (new InstanceSessionsHistoryCollection($resource->paginate($limit)))->response()->getData();
            $meta       = $histories->meta ?? null;

            $response = [
                'data'  => $histories->data ?? [],
                'total' => $meta->total ?? 0,
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }
}
