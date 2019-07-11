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
            $userInstances = $user->UserInstances;
            $currentDateTime = date('Y-m-d H:i:s');
            if (!empty($userInstances)) {
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
        // Get Low CreditPercentage
        $CreditPercentage = CreditPercentage::get();
        $lowPercentage  = array();
        foreach ($CreditPercentage  as $row)
        {
            $lowPercentage[] = $row->percentage;
        }
        //End Get low CreditPercentage

        foreach ($users as $UserObj) {

            $UserInstances = isset($UserObj->UserInstances) ? $UserObj->UserInstances : '';
            if (!empty($UserInstances)) {
                $usedCreditArray = [];
                $instancesIds = [];
                foreach ($UserInstances as $userInstance) {
                    $usedCredit = isset($userInstance->used_credit) ? $userInstance->used_credit : '0';
                    array_push($usedCreditArray, $usedCredit);

                    // add instancesIds
                    array_push($instancesIds, $userInstance->aws_instance_id);
                }
                $totalUsedCredit = array_sum($usedCreditArray);
                if (empty($UserObj->temp_remaining_credits) || $UserObj->temp_remaining_credits == 0) {
                    $UserObj->temp_remaining_credits = $UserObj->remaining_credits;
                }
                $temp_credit = $UserObj->temp_remaining_credits;
                $creditScore = (float)$temp_credit - (float)$totalUsedCredit;
                $UserObj->remaining_credits = $creditScore;

                //User relation to get packages amount what package buy.

                if(count($UserObj->UserSubscriptionPlan) > 0){
                    $packageAmount = $UserObj->UserSubscriptionPlan[0]->credit;

                    // Find  Percentage by current credit and user package amount
                    // Percentage get on round and then match
                    $creditScorePercentage = round(($creditScore * 100) / $packageAmount);

                    // Check low percentge and if match then sent mail user credit is low please add credit.
                    if(in_array($creditScorePercentage, $lowPercentage))
                    {
                        // Check last mail Percentage sent and current creditScorePercentage if not match then send mail
                        if($UserObj->sent_email_status != $creditScorePercentage)
                        {
                           // if send mail then save on usres table on
                            $UserObj->sent_email_status = $creditScorePercentage;
                            $User = new User;
                            $dataResult = $User->UserCreditSendEmail($UserObj);
                        }
                    }
                }

                // user credits score is 0 then we will they user all instance will stop
                if($creditScore == 0)
                {
                    // below if in we check admin role 1-is admin Role so we have checked.
                    if($UserObj->role_id != 1)
                    {
                        // Stop Instance for the user
                        $result = AwsConnection::StopInstance($instancesIds);
                        $startInstance = $result->getPath('StoppingInstances');
                        // Update instance  on user instance table
                        foreach ($startInstance as $instanceDetail) {
                            $CurrentState = $instanceDetail['CurrentState'];
                            $instanceId = $instanceDetail['InstanceId'];
                            if ($CurrentState['Name'] == 'stopped' || $CurrentState['Name'] == 'stopping') {
                                $UserInstance = UserInstances::findByInstanceId($instanceId)->first();
                                $UserInstance->status = 'stop';
                                $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();
                                if (!empty($instanceDetail)) {
                                    $instanceDetail->end_time = $currentDate;
                                    $diffTime = $this->DiffTime($instanceDetail->start_time, $instanceDetail->end_date);
                                    $instanceDetail->total_time = $diffTime;
                                    if ($instanceDetail->save()) {
                                        if ($diffTime > $UserInstance->cron_up_time) {
                                            $UserInstance->cron_up_time = 0;
                                            $tempUpTime = !empty($UserInstance->temp_up_time) ? $UserInstance->temp_up_time : 0;
                                            $upTime = $diffTime + $tempUpTime;
                                            $UserInstance->temp_up_time = $upTime;
                                            $UserInstance->up_time = $upTime;
                                        }
                                    }
                                }
                                if ($UserInstance->save()) {
                                    Log::info('Instance Id ' . $instanceId . ' Stopped');
                                }
                            } else {
                                Log::info('Instance Id ' . $instanceId . ' Not Stopped Successfully');
                            }
                        }
                    }
                }

                $UserObj->save();

                Log::info('Credits of email: ' . $UserObj->email . ' is ' . $UserObj->remaining_credits);
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
                $UserInstanceObj = isset($scheduler->userInstances) ? $scheduler->userInstances : '';
                $scheduleDetails = isset($scheduler->schedulingInstanceDetails) ? $scheduler->schedulingInstanceDetails : '';
                if (!empty($scheduleDetails)) {
                    foreach ($scheduleDetails as $detail) {
                        if (!empty($UserInstanceObj) && !empty($detail->cron_data)) {
                            $CronDate = explode(' ', $detail->cron_data);
                            $currentTime = strtotime(date('D h:i A'));
                            $cronTimeDate = date_create($CronDate[0] . $CronDate[1] . $CronDate[2], timezone_open('GMT'.$CronDate[3]));
                            $cronTime = date_timestamp_get($cronTimeDate);
                            if ($currentTime == $cronTime) {
                                Log::info($detail->scheduling_instances_id);

                                $timezoneoffset = explode('+',$detail->cron_data);
                                $hourmins = explode(':',$timezoneoffset[1]);
                                $offsetinseconds = $hourmins[0] * 3600 + $hourmins[1] * 60;
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

                                array_push($instancesIds, $UserInstanceObj->aws_instance_id);
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
                            $UserInstance = UserInstances::findByInstanceId($instanceId)->first();
                            $UserInstance->status = 'running';
                            if ($UserInstance->save()) {
                                $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();
                                if (empty($instanceDetail)) {
                                    $instanceDetail = new UserInstancesDetails();
                                    $instanceDetail->user_instance_id = $UserInstance->id;
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
                $UserInstanceObj = isset($scheduler->userInstances) ? $scheduler->userInstances : '';
                $scheduleDetails = isset($scheduler->schedulingInstanceDetails) ? $scheduler->schedulingInstanceDetails : '';
                if (!empty($scheduleDetails)) {
                    foreach ($scheduleDetails as $detail) {
                        if (!empty($UserInstanceObj) && !empty($detail->cron_data)) {
                            $CronDate = explode(' ', $detail->cron_data);
                            $currentTime = strtotime(date('D h:i A'));
                            Log::info(print_r($CronDate,true));
                            $cronTimeDate = date_create($CronDate[0] . $CronDate[1] . $CronDate[2], timezone_open('GMT'.$CronDate[3]));
                            $cronTime = date_timestamp_get($cronTimeDate);
                            if ($currentTime == $cronTime) {

                                //Save the session history
                                $history = new SessionsHistory;
                                $history->scheduling_instances_id = $scheduler->id;
                                $history->user_id = $scheduler->user_id;
                                $history->schedule_type = $detail->schedule_type;
                                $history->selected_time = $detail->selected_time;
                                $history->save();

                                array_push($instancesIds, $UserInstanceObj->aws_instance_id);
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
                            $UserInstance = UserInstances::findByInstanceId($instanceId)->first();
                            $UserInstance->status = 'stop';
                            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $UserInstance->id, 'end_time' => null])->latest()->first();
                            if (!empty($instanceDetail)) {
                                $instanceDetail->end_time = $currentDate;
                                $diffTime = $this->DiffTime($instanceDetail->start_time, $instanceDetail->end_date);
                                $instanceDetail->total_time = $diffTime;
                                if ($instanceDetail->save()) {
                                    if ($diffTime > $UserInstance->cron_up_time) {
                                        $UserInstance->cron_up_time = 0;
                                        $tempUpTime = !empty($UserInstance->temp_up_time) ? $UserInstance->temp_up_time : 0;
                                        $upTime = $diffTime + $tempUpTime;
                                        $UserInstance->temp_up_time = $upTime;
                                        $UserInstance->up_time = $upTime;
                                        $UserInstance->used_credit = $this->CalUsedCredit($upTime);
                                    }
                                }
                            }
                            if ($UserInstance->save()) {
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

