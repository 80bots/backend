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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManageController extends InstanceController {
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
}
