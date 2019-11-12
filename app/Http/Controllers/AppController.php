<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\AwsSetting;
use App\Bot;
use App\BotInstance;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Jobs\InstanceChangeStatus;
use App\Jobs\RestoreUserInstance;
use App\Jobs\StoreUserInstance;
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

    public function apiEmpty(Request $request)
    {
        try {
            //Specify the Amazon DocumentDB cert
//            $ctx = stream_context_create(array(
//                    "ssl" => array(
//                        "cafile" => storage_path('rds-combined-ca-bundle.pem'),
//                        'capture_peer_cert' => true,
//                        'verify_peer' => true,
//                        'verify_peer_name' => true,
//                        'allow_self_signed' => false,
//                    ))
//            );
//
//            $client = new Client("mongodb://saas:123456789@docdb-2019-10-30-15-15-51.cluster-cw5mo3pxfvfe.us-east-2.docdb.amazonaws.com:27017",
//                [
//                    "ssl" => true
//                ],
//                [
//                    "context" => $ctx
//                ]
//            );
//
//            //Specify the database and collection to be used
//            $col = $client->test->col;
//
//            //Insert a single document
//            $result1 = $col->insertOne( [ 'hello' => 'Amazon DocumentDB'] );
//
//            //Find the document that was previously written
//            $result2 = $col->findOne(['hello' => 'Amazon DocumentDB']);
//
//            //Print the result to the screen
//            dd($result1, $result2);

        } catch (Throwable $throwable) {
            dd("Throwable", $throwable->getMessage());
        }

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

        $now = Carbon::now();
        $nowDate = $now->toDateString();
        $yesterdayDate = $now->subDay()->toDateString();

        $type = InstanceHelper::getTypeS3Object($request->query('type'));

        $isset = [];

        $dates = InstanceHelper::getListInstancesDates($instance);

        if (! empty($dates)) {

            $credentials = [
                'key'    => config('aws.iam.access_key'),
                'secret' => config('aws.iam.secret_key')
            ];

            $aws = new Aws;
            $aws->s3Connection('', $credentials);

            $folder = config('aws.streamer.folder');

            foreach ($dates as $date) {

                if ($date === $nowDate) {
                    $name = 'Today';
                } elseif ($date === $yesterdayDate) {
                    $name = 'Yesterday';
                } else {
                    $name = $date;
                }

                $prefix     = "{$folder}/{$instance->tag_name}/{$type}/{$date}/thumbnail.jpg";
                $thumbnail  = $aws->getPresignedLink($aws->getS3Bucket(), $prefix);

                if (! empty($thumbnail)) {
                    array_push($isset, [
                        "name"      => $name,
                        "thumbnail" => [
                            'url'   => $thumbnail
                        ]
                    ]);
                }

//                $prefix     = "{$folder}/{$instance->tag_name}/{$type}/{$date}";
//                $info = InstanceHelper::getDateInfo($aws, $prefix, $date, $nowDate, $yesterdayDate);
//
//                if (! empty($info)) {
//                    array_push($isset, $info);
//                }
            }
        }

        return $this->success([
            'folders' => $isset
        ]);
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

    public function copy(Request $request)
    {
        try {

            $instance = $this->getInstanceWithCheckUser($request->input('instance_id'));

            if (empty($instance)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
            }

            $copy = $instance->replicate();
//            $copy->fill([
//                'tag_name' =>
//            ]);


            dd($instance, $copy);

            return $this->success();

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }
}

