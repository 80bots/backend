<?php

namespace App\Http\Controllers;

use App\CreditUsage;
use App\Helpers\QueryHelper;
use App\Http\Resources\User\CreditUsageCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class HistoryController extends Controller
{
    const PAGINATE = 1;

    public function getCreditUsage(Request $request)
    {
        try {
            $limit = $request->query('limit') ?? self::PAGINATE;
            $action = $request->input('action');
            $sort = $request->input('sort');
            $order = $request->input('order') ?? 'asc';

            $resource = CreditUsage::with('user')->findByUserId(Auth::id());

            // TODO: Add Filters

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
