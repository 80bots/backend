<?php

namespace App\Http\Controllers\admin;

use App\Bots;
use App\Http\Controllers\AwsConnectionController;
use App\Jobs\StoreUserInstance;
use App\Platforms;
use App\UserInstances;
use App\UserInstancesDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use function GuzzleHttp\Promise\all;
use App\Traits\AWSInstances;
use Auth, Log, App;
use App\User;
use Carbon\Carbon;

class UserInstancesController extends AwsConnectionController
{
    use AWSInstances;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $status = 'all', $userId = null)
    {
        Session::forget('error');
        $userInstances = UserInstances::with('user');

        if($status = 'running') {
            $request->offsetSet('status', 'running');
        }

        if($request->status = 'running') {
            $userInstances = $userInstances->where('status', $request->status);
        }

        if($request->list && $request->list == 'my_bots') {
            $userInstances = $userInstances->where('status', Auth::id());
        }

        if($userId) {
            $userInstances = $userInstances->findByUserId($userId);
        }

        $userInstances = $userInstances->get();

        $filters = $request->all();
        return view('admin.instance.index', compact('userInstances', 'filters'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $bot = Bots::find($request->bot_id);

            if (!$bot) {
                return redirect()->back()->with('error', 'Bot Not Found Please Try Again');
            }

            $keyPair = $this->CreateKeyPair();
            $securityGroup = $this->CreateSecurityGroupId();

            $keyPairName = $keyPair['keyName'];
            $keyPairPath = $keyPair['path'];

            $groupId = $securityGroup['securityGroupId'];
            $groupName = $securityGroup['securityGroupName'];
            $instanceIds = [];

            // Instance Create
            $newInstanceResponse = $this->LaunchInstance($keyPairName, $groupName, $bot);
            $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];

            array_push($instanceIds, $instanceId);
            $waitUntilResponse = $this->waitUntil($instanceIds);

            /*if(!empty($bot)){
                $StartUpScriptString = $bot->aws_startup_script;
                $StartUpScript = explode(PHP_EOL, $StartUpScriptString);
                $runScript = $this->RunStartUpScript($StartUpScript);
            }*/

            // Instance Describe for Public Dns Name
            $describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

            $LaunchTime = isset($instanceArray['LaunchTime']) ? $instanceArray['LaunchTime'] : '';
            $publicIp = isset($instanceArray['PublicIpAddress']) ? $instanceArray['PublicIpAddress'] : '';
            $publicDnsName = isset($instanceArray['PublicDnsName']) ? $instanceArray['PublicDnsName'] : '';

            $awsAmiId = env('AWS_IMAGEID', 'ami-0cd3dfa4e37921605');

            $created_at = date('Y-m-d H:i:s', strtotime($LaunchTime));

            // store instance details in database
            $userInstance = new UserInstances();
            $userInstance->user_id = $user_id;
            $userInstance->bot_id = $bot_id;
            $userInstance->aws_instance_id = $instanceId;
            $userInstance->aws_ami_id = $awsAmiId;
            $userInstance->aws_security_group_id = $groupId;
            $userInstance->aws_security_group_name = $groupName;
            $userInstance->aws_public_ip = $publicIp;
            $userInstance->status = 'running';
            $userInstance->aws_public_dns = $publicDnsName;
            $userInstance->aws_pem_file_path = $keyPairPath;
            $userInstance->created_at = $created_at;
            if ($userInstance->save()) {
                $userInstanceDetail = new UserInstancesDetails();
                $userInstanceDetail->user_instance_id = $userInstance->id;
                $userInstanceDetail->start_time = $created_at;
                $userInstanceDetail->save();
                session()->flash('success', 'Instance Created successfully');
                return redirect(route('admin.my-bots'));
            }
            session()->flash('error', 'Please Try again later');
            return redirect(route('admin.my-bots'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return redirect(route('admin.my-bots'));
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $instanceObj = UserInstances::find($request->id);
            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $request->id])->latest()->first();
            $instanceIds = [];
            array_push($instanceIds, $instanceObj->aws_instance_id);
            $currentDate = date('Y-m-d H:i:s');

            $describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $reservationObj = $describeInstancesResponse->getPath('Reservations');
            if (empty($reservationObj)) {
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is not exist');
                return 'false';
            }
            $InstStatus = $reservationObj[0]['Instances'][0]['State']['Name'];
            if ($InstStatus == 'terminated') {
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is already terminated');
                return 'false';
            }

            if ($request->status == 'start') {
                $instanceObj->status = 'running';
                $startObj = $this->StartInstance($instanceIds);
                $instanceDetail = new UserInstancesDetails();
                $instanceDetail->user_instance_id = $request->id;
                $instanceDetail->start_time = $currentDate;
                $instanceDetail->save();

            } elseif ($request->status == 'stop') {
                $instanceObj->status = 'stop';
                $stopObj = $this->StopInstance($instanceIds);
                $instanceDetail->end_time = $currentDate;
                $diffTime = $this->DiffTime($instanceDetail->start_time, $instanceDetail->end_date);
                $instanceDetail->total_time = $diffTime;
                if ($instanceDetail->save()) {
                    if ($diffTime > $instanceObj->cron_up_time) {
                        $instanceObj->cron_up_time = 0;
                        $tempUpTime = !empty($instanceObj->temp_up_time) ? $instanceObj->temp_up_time : 0;
                        $upTime = $diffTime + $tempUpTime;
                        $instanceObj->temp_up_time = $upTime;
                        $instanceObj->up_time = $upTime;
                        $instanceObj->used_credit = $this->CalUsedCredit($upTime);
                    }
                }
            } else {
                $instanceObj->status = 'terminated';
                $terminateInstance = $this->TerminateInstance($instanceIds);
            }

            if ($instanceObj->save()) {
                session()->flash('success', 'Instance ' . $request->status . ' successfully!');
                return 'true';
            }
            session()->flash('error', 'Instance ' . $request->status . ' Not successfully!');
            return 'false';
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return 'false';
        }
    }

    /* store bot_id in session */
    public function storeBotIdInSession(Request $request)
    {
        $userInstance = new UserInstances();
        $userInstance->user_id = $request->user_id;
        $userInstance->bot_id = $request->bot_id;
        if ($userInstance->save()) {
            Log::debug('IN-queued Instance : '.json_encode($userInstance));
            Session::put('instance_id',$userInstance->id);
            return response()->json(['type' => 'success','data' => $userInstance->id], 200);
        }
        return response()->json(['type' => 'error', 'data' => ''], 200);
    }

    /* execute job to store user instance data */
    public function dispatchLaunchInstance(Request $request)
    {
        $user = Auth::user();
        $result = dispatch(new StoreUserInstance($request->all(), $user));
        Session::forget('instance_id');
        return response()->json(['type' => 'success'], 200);
    }


    public function checkBotIdInQueue(Request $request)
    {
        $instanceIds = array();
        $user = Auth::user();
        $instanceIds = UserInstances::select('id')
                                ->where('user_id', $user->id)
                                ->where('is_in_queue', '=', 1)
                                ->pluck('id')->toArray();
        $instanceIds = array_unique($instanceIds);

        foreach($instanceIds as $instanceId) {
            $result = dispatch(new StoreUserInstance($instanceId, $user));
        }
        return response()->json(['type' => 'success', 'data' => $instanceIds], 200);
    }

    public function syncInstances()
    {
        try {
          \Log::info('Sync started at ' . date('Y-m-d h:i:s'));
          $instancesByStatus = $this->sync();

          \Log::info($instancesByStatus);
          $awsInstancesIn = [];
          foreach ($instancesByStatus as $status => $instances) {
            foreach ($instances as $key => $instance) {
              $bot = Bots::where('aws_ami_image_id', $instance['aws_ami_id'])->first();

              if($bot) {
                $instance['bot_id'] = $bot->id;
              }

              $userInstance = UserInstances::where('aws_instance_id' , $instance['aws_instance_id'])->first();

              if(!$userInstance) {
                \Log::info($instance['aws_instance_id'] . ' has not been recorded while launch or manually launched from the aws');
                $admin = User::where('role_id', 1)->first();
                if($admin) {
                  $instance['user_id']      = $admin->id;
                  $instance['status']       = $status;
                  if($status == 'running') {
                    $instance['is_in_queue']  = 0;
                  }
                  $userInstance = UserInstances::updateOrCreate([
                    'aws_instance_id' => $instance['aws_instance_id']
                  ], $instance);
                } else {
                  \Log::info($instance['aws_instance_id'] . ' cannot be synced');
                }
              } else {
                $userInstance->status  = $status;
                $userInstance->name =  $instance['name'];
                if($status == 'running') {
                  $userInstance->is_in_queue = 0;
                }
                $userInstance->save();
              }

              $awsInstancesIn[] = $instance['aws_instance_id'];
            }
          }

          UserInstances::where(function($query) use($awsInstancesIn) {
                          $query->whereNotIn('aws_instance_id', $awsInstancesIn)
                          ->orWhere('aws_instance_id', null)
                          ->orWhere('status', 'terminated');
                        })->whereNotIn('status', ['start', 'stop'])
                        ->delete();

          UserInstances::where(function($query) {
                          $query->where('is_in_queue', 1)
                          ->orWhereIn('status', ['start', 'stop']);
                        })->where('updated_at', '<' , Carbon::now()->subMinutes(10)->toDateTimeString())
                        ->delete();

          \Log::info('Synced completed at ' . date('Y-m-d h:i:s'));
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }

        if(!App::runningInConsole()) {
          session()->flash('success', 'Instances updated successfully!');
          return back();
        }
    }
}
