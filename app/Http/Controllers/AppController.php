<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\AwsSetting;
use App\Bot;
use App\BotInstance;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Http\Resources\S3ObjectCollection;
use App\Jobs\InstanceChangeStatus;
use App\Jobs\RestoreUserInstance;
use App\Jobs\StoreS3Objects;
use App\Jobs\StoreUserInstance;
use App\S3Object;
use App\Services\Aws;
use App\User;
use Carbon\Carbon;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;
use Throwable;

class AppController extends Controller
{
    protected $credit;

    public function __construct()
    {
        $this->credit = CommonHelper::calculateCredit();
    }

    public function apiEmpty()
    {
        return response()->json([]);
    }

    /**
     * Launch EC2 Instances
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function launchInstances(Request $request)
    {
        try {

            $botId = $request->input('bot_id');
            $params = collect($request->input('params'));

            $credit = config('app.credit');

            if ($params->isEmpty()) {
                return $this->error(__('keywords.error'), __('keywords.instance.parameters_incorrect'));
            }

            /**
             * Check whether the user has enough credits in order
             * to run the selected number of instances
             */
            $user = User::find(Auth::id()); // Get "App\User" object

            if ($user->isUser() && $user->credits < ($credit*$params->count())) {
                return $this->error(__('keywords.error'), __('keywords.instance.credits_error'));
            }

            //
            $region = $user->region ?? null;

            Log::debug("AwsRegion ISSET");

            if (!$this->checkLimitInRegion($region, $params->count())) {
                return $this->error(__('keywords.error'), __('keywords.instance.launch_limit_error'));
            }

            Log::debug("checkLimitInRegion ISSET");

            $bot = Bot::find($botId);

            if (empty($bot)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.bots.not_found'));
            }

            Log::debug("BOT ISSET");

            $awsSetting = AwsSetting::isDefault()->first();

            $imageId = $region->default_image_id ?? $awsSetting->image_id;

            if ($this->issetAmiInRegion($region, $imageId)) {

                Log::debug("issetAmiInRegion ISSET");

                foreach ($params as $param) {

                    $instance = BotInstance::create([
                        'user_id'       => Auth::id(),
                        'bot_id'        => $bot->id ?? null,
                        'aws_region_id' => $region->id ?? null,
                        'aws_status'    => BotInstance::STATUS_PENDING
                    ]);

                    $instance->details()->create([
                        'aws_instance_type' => $awsSetting->type ?? null,
                        'aws_storage_gb'    => $awsSetting->storage ?? null,
                        'aws_image_id'      => $imageId ?? null
                    ]);

                    if (! empty($instance)) {
                        dispatch(new StoreUserInstance($bot, $instance, $user, $param, $request->ip()));
                    }
                }

                Log::debug("COUNT PARAMS: {$params->count()}");

                $region->increment('created_instances', $params->count());

                return $this->success([
                    'instance_id' => $instance->id ?? null
                ], __('keywords.instance.launch_success'));

            } else {
                return $this->error(__('keywords.error'), __('keywords.instance.not_exist_ami'));
            }

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Restore EC2 Instance
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function restoreInstance(Request $request)
    {
        $instance = $this->getInstanceWithCheckUser($request->input('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
        }

        dispatch(new RestoreUserInstance($instance, Auth::user(), $request->ip()));

        $instance->region->increment('created_instances', 1);

        return $this->success([
            'instance_id' => $instance->id ?? null
        ], __('keywords.instance.launch_success'));
    }

    /**
     * Limit check whether we can create instance in the region
     * @param AwsRegion $awsRegion
     * @param int $countInstances
     * @return bool
     */
    private function checkLimitInRegion(AwsRegion $awsRegion, int $countInstances): bool
    {
        $limit      = $awsRegion->limit ?? 0;
        $created    = $awsRegion->created_instances ?? 0;

        return $created < ($limit*AwsRegion::PERCENT_LIMIT);
    }

    /**
     * @param AwsRegion $region
     * @param string $imageId
     * @return bool
     */
    private function issetAmiInRegion(AwsRegion $region, string $imageId): bool
    {
        $result = AwsRegion::whereHas('amis', function (Builder $query) use ($imageId) {
            $query->where('image_id', '=', $imageId);
        })->first();

        return !empty($result) ? $result->id === $region->id : false;
    }

    /**
     * Change status ec2 instance
     * @param $status
     * @param $id
     * @return bool
     * @throws Throwable
     */
    protected function changeStatus($status, $id): bool
    {
        $instance = $this->getInstanceWithCheckUser($id);

        if (empty($instance)) {
            return false;
        }

        $instanceDetail = $instance->details()->latest()->first();

        if (empty($instanceDetail)) {
            return false;
        }

        if (empty($instance->aws_region_id)) {
            return false;
        }

        $user   = User::find(Auth::id());
        $aws    = new Aws;

        //
        $instance->clearPublicIp();

        try {

            $describeInstancesResponse = $aws->describeInstances(
                [$instance->aws_instance_id ?? null],
                $instance->region->code
            );

            if (! $describeInstancesResponse->hasKey('Reservations') || InstanceHelper::checkTerminatedStatus($describeInstancesResponse)) {
                $instance->setAwsStatusTerminated();

                if ($instance->region->created_instances > 0) {
                    $instance->region->decrement('created_instances');
                }

                InstanceHelper::cleanUpTerminatedInstanceData($aws, $instanceDetail);
                return true;
            }

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return false;
        }

        $instance->setAwsStatusPending();

        dispatch(new InstanceChangeStatus($instance, $user, $instance->region, $status));

        return true;
    }

    public function UserActivation($id)
    {
        $checkActivationToken = User::where('verification_token', $id)->first();

        if (isset($checkActivationToken) && !empty($checkActivationToken)) {
            $checkActivationToken->verification_token = '';
            $checkActivationToken->status = 'active';
            if ($checkActivationToken->save()) {
                return redirect(route('login'))->with('success', 'Your Account will be verified successfully!!');
            } else {
                return redirect(route('login'))->with('error', 'Please Try After Some Time');
            }
        } else {
            return redirect(route('login'))->with('error', 'Unauthorized');
        }
    }

    protected function storeS3Objects(Request $request)
    {
//        $instance = BotInstance::find(2);
//
//        $s3Objects = $instance->s3Objects()
//            ->with('children')
//            ->whereNull('parent_id')
//            ->get();
//
//        dd($s3Objects->toArray());
//
//        dd("DD");

        dispatch(new StoreS3Objects(
            $request->ip(),
            $request->only('instance_id', 'key')
        ));

        return response()->json([], 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getInstanceFolders(Request $request)
    {
        $instance = $this->getInstanceWithCheckUser($request->query('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
        }

        $limit = $request->query('limit') ?? 10;

        $this->updateObjectsThumbnailLink($request, $instance);

        $resource   = $instance->s3Objects()->whereNull('parent_id');
        $folders    = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta       = $folders->meta ?? null;

        $response = [
            'data'  => $folders->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    public function getS3Objects(Request $request)
    {
        $instance = $this->getInstanceWithCheckUser($request->query('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
        }

        $limit  = $request->query('limit') ?? self::PAGINATE;
        $folder = $request->query('folder');
        if(!$folder) {
            return $this->getInstanceFolders($request);
        }
        $type   = InstanceHelper::getTypeS3Object($request->query('type'));

        $folderObjects = S3Object::find($folder);

        if (empty($folderObjects)) {
            return $this->notFound(__('keywords.not_found'), __('keywords.not_found'));
        }

        switch ($type) {
            case S3Object::TYPE_SCREENSHOTS:
                // Update links from DB, which will be expired soon
                InstanceHelper::updateScreenshotsOldLinks($instance, $folderObjects);
                break;
            case S3Object::TYPE_JSON:
                // Update links from DB, which will be expired soon
                InstanceHelper::updateJsonsOldLinks($instance, $folderObjects);
                break;
        }

        $resource = $instance->s3Objects()
            ->where('path', 'like', "{$folderObjects->name}/output/{$type}/%")
            ->where('entity', '=', S3Object::ENTITY_FILE)
            ->where('name', '!=', 'thumbnail');

        $screenshots    = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta           = $screenshots->meta ?? null;

        $response = [
            'data'  => $screenshots->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    public function getS3Logs(Request $request)
    {
        $instance = $this->getInstanceWithCheckUser($request->query('instance_id'));

        if (empty($instance)) {
            return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
        }

        // Remove links from DB, which will be expired soon
        S3Object::removeOldLinks($instance->id);

        $limit  = $request->query('limit') ?? self::PAGINATE;

        $resource = $instance->s3Objects()
            ->where('type', '=', S3Object::TYPE_LOGS);

        if ($resource->count() === 0) {
            InstanceHelper::saveS3Logs($instance);
        }

        $instances  = (new S3ObjectCollection($resource->paginate($limit)))->response()->getData();
        $meta       = $instances->meta ?? null;

        $response = [
            'data'  => $instances->data ?? [],
            'total' => $meta->total ?? 0
        ];

        return $this->success($response);
    }

    /**
     * @param string|null $id
     * @param bool $withTrashed
     * @return BotInstance|null
     */
    public function getInstanceWithCheckUser(?string $id, $withTrashed = false): ?BotInstance
    {
        /** @var BotInstance $query */
        $query = BotInstance::where('id', '=', $id);
        if($withTrashed) {
            $query->withTrashed();
        }
        if(!Auth::user()->isAdmin()) {
            $query->where('user_id', '=', Auth::id());
        }
        return $query->first();
    }

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

