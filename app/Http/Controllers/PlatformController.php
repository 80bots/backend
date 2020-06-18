<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlatformCollection;
use App\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PlatformController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = Platform::where('status', '=', 'active');

            if (! empty($search)) {
                $resource->where('name', 'like', "%{$search}%");
            }

            if (!empty($sort)) {
                $resource->orderBy($sort, $order);
            }

            $bots   = (new PlatformCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $bots->meta ?? null;

            $response = [
                'data'  => $bots->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @return JsonResponse
     */
    public function getInstanceTypes()
    {
        return $this->success(['t2.micro', 't2.small']);
    }
}
