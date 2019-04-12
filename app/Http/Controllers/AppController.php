<?php

namespace App\Http\Controllers;

use App\AwsConnection;
use App\BaseModel;
use App\Notifications;
use App\User;
use App\UserInstances;
use App\SchedulingInstance;
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
        foreach ($users as $UserObj) {
            $UserInstances = isset($UserObj->UserInstances) ? $UserObj->UserInstances : '';
            if (!empty($UserInstances)) {
                $usedCreditArray = [];
                foreach ($UserInstances as $userInstance) {
                    $usedCredit = isset($userInstance->used_credit) ? $userInstance->used_credit : '0';
                    array_push($usedCreditArray, $usedCredit);
                }
                $totalUsedCredit = array_sum($usedCreditArray);
                if (empty($UserObj->temp_credit_score) || $UserObj->temp_credit_score == 0) {
                    $UserObj->temp_credit_score = $UserObj->credit_score;
                }
                $temp_credit = $UserObj->temp_credit_score;
                $creditScore = (float)$temp_credit - (float)$totalUsedCredit;
                $UserObj->credit_score = $creditScore;
                $UserObj->save();
                /*if($UserObj->save()){
                    if($creditScore <= 1){
                        $this->SendEmailNotification($UserObj);
                    }
                }*/
                Log::info('credit Score of email: ' . $UserObj->email . ' is ' . $UserObj->credit_score);
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

    /*public function SendEmailNotification($UserObj){
        $UserInstances = UserInstances::findRunningInstanceByUserId($UserObj->id);
        if (!empty($UserInstances)) {
            foreach ($UserInstances as $instance) {

            }
        }
    }*/
    
    public function startScheduling()
    {
        Log::info('cron call start scheduling');
        try {
            $startScheduling  = SchedulingInstance::findScheduling('start');
            $instancesIds = [];

            if(count($startScheduling) >0)
            {
                foreach ($startScheduling as $row) {
                    Log::info('Get Scheduling  instance id'.$row->userInstances->aws_instance_id);
                    if(isset($row->userInstances->aws_instance_id))
                    {
                        array_push($instancesIds, $row->userInstances->aws_instance_id);
                    }
                        
                    // update user Instances  
                    Log::info('Update status on user instance '.$row->userInstances->aws_instance_id);    
                    $userInstances = $row->userInstances;
                    $userInstances->status = 'running';
                    $savedata =  $userInstances->save();
                }

                if(count($instancesIds) > 0){
                     Log::info('start all instance call aws connection class');
                    $result = AwsConnection::StartInstance($instancesIds);
                    // End instance 
                    $reservationObj = $result->getPath('StartingInstances');
                    if($reservationObj[0]['CurrentState']['Name'] == 'running')
                    {
                        Log::info('All instance are started successfully');
                    }
                    else
                    {
                        Log::info('Instance are not started');
                    }
                }
            }
            else
            {
                //echo 'Record Not Found';
                Log::info('Start scheduling record not found');
            }
        }
        catch (\Exception $e) {

            Log::info('Catch Error Message '. $e->getMessage());
        }    
    }

    public function stopScheduling()
    {
        Log::info('cron call stop scheduling');
        try {
            $startScheduling  = SchedulingInstance::findScheduling('stop');

            $instancesIds = [];
            if(count($startScheduling) >0)
            {
                foreach ($startScheduling as $row) {
                    Log::info('Get Scheduling  instance id'.$row->userInstances->aws_instance_id);
                    if(isset($row->userInstances->aws_instance_id))
                    {
                        array_push($instancesIds, $row->userInstances->aws_instance_id);
                    }
                   
                    Log::info('Update status on user instance '.$row->userInstances->aws_instance_id);
                    //update user Instances      
                    $userInstances = $row->userInstances;
                    $userInstances->status = 'stop';
                    $savedata =  $userInstances->save();
                }

                if(count($instancesIds) > 0){
                    Log::info('Stop all instance call aws connection class');
                    $result = AwsConnection::StopInstance($instancesIds);
                    
                    // End instance 
                    $reservationObj = $result->getPath('StoppingInstances');
                    if($reservationObj[0]['CurrentState']['Name'] == 'stopped')
                    {
                        Log::info('All instance are stop successfully');
                    }
                    else
                    {
                        Log::info('All instance  are not stoped');
                    }
                }
            }
            else
            {
                // echo 'Record Not Found';
                Log::info('Stop scheduling record not found');
            }
        }
        catch (\Exception $e) {

            Log::info('Catch Error Message '. $e->getMessage());
        }    
    }
}
