<?php

namespace App\Http\Controllers\Common\Instances;

use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\Http\Resources\S3ObjectCollection;
use App\Jobs\StoreS3Objects;
use App\S3Object;
use App\Services\Aws;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        dispatch(new StoreS3Objects( $user, $instance_id, $key ));
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
        $parentFolder = $instance->s3Objects()->where('path', '=', "{$parent}")->first();
        if(!$parentFolder) {
            return $this->notFound(__('keywords.not_found'), __('keywords.files.not_exist'));
        }
        $resource = $parentFolder->children()->latest();

        $objects = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta = $objects->meta ?? null;

        $response = [
            'data'  => $objects->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    public function getS3Object ()
    {
        return $this->success();
    }

    // TODO: re-work
    private function updateObjectsThumbnailLink(Request $request, BotInstance $instance): void
    {
        $page   = $request->query('page') ?? 1;
        $limit  = $request->query('limit') ?? 10;
        $skip   = $page === 1 ? 0 : ($page-1)*$limit;
        $type   = $request->query('type') ?? '';

        $folders = $instance->s3Objects()
            ->whereNull('parent_id')
            ->skip($skip)
            ->take($limit)
            ->get();

        if ($folders->isNotEmpty()) {

            $thumbnailPath = InstanceHelper::getThumbnailPathByTypeS3Object($type);

            $credentials = [
                'key'    => config('aws.iam.access_key'),
                'secret' => config('aws.iam.secret_key')
            ];

            $aws = new Aws;
            $aws->s3Connection('', $credentials);

            foreach ($folders as $folder) {
                $prefix = "{$instance->baseS3Dir}/{$folder->name}/{$thumbnailPath}";
                $folder->update([
                    'link' => $aws->getPresignedLink($aws->getS3Bucket(), $prefix)
                ]);
            }

            unset($thumbnailPath, $credentials, $aws);
        }

        unset($page, $limit, $skip, $folders);
    }
}
