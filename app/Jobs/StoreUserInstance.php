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
use App\Events\dispatchedInstanceEvent;

class StoreUserInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $user;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $user)
    {

        $this->id = $id;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Starting instance for ' . $this->id);
        try {
            ini_set('memory_limit', '-1');

            $userInstance = UserInstances::findOrFail($this->id);
            $bot = Bots::find($userInstance->bot_id);

            if(!$bot){
                session()->flash('error', 'Bot Not Found Please Try Again');
                return response()->json(['message' => 'Bot Not Found Please Try Again'], 404);
            }

            $keyPair       = AwsConnectionController::CreateKeyPair();
            \Log::info('Created Key pair');

            $tagName       = AwsConnectionController::CreateTagName();
            \Log::info('Created tag name');

            $securityGroup = AwsConnectionController::CreateSecurityGroupId();
            \Log::info('Created SecurityGroups');

            $keyPairName = $keyPair['keyName'];
            $keyPairPath = $keyPair['path'];

            $groupId = $securityGroup['securityGroupId'];
            $groupName = $securityGroup['securityGroupName'];

            $instanceIds = [];

            // Instance Create
            $newInstanceResponse = AwsConnectionController::LaunchInstance($keyPairName, $groupName, $bot, $tagName, $this->user);

            $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];

            \Log::info('Lauched instance ' . $instanceId);

            array_push($instanceIds, $instanceId);
            $waitUntilResponse = AwsConnectionController::waitUntil($instanceIds);

            $describeInstancesResponse = AwsConnectionController::DescribeInstances($instanceIds);

            $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

            $launchTime    = $instanceArray['LaunchTime'] ??  '';
            $publicIp      = $instanceArray['PublicIpAddress'] ?? '';
            $publicDnsName = $instanceArray['PublicDnsName'] ?? '';

            $awsAmiId   = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');
            $created_at = date('Y-m-d H:i:s', strtotime($launchTime));

            // store instance details in database

            $userInstance->tag_name                 = $tagName;
            $userInstance->aws_ami_name             = $bot->aws_ami_name;
            $userInstance->aws_instance_id          = $instanceId;
            $userInstance->aws_ami_id               = $awsAmiId;
            $userInstance->aws_security_group_id    = $groupId;
            $userInstance->aws_security_group_name  = $groupName;
            $userInstance->aws_public_ip            = $publicIp;
            $userInstance->status                   = 'running';
            $userInstance->aws_public_dns           = $publicDnsName;
            $userInstance->aws_pem_file_path        = $keyPairPath;
            $userInstance->created_at               = $created_at;
            $userInstance->is_in_queue              = 0;
            $userInstance->tag_user_email           = $this->user ? $this->user->email : null;


            if($userInstance->save()){
                Log::debug('Updated Instance : '.json_encode($userInstance));
                Session::put('instance_id','');
                $userInstanceDetail = new UserInstancesDetails();
                $userInstanceDetail->user_instance_id = $userInstance->id;
                $userInstanceDetail->start_time       = $created_at;
                $userInstanceDetail->save();
                session()->flash('success', 'Instance Created successfully');
                broadcast(new dispatchedInstanceEvent($userInstance));
            }

            return response()->json(['message' => 'Instance Created successfully'], 200);

        } catch (Exception $e) {
            Log::debug('Error on catch : '.$e->getMessage());
            return false;
        }
    }

}
