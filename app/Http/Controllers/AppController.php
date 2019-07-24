<?php

namespace App\Http\Controllers;

use App\AwsConnection;
use App\BaseModel;
use App\SchedulingInstance;
use App\User;
use App\CreditPercentage;
use App\UserInstances;
use App\UserInstancesDetails;
use App\InstanceSessionsHistory as SessionsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppController extends Controller
{

    protected $credit;

    public function __construct()
    {
        $this->credit = BaseModel::CalCredit();
    }

    public function DiffTime($start_time, $end_time)
    {
        return BaseModel::DiffTime($start_time, $end_time);
    }

    public function CalInstancesUpTime()
    {
        $users = User::findUserInstances();
        foreach ($users as $user) {
            $userInstances = $user->userInstances()->get();
            $currentDateTime = date('Y-m-d H:i:s');
            foreach ($userInstances as $instance) {
                if ($instance->status == 'running') {
                    $instancesIds = [];
                    array_push($instancesIds, $instance->aws_instance_id);
                    try {
                        $describeInstance = AwsConnection::DescribeInstances($instancesIds);
                        $reservationObj = $describeInstance->getPath('Reservations');
                        if (empty($reservationObj)) {
                            $instance->status = 'terminated';
                            $instance->save();
                            Log::debug('instance id ' . $instance->aws_instance_id . ' already terminated');
                        }
                        $instanceResponse = $reservationObj[0]['Instances'][0];
                        $launchTime = $instanceResponse['LaunchTime'];
                        $launchDateTime = date('Y-m-d H:i:s', strtotime($launchTime));
                        $cronUpTime = $this->DiffTime($launchDateTime, $currentDateTime);
                        $instance->cron_up_time = $cronUpTime;
                        $tempUpTime = !empty($instance->temp_up_time) ? $instance->temp_up_time : 0;
                        $upTime = $cronUpTime + $tempUpTime;
                        $instance->up_time = $upTime;
                        $instance->used_credit = $this->CalUsedCredit($upTime);
                        $instance->save();
                        Log::debug('instance id ' . $instance->aws_instance_id . ' Cron Up Time is ' . $cronUpTime);
                    } catch (\Exception $exception) {
                        $instance->status = 'terminated';
                        $instance->save();
                        Log::debug('instance id ' . $instance->aws_instance_id . ' not found');
                    }
                } else {
                    Log::debug('instance id ' . $instance->aws_instance_id . ' is ' . $instance->status);
                }
            }
        }
    }

    public function CalUsedCredit($UpTime)
    {
        if ($UpTime > 0) {
            return round($UpTime * (float)config('app.credit') / (float)config('app.up_time'), 2);
        } else {
            return 0;
        }
    }

    public function CalUserCreditScore()
    {
        $users = User::findUserInstances();
        $currentDate = date('Y-m-d H:i:s');

        $lowPercentage = CreditPercentage::select('percentage')->pluck('percentage')->toArray();

        foreach ($users as $user) {
            $userInstances = $user->userInstances()->get();
            if (!empty($userInstances)) {
                $usedCreditArray = [];
                $instancesIds = [];
                foreach ($userInstances as $userInstance) {
                    $usedCredit = ($userInstance->used_credit) ? $userInstance->used_credit : '0';
                    if($userInstance->status == 'running'){
                        array_push($usedCreditArray, $usedCredit);
                    }
                    array_push($instancesIds, $userInstance->aws_instance_id);
                }
                $totalUsedCredit = array_sum($usedCreditArray);
                $user = User::find($user->id);
                if (empty($user->temp_remaining_credits) || $user->temp_remaining_credits == 0) {
                    $user->temp_remaining_credits = $user->remaining_credits;
                }

                $tempCredit = $user->temp_remaining_credits;
                $creditScore = (float)$tempCredit - (float)$totalUsedCredit;
                $user->remaining_credits = $creditScore;

                if($user->hasSubscriptionPlan()){
                    $packageAmount = $user->userSubscriptionPlan[0]->credit;
                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);

                    if(in_array($creditScorePercentage, $lowPercentage) && $user->sent_email_status != $creditScorePercentage)
                    {
                        $user->sent_email_status = $creditScorePercentage;
                        $dataResult = User::UserCreditSendEmail($user);
                    }
                }
                $user->save();

                if($creditScore <= 0 && $user->role_id != 1)
                {
                    $result = AwsConnection::StopInstance($instancesIds);
                    $startInstance = $result->getPath('StoppingInstances');

                    foreach ($startInstance as $instanceDetail) {
                        $currentState = $instanceDetail['CurrentState'];
                        $instanceId = $instanceDetail['InstanceId'];
                        if ($currentState['Name'] == 'stopped' || $currentState['Name'] == 'stopping') {
                            $userInstance = UserInstances::findByInstanceId($instanceId)->first();
                            $userInstance->status = 'stop';
                            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $userInstance->id, 'end_time' => null])->latest()->first();
                            if (!empty($instanceDetail)) {
                                $instanceDetail->end_time = $currentDate;
                                $diffTime = $this->DiffTime($instanceDetail->start_time, $instanceDetail->end_date);
                                $instanceDetail->total_time = $diffTime;
                                if ($instanceDetail->save()) {
                                    if ($diffTime > $userInstance->cron_up_time) {
                                        $userInstance->cron_up_time = 0;
                                        $tempUpTime = !empty($userInstance->temp_up_time) ? $userInstance->temp_up_time : 0;
                                        $upTime = $diffTime + $tempUpTime;
                                        $userInstance->temp_up_time = $upTime;
                                        $userInstance->up_time = $upTime;
                                    }
                                }
                            }
                            if ($userInstance->save()) {
                                Log::info('Instance Id ' . $instanceId . ' Stopped');
                            }
                        } else {
                            Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                        }
                    }
                }



                Log::info('Credits of email: ' . $user->email . ' is ' . $user->remaining_credits);
            }
        }
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

    public function startScheduling()
    {
        Log::info('cron call start scheduling');
        $currentDate = date('Y-m-d H:i:s');
        try {
            $instancesIds = [];
            $startSchedule = SchedulingInstance::findScheduling('start')->get();
            foreach ($startSchedule as $scheduler) {
                $userInstanceObj = isset($scheduler->userInstances) ? $scheduler->userInstances : '';
                $scheduleDetails = isset($scheduler->schedulingInstanceDetails) ? $scheduler->schedulingInstanceDetails : '';
                if (!empty($scheduleDetails)) {
                    foreach ($scheduleDetails as $detail) {
                        if (!empty($userInstanceObj) && !empty($detail->cron_data)) {
                            $cronDate = explode(' ', $detail->cron_data);
                            $currentTime = strtotime(date('D h:i A'));
                            $cronTimeDate = date_create($cronDate[0] . $cronDate[1] . $cronDate[2], timezone_open('GMT'.$cronDate[3]));
                            $cronTime = date_timestamp_get($cronTimeDate);
                            if ($currentTime == $cronTime) {
                                Log::info($detail->scheduling_instances_id);

                                $timezoneoffset = explode('+',$detail->cron_data);
                                $hourMins = explode(':',$timezoneoffset[1]);
                                $offsetinseconds = $hourMins[0] * 3600 + $hourMins[1] * 60;
                                $timezone = timezone_name_from_abbr("", $offsetinseconds, 0);

                                //Save the session history
                                $history = new SessionsHistory;
                                $history->scheduling_instances_id = $scheduler->id;
                                $history->user_id = $scheduler->user_id;
                                $history->schedule_type = $detail->schedule_type;
                                $history->cron_data = $detail->cron_data;
                                $history->current_time_zone = $timezone;
                                $history->selected_time = $detail->selected_time;
                                $history->save();

                                array_push($instancesIds, $userInstanceObj->aws_instance_id);
                            }
                        }
                    }
                }
            }

            if (count($instancesIds) > 0) {
                $result = AwsConnection::StartInstance($instancesIds);
                if (!empty($result)) {
                    $startInstance = $result->getPath('StartingInstances');
                    foreach ($startInstance as $instanceDetail) {
                        $CurrentState = $instanceDetail['CurrentState'];
                        $instanceId = $instanceDetail['InstanceId'];
                        if ($CurrentState['Name'] == 'pending' || $CurrentState['Name'] == 'running') {
                            $userInstance = UserInstances::findByInstanceId($instanceId)->first();
                            $userInstance->status = 'running';
                            if ($userInstance->save()) {
                                $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $userInstance->id, 'end_time' => null])->latest()->first();
                                if (empty($instanceDetail)) {
                                    $instanceDetail = new UserInstancesDetails();
                                    $instanceDetail->user_instance_id = $userInstance->id;
                                    $instanceDetail->start_time = $currentDate;
                                    $instanceDetail->save();
                                }
                                Log::info('Instance Id ' . $instanceId . ' Started');
                            }
                        } else {
                            Log::info('Instance Id ' . $instanceId . ' Not Started Successfully');
                        }
                    }
                } else {
                    Log::info('Instances are not Started [' . $instancesIds . ']');
                }
            } else {
                Log::info('No Instances Are there to Start');
            }
        } catch (\Exception $e) {
            Log::info('Catch Error Message ' . $e->getMessage());
        }
    }

    public function stopScheduling()
    {
        Log::info('cron call stop scheduling');
        $currentDate = date('Y-m-d H:i:s');
        try {
            $instancesIds = [];
            $startSchedule = SchedulingInstance::findScheduling('stop')->get();
            foreach ($startSchedule as $scheduler) {
                $userInstanceObj = isset($scheduler->userInstances) ? $scheduler->userInstances : '';
                $scheduleDetails = isset($scheduler->schedulingInstanceDetails) ? $scheduler->schedulingInstanceDetails : '';
                if (!empty($scheduleDetails)) {
                    foreach ($scheduleDetails as $detail) {
                        if (!empty($userInstanceObj) && !empty($detail->cron_data)) {
                            $cronDate = explode(' ', $detail->cron_data);
                            $currentTime = strtotime(date('D h:i A'));
                            Log::info(print_r($cronDate,true));
                            $cronTimeDate = date_create($cronDate[0] . $cronDate[1] . $cronDate[2], timezone_open('GMT'.$cronDate[3]));
                            $cronTime = date_timestamp_get($cronTimeDate);
                            if ($currentTime == $cronTime) {

                                //Save the session history
                                $history = new SessionsHistory;
                                $history->scheduling_instances_id = $scheduler->id;
                                $history->user_id = $scheduler->user_id;
                                $history->schedule_type = $detail->schedule_type;
                                $history->selected_time = $detail->selected_time;
                                $history->save();

                                array_push($instancesIds, $userInstanceObj->aws_instance_id);
                            }
                        }
                    }
                }
            }
            if (count($instancesIds) > 0) {
                $result = AwsConnection::StopInstance($instancesIds);
                if (!empty($result)) {
                    $startInstance = $result->getPath('StoppingInstances');
                    foreach ($startInstance as $instanceDetail) {
                        $CurrentState = $instanceDetail['CurrentState'];
                        $instanceId = $instanceDetail['InstanceId'];
                        if ($CurrentState['Name'] == 'stopped' || $CurrentState['Name'] == 'stopping') {
                            $userInstance = UserInstances::findByInstanceId($instanceId)->first();
                            $userInstance->status = 'stop';
                            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $userInstance->id, 'end_time' => null])->latest()->first();
                            if (!empty($instanceDetail)) {
                                $instanceDetail->end_time = $currentDate;
                                $diffTime = $this->DiffTime($instanceDetail->start_time, $instanceDetail->end_date);
                                $instanceDetail->total_time = $diffTime;
                                if ($instanceDetail->save()) {
                                    if ($diffTime > $userInstance->cron_up_time) {
                                        $userInstance->cron_up_time = 0;
                                        $tempUpTime = !empty($userInstance->temp_up_time) ? $userInstance->temp_up_time : 0;
                                        $upTime = $diffTime + $tempUpTime;
                                        $userInstance->temp_up_time = $upTime;
                                        $userInstance->up_time = $upTime;
                                        $userInstance->used_credit = $this->CalUsedCredit($upTime);
                                    }
                                }
                            }
                            if ($userInstance->save()) {
                                Log::info('Instance Id ' . $instanceId . ' Stopped');
                            }
                        } else {
                            Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                        }
                    }
                } else {
                    Log::info('Instances are not Stopped [' . $instancesIds . ']');
                }
            } else {
                Log::info('No Instances Are there to stop');
            }
        } catch (\Exception $e) {
            Log::info('Catch Error Message ' . $e->getMessage());
            Log::info('Catch Error File ' . $e->getFile());
            Log::info('Catch Error Trace:');
            //Log::info(print_r($e->getTrace(), true));
        }
    }
}
