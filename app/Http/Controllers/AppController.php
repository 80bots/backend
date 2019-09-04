<?php

namespace App\Http\Controllers;

use App\AwsRegion;
use App\AwsSetting;
use App\Bot;
use App\DeleteSecurityGroup;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Jobs\InstanceChangeStatus;
use App\Jobs\StoreUserInstance;
use App\Services\Aws;
use App\User;
use App\BotInstance;
use App\BotInstancesDetails;
use Aws\Result;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
     * Launch EC2 Instance
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function launchInstance(Request $request)
    {
        try {

            $region = $request->input('region');

            $bot = Bot::find($request->input('bot_id'));

            if (empty($bot)) {
                return $this->notFound(__('keywords.not_found'), __('keywords.bots.not_found'));
            }

            Log::debug("BOT ISSET");

            //
            $awsRegion = AwsRegion::onlyRegion($region)->first();

            Log::debug("AwsRegion ISSET");

            if (!$this->checkLimitInRegion($awsRegion)) {
                return $this->error(__('keywords.error'), __('keywords.instance.launch_limit_error'));
            }

            Log::debug("checkLimitInRegion ISSET");

            $awsSetting = AwsSetting::isDefault()->first();

            if ($this->issetAmiInRegion($awsRegion, $awsSetting->image_id)) {

                Log::debug("issetAmiInRegion ISSET");

                //
                $awsRegion->increment('created_instances');

                $instance = BotInstance::create([
                    'user_id'       => Auth::id(),
                    'bot_id'        => $bot->id ?? null,
                    'aws_region_id' => $awsRegion->id ?? null,
                    'aws_status'    => BotInstance::STATUS_PENDING
                ]);

                $instance->details()->create([
                    'aws_instance_type' => $awsSetting->type ?? null,
                    'aws_storage_gb'    => $awsSetting->storage ?? null,
                    'aws_image_id'      => $awsSetting->image_id ?? null
                ]);

                if (! empty($instance)) {

                    $user = User::find(Auth::id());

                    $dispatch = dispatch(new StoreUserInstance($bot, $instance, $user, $request->input('params')));

                    return $this->success([
                        'instance_id' => $instance->id ?? null
                    ], __('keywords.instance.launch_success'));
                }
            }

            return $this->error(__('keywords.error'), __('keywords.instance.launch_error'));

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Limit check whether we can create instance in the region
     * @param AwsRegion $awsRegion
     * @return bool
     */
    private function checkLimitInRegion(AwsRegion $awsRegion): bool
    {
        $limit      = $awsRegion->limit ?? 0;
        $created    = $awsRegion->created_instances ?? 0;

        return $created < ($limit*AwsRegion::PERCENT_LIMIT);
    }

    /**
     * @param AwsRegion $awsRegion
     * @param string $imageId
     * @return bool
     */
    private function issetAmiInRegion(AwsRegion $awsRegion, string $imageId): bool
    {
        $count = $awsRegion->whereHas('amis', function (Builder $query) use ($imageId) {
            $query->where('image_id', '=', $imageId);
        })->count();

        return $count > 0;
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

        $awsRegion = AwsRegion::find($instance->aws_region_id ?? null);

        if (empty($awsRegion)) {
            return false;
        }

        $user   = User::find(Auth::id());
        $aws    = new Aws;

        $describeInstancesResponse = $aws->describeInstances([$instanceDetail->aws_instance_id ?? null]);

        if (! $describeInstancesResponse->hasKey('Reservations')) {
            // TODO: Remove instance
            return false;
        }

        if (InstanceHelper::checkTerminatedStatus($describeInstancesResponse)) {
            $instance->setAwsStatusTerminated();
            //
            if ($awsRegion->created_instances > 0) {
                $awsRegion->decrement('created_instances');
            }
            //
            InstanceHelper::cleanUpTerminatedInstanceData($aws, $instanceDetail);
            return false;
        }

        $instance->setAwsStatusPending();
        //
        $instanceDetail->clearPublicIp();

        dispatch(new InstanceChangeStatus($instance, $user, $awsRegion, $status));

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
     * @param string|null $id
     * @return BotInstance|null
     */
    private function getInstanceWithCheckUser(?string $id): ?BotInstance
    {
        if (Auth::user()->isAdmin()) {
            return BotInstance::find($id);
        } elseif (Auth::user()->isUser()) {
            return BotInstance::where([
                ['id', '=', $id],
                ['user_id', '=', Auth::id()]
            ])->first();
        } else {
            return null;
        }
    }
}

