<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Resources\User\PlatformCollection;
use App\Platform;
use Illuminate\Http\Request;
use Throwable;

class PlatformController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ApiResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

            $resource = Platform::where('status', '=', 'active');

            // TODO: Add Filters

            //
            if (! empty($search)) {
                $resource->where('name', 'like', "%{$search}%");
            }

            //
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

    public function getInstanceTypes()
    {
        // TODO: receive through AWS Pricing API
        return $this->success(['t2.micro', 't2.small']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Platform  $platforms
     * @return \Illuminate\Http\Response
     */
    public function show(Platform $platforms)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Platform  $platforms
     * @return \Illuminate\Http\Response
     */
    public function edit(Platform $platforms)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Platform  $platforms
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Platform $platforms)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Platform  $platforms
     * @return \Illuminate\Http\Response
     */
    public function destroy(Platform $platforms)
    {
        //
    }
}
