<?php

namespace App\Http\Controllers\Common\Instances;

use App\AwsSetting;
use App\Bot;
use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\Jobs\InstanceChangeStatus;
use App\Jobs\RestoreUserInstance;
use App\Jobs\StoreUserInstance;
use App\Services\Aws;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManageController extends InstanceController {
    /**
     * Launch EC2 Instances
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function launchInstances(Request $request)
    {
        try {
            Log::debug("Launch Instance Start");
            $botId = $request->input('bot_id');
            $params = collect($request->input('params'));

            Log::debug("Params : ".$params);

            if ($params->isEmpty()) {
                return $this->error(__('keywords.error'), __('keywords.instance.parameters_incorrect'));
            }

            /**
             * Check whether the user has enough credits in order
             * to run the selected number of instances
             */
            $user = User::find(Auth::id()); // Get "App\User" object

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

            Log::debug("Aws Setting");

            $imageId = $region->default_image_id ?? $awsSetting->image_id;

            Log::debug("Image Id");

            if ($this->issetAmiInRegion($region, $imageId)) {

                Log::debug("issetAmiInRegion ISSET");
                Log::debug("params ".$params);
                foreach ($params as $param) {
                    $instance = BotInstance::create([
                        'user_id'           => Auth::id(),
                        'tag_user_email'    => Auth::user()->email ?? '',
                        'bot_id'            => $bot->id ?? null,
                        'aws_region_id'     => $region->id ?? null,
                        'aws_status'        => BotInstance::STATUS_PENDING
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
     * @return JsonResponse
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function copy(Request $request)
    {
        try {
            $instance = $this->getInstanceWithCheckUser($request->input('instance_id'));
            if (empty($instance)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.instance.not_found'));
            }
            if (empty($instance->about)) {
                return $this->error(__('keywords.server_error'), __('keywords.bots.error_parameters'));
            }
            $instanceDetail = $instance->details()->latest()->first();
            $user   = Auth::user();
            $start  = Carbon::now()->toDateTimeString();
            $copy = $instance->replicate();
            $copy->fill([
                'user_id'           => $user->id,
                'tag_name'          => null,
                'tag_user_email'    => $user->email,
                'aws_region_id'     => $user->region->id,
                'aws_instance_id'   => null,
                'aws_public_ip'     => null,
                'up_time'           => 0,
                'cron_up_time'      => 0,
                'total_up_time'     => 0,
                'aws_status'        => BotInstance::STATUS_PENDING,
                'start_time'        => $start,
                'created_at'        => $start,
                'updated_at'        => $start
            ]);
            if($copy->save()) {
                $copy->details()->create([
                    'aws_instance_type' => $instanceDetail->aws_instance_type,
                    'aws_storage_gb'    => $instanceDetail->aws_storage_gb,
                    'aws_image_id'      => $instanceDetail->aws_image_id
                ]);
                $instance_params = json_decode(''.$instance->about->params, true);
                dispatch(new StoreUserInstance($copy->bot, $copy, $user, $instance_params, $request->ip()));
                return $this->success([
                    'instance_id' => $copy->id ?? null
                ], __('keywords.instance.launch_success'));
            }
            return $this->error(__('keywords.error'), __('keywords.server_error'));
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }
}
