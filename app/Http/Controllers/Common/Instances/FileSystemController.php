<?php

namespace App\Http\Controllers\Common\Instances;

use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\Http\Resources\S3ObjectCollection;
use App\Jobs\StoreS3Objects;
use App\Services\Aws;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileSystemController extends InstanceController
{

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getS3Objects(Request $request, string $instance_id)
    {
        $request->validate([
            'limit' => 'numeric:nullable'
        ]);
        /** @var BotInstance $instance */
        $instance = $this->getInstanceWithCheckUser($instance_id);

        $limit = $request->query('limit') ?? self::PAGINATE;
        $type = $request->query('type');
        $entity = $request->query('entity');
        $parent_id = $request->query('parent_id');

        $type = InstanceHelper::getTypeS3Object($type);

        $resource = $instance
            ->s3Objects()
            ->where('type', '=', $type)
            ->where('entity', '=', $entity)
            ->where('parent_id', '=', $parent_id)
            ->where('name', '!=', 'thumbnail');

        // TODO: Update items by limit;
//        switch ($type) {
//            case S3Object::TYPE_SCREENSHOTS:
//                // Update links from DB, which will be expired soon
//                InstanceHelper::updateScreenshotsOldLinks($instance, $folderObjects);
//                break;
//            case S3Object::TYPE_JSON:
//                // Update links from DB, which will be expired soon
//                InstanceHelper::updateJsonsOldLinks($instance, $folderObjects);
//                break;
//        }

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
