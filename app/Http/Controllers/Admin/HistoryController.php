<?php

namespace App\Http\Controllers\Admin;

use App\CreditUsage;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\CreditUsageCollection;
use Illuminate\Http\Request;
use Throwable;

class HistoryController extends AppController
{
    const PAGINATE = 1;

    public function getCreditUsage(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $action = $request->input('action');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = CreditUsage::with('user')->findByUserId($request->input('user'));

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

            //
            if (empty($sort)) {
                $sort = 'created_at';
                $order = 'desc';
            }
            $resource->orderBy($sort, $order);

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