<?php

namespace App\Jobs;

use App\Bots;
use App\Http\Controllers\AwsConnectionController;
use App\Http\Controllers\UserInstancesController;
use App\UserInstances;
use App\UserInstancesDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class StoreUserInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request)
    {

        $this->data = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
        ini_set('memory_limit', '-1');
        $result = $this->data;
        $userInstance = UserInstances::findOrFail($result['instance_id']);

        $bots = null;
        $botObj = Bots::find($userInstance->bot_id);
        if(empty($botObj)){
            return redirect()->back()->with('error', 'Bot Not Found Please Try Again');
        } else {
            $bots = $botObj;
        }

        //$keyPair = $this->CreateKeyPair();
            $keyPair = AwsConnectionController::CreateKeyPair();
            
            //$SecurityGroup = $this->CreateSecurityGroupId();
            $SecurityGroup = AwsConnectionController::CreateSecurityGroupId();

            $keyPairName = $keyPair['keyName'];
            $keyPairPath = $keyPair['path'];

            $groupId = $SecurityGroup['securityGroupId'];
            $groupName = $SecurityGroup['securityGroupName'];
            $instanceIds = [];

            // Instance Create
            /*$newInstanceResponse = $this->LaunchInstance($keyPairName, $groupName, $bots);*/
            $newInstanceResponse = AwsConnectionController::LaunchInstance($keyPairName, $groupName, $bots);

            $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];

            array_push($instanceIds, $instanceId);
            //$waitUntilResponse = $this->waitUntil($instanceIds);
            $waitUntilResponse = AwsConnectionController::waitUntil($instanceIds);         

            //$describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $describeInstancesResponse = AwsConnectionController::DescribeInstances($instanceIds);

            $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

            $LaunchTime = isset($instanceArray['LaunchTime']) ? $instanceArray['LaunchTime'] : '';
            $publicIp = isset($instanceArray['PublicIpAddress']) ? $instanceArray['PublicIpAddress'] : '';
            $publicDnsName = isset($instanceArray['PublicDnsName']) ? $instanceArray['PublicDnsName'] : '';

            $awsAmiId = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');
            $created_at = date('Y-m-d H:i:s', strtotime($LaunchTime));

            // store instance details in database

            $userInstance->aws_instance_id = $instanceId;
            $userInstance->aws_ami_id = $awsAmiId;
            $userInstance->aws_security_group_id = $groupId;
            $userInstance->aws_security_group_name = $groupName;
            $userInstance->aws_public_ip = $publicIp;
            $userInstance->status = 'running';
            $userInstance->aws_public_dns = $publicDnsName;
            $userInstance->aws_pem_file_path = $keyPairPath;
            $userInstance->created_at = $created_at;
            $userInstance->is_in_queue = 0;
            if($userInstance->save()){
                Log::debug('Saved Instance : '.json_encode($userInstance));
                Session::put('instance_id','');
                $userInstanceDetail = new UserInstancesDetails();
                $userInstanceDetail->user_instance_id = $userInstance->id;
                $userInstanceDetail->start_time = $created_at;
                $userInstanceDetail->save();
                session()->flash('success', 'Instance Create successfully');
                return response()->json(['status' => 'success'],200);
                //return redirect(route('user.instance.index'));
            }

            //UserInstancesController::store($result);
        }catch (Exception $e)
        {
            Log::debug('Error on catch : '.$e->getMessage());
            return false;
        }
    }

}



