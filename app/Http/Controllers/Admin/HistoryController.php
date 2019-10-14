<?php

namespace App\Http\Controllers\Admin;

use App\CreditUsage;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\CreditUsageCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class HistoryController extends AppController
{
    const PAGINATE = 1;

    public function getCreditUsage(Request $request)
    {
        try {

            $limit      = $request->query('limit') ?? self::PAGINATE;
            $action     = $request->input('action');
            $sort       = $request->input('sort');
            $order      = $request->input('order') ?? 'asc';
            $instanceId = $request->input('instanceId');

            $resource = CreditUsage::findByUserId($request->input('user'));

            // TODO: Add Filters

            if (! empty($instanceId)) {
                $resource->whereHas('instance', function (Builder $query) use ($instanceId) {
                    $query->where('aws_instance_id', '=', $instanceId);
                });
            }

            switch ($action) {
                case CreditUsage::ACTION_ADDED:
                    $resource->onlyAdded();
                    break;
                case CreditUsage::ACTION_USED:
                    $resource->onlyUsed();
                    break;
                default:
                    break;
            }

            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(CreditUsage::ORDER_FIELDS[$sort])) {
                    return QueryHelper::orderCreditHistory($query, CreditUsage::ORDER_FIELDS[$sort], $order);
                } else {
                    return $query->orderBy('created_at', 'desc');
                }
            }, function ($query) {
                return $query->orderBy('created_at', 'desc');
            });

            $history    = (new CreditUsageCollection($resource->paginate($limit)))->response()->getData();
            $meta       = $history->meta ?? null;

            $response = [
                'data'  => $history->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        }  catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }
}
