<?php

namespace App\Http\Controllers\Common\Instances;

use App\BotInstance;
use App\Http\Resources\S3ObjectCollection;
use App\Jobs\StoreS3Objects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileSystemController extends InstanceController
{
    /**
     * @param Request $request
     * @param string $instance_id
     * @return JsonResponse
     */
    public function storeS3Object(Request $request, string $instance_id)
    {
        $user = Auth::user();
        $key = $request->input('key');
        $diff = $request->input('difference') ?? 0.00;
        dispatch(new StoreS3Objects( $user, $instance_id, $key, $diff ));
        return response()->json([], 201);
    }

    /**
     * @param Request $request
     * @param $instance_id
     * @return JsonResponse
     */
    public function getS3Objects(Request $request, string $instance_id)
    {
        $request->validate([
            'limit' => 'numeric:nullable'
        ]);

        /** @var BotInstance $instance */
        $instance = $this->getInstanceWithCheckUser($instance_id);

        $limit = $request->query('limit') ?? self::PAGINATE;
        $parent = $request->query('parent') ?? null;
        $isFiltered = $request->query('isFiltered') == 'false' || empty($request->query('isFiltered'))
                      ? 0
                      : 1;
        $parentFolder = $instance->s3Objects()->where('path', '=', "{$parent}")->first();

        if(!$parentFolder) {
            return $this->success([
                'data'  => $objects->data ?? [],
                'total' => $meta->total ?? 0
            ]);
        }
        $resource = $parentFolder->children();

        $resource = $this->applyBlackList($resource);

        $resource = $resource->latest();

        if ($isFiltered == true) {
            $objects = (new S3ObjectCollection($resource->where([
                                                            ['name', 'not like', '%blank%'],
                                                            ['name', 'not like', '%black%']
                                                        ])
                                                        ->paginate($limit)))->response()->getData();
        } else {
            $objects = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        }

        $meta = $objects->meta ?? null;

        $response = [
            'data'  => $objects->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    /**
     * @return JsonResponse
     */
    public function getS3Object ()
    {
        return $this->success();
    }

    /**
     * @param $resource
     * @return resource
     */
    private function applyBlackList($resource)
    {
        if(Auth::check()) {
            return $resource;
        }
        $resource->where('path', 'not like');
        return $resource;
    }


}
