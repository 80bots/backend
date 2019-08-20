<?php

namespace App\Http\Controllers;

use App\DeleteSecurityGroup;
use App\Helpers\CommonHelper;
use App\Jobs\StoreUserInstance;
use App\Services\Aws;
use App\User;
use App\UserInstance;
use App\UserInstancesDetails;
use Aws\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $region = 'ca-central-1';

            $instance = UserInstance::create([
                'user_id'   => Auth::id(),
                'bot_id'    => $request->input('bot_id'),
            ]);

            if (! empty($instance)) {
                dispatch(new StoreUserInstance($instance->id ?? null, Auth::user(), $region));
                return $this->success([
                    'instance_id' => $instance->id ?? null
                ], __('keywords.instance.launch_success'));
            }

            return $this->error(__('keywords.error'), __('keywords.instance.launch_error'));

        } catch (Throwable $throwable) {
            return $this->error(__('keywords.server_error'), $throwable->getMessage());
        }
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

        $currentDate    = Carbon::now()->toDateTimeString();
        $aws            = new Aws;

        $describeInstancesResponse = $aws->describeInstances([$instance->aws_instance_id ?? null]);

        if (! $describeInstancesResponse->hasKey('Reservations')) {
            return false;
        }

        if ($this->checkTerminatedStatus($describeInstancesResponse)) {
            $instance->update(['status' => UserInstance::STATUS_TERMINATED]);
            //
            $this->cleanUpTerminatedInstanceData($aws, $instance);
            return false;
        }

        switch ($status) {

            case UserInstance::STATUS_RUNNING:

                $instance->fill(['status' => UserInstance::STATUS_RUNNING]);

                // TODO: Check result
                $aws->startInstance([$instance->aws_instance_id ?? null]);

                UserInstancesDetails::create([
                    'user_instance_id'  => $instance->id ?? null,
                    'start_time'        => $currentDate
                ]);

                break;
            case UserInstance::STATUS_STOPPED:

                $instance->fill(['status' => UserInstance::STATUS_STOPPED]);

                // TODO: Check result
                $aws->stopInstance([$instance->aws_instance_id ?? null]);

                $instanceDetail = UserInstancesDetails::where('user_instance_id', '=', $id)
                    ->latest()
                    ->first();

                $diffTime = CommonHelper::diffTimeInMinutes($instanceDetail->start_time, $currentDate);

                $instanceDetail->fill([
                    'end_time'      => $currentDate,
                    'total_time'    => $diffTime
                ]);

                if ($instanceDetail->save()) {
                    if($diffTime > ($instance->cron_up_time ?? 0)){
                        $upTime = $diffTime + ($instance->temp_up_time ?? 0);
                        $instance->fill([
                            'cron_up_time'  => 0,
                            'temp_up_time'  => $upTime,
                            'up_time'       => $upTime,
                            'used_credit'   => CommonHelper::calculateUsedCredit($upTime)
                        ]);
                    }
                }

                break;
            default:
                $instance->fill(['status' => UserInstance::STATUS_TERMINATED]);
                // TODO: Check result
                $aws->terminateInstance([$instance->aws_instance_id ?? null]);

                $this->cleanUpTerminatedInstanceData($aws, $instance);

                break;
        }

        if ($instance->save()) {

            if ($instance->status === UserInstance::STATUS_TERMINATED) {
                $instance->delete();
            }

            return true;
        }

        return false;
    }

    /**
     * Clean up unused keys and security groups
     * @param Aws $aws
     * @param UserInstance $instance
     */
    protected function cleanUpTerminatedInstanceData(Aws $aws, UserInstance $instance): void
    {
        //
        if(preg_match('/^keys\/(.*)\.pem$/s', $instance->aws_pem_file_path ?? '', $matches)) {
            $aws->deleteKeyPair($matches[1]);
            $aws->deleteS3KeyPair($instance->aws_pem_file_path ?? '');
        }
        DeleteSecurityGroup::create([
            'group_id'      => $instance->aws_security_group_id ?? '',
            'group_name'    => $instance->aws_security_group_name ?? '',
        ]);
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
     * @param Result $describeInstancesResponse
     * @return bool
     */
    private function checkTerminatedStatus(Result $describeInstancesResponse): bool
    {
        $reservationObj = $describeInstancesResponse->get('Reservations');
        return empty($reservationObj) || $reservationObj[0]['Instances'][0]['State']['Name'] === 'terminated';
    }

    /**
     * @param string|null $id
     * @return UserInstance|null
     */
    private function getInstanceWithCheckUser(?string $id): ?UserInstance
    {
        if (Auth::user()->isAdmin()) {
            return UserInstance::find($id);
        } elseif (Auth::user()->isUser()) {
            return UserInstance::where([
                ['id', '=', $id],
                ['user_id', '=', Auth::id()]
            ])->first();
        } else {
            return null;
        }
    }
}

