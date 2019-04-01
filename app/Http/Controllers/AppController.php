<?php

namespace App\Http\Controllers;

use App\AwsConnection;
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
        $this->credit = Notifications::CalCredit();
    }

    public function CalUsedCredit(){

        $user = Auth::user();
        $userInstances = $user->userInstances;
        $instancesIds = [];
        foreach ($userInstances as $instance){
            if($instance->status == 'running'){
                array_push($instancesIds, $instance->aws_instance_id);
            }
        }
        if(!empty($instancesIds)){
            $describeInstance = AwsConnection::DescribeInstances($instancesIds);
            $reservations = $describeInstance->getPath('Reservations');
            foreach ($reservations as $reserved){
                dd($reserved);
                $instanceArray = $reserved['Instances'][0];
                $inspectionId = $instanceArray['InstanceId'];
                $status = $instanceArray['State']['Name'];
                if($status == 'running'){
                    $userInstance = UserInstances::findByInstanceId($inspectionId)->first();
                    $currentDate = date('Y-m-d H:i:s');
//                    $userInstance->up_time = $userInstance->up_time +
                }
            }

        }

        Log::debug('cal used credit scheduler');
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
