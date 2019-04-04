<?php

namespace App\Http\Controllers;

use App\AwsConnection;
use App\BaseModel;
use App\Notifications;
use App\User;
use App\UserInstances;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppController extends Controller
{

    protected $credit;

    public function __construct()
    {
//        $this->credit = Notifications::CalCredit();
    }

    public function DiffTime($start_time, $end_time){
        return BaseModel::DiffTime($start_time, $end_time);
    }

    public function CalInstancesUpTime()
    {
        $users = User::findUserInstances();
        foreach ($users as $user){
            $userInstances = $user->UserInstances;
            $currentDateTime = date('Y-m-d H:i:s');
            if(!empty($userInstances)){
                foreach ($userInstances as $instance){
                    if($instance->status == 'running'){
                        $instancesIds = [];
                        array_push($instancesIds, $instance->aws_instance_id);
                        try{
                            $describeInstance = AwsConnection::DescribeInstances($instancesIds);
                            $reservationObj = $describeInstance->getPath('Reservations');
                            if(empty($reservationObj)){
                                $instance->status = 'terminated';
                                $instance->save();
                                Log::debug('instance id '.$instance->aws_instance_id. ' already terminated');
                            }
                            $instanceResponse = $reservationObj[0]['Instances'][0];
                            $launchTime = $instanceResponse['LaunchTime'];
                            $launchDateTime = date('Y-m-d H:i:s', strtotime($launchTime));
                            $cronUpTime = $this->DiffTime($launchDateTime, $currentDateTime);
                            $instance->cron_up_time = $cronUpTime;
                            $tempUpTime = !empty($instance->temp_up_time)?$instance->temp_up_time:0;
                            $upTime = $cronUpTime + $tempUpTime;
                            $instance->up_time = $upTime;
                            $instance->save();
                            Log::debug('instance id '.$instance->aws_instance_id. ' Cron Up Time is '.$cronUpTime);
                        } catch (\Exception $exception){
                            $instance->status = 'terminated';
                            $instance->save();
                            Log::debug('instance id '.$instance->aws_instance_id. ' not found');
                        }
                    } else {
                        Log::debug('instance id '.$instance->aws_instance_id. ' is '.$instance->status);
                    }
                }
            }
        }
    }


    public function CalUsedCredit(){
        Log::info('cal used credit');
    }

    public function UserActivation($id){
        $checkActivationToken = User::where('verification_token', $id)->first();

        if(isset($checkActivationToken) && !empty($checkActivationToken)){
            $checkActivationToken->verification_token = '';
            $checkActivationToken->status = 'active';
            if($checkActivationToken->save()){
                return redirect(route('login'))->with('success','Your Account will be verified successfully!!');
            } else {
                return redirect(route('login'))->with('error','Please Try After Some Time');
            }
        } else {
            return redirect(route('login'))->with('error','Unauthorized');
        }
    }
}
