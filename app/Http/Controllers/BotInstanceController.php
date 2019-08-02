<?php

namespace App\Http\Controllers;

use App\Bots;
use App\UserInstances;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;
use App\Jobs\StoreUserInstance;

class BotInstanceController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{

            $UserInstance = UserInstances::findByUserId(Auth::id())->get();
            $botsArr = Bots::all();

            if ($UserInstance->isEmpty()) {
                session()->flash('error', 'Instance Not Found');
                return view('user.bots.running.index');
            }

            return view('user.bots.running.index', compact('UserInstance','botsArr'));

        } catch (Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
            return view('user.bots.running.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /*$BotObj = Bots::find(1);
        $string = $BotObj->aws_startup_script;
        $StartUpScript = array_filter(explode(';',$string));
        $runScript = $this->RunStartUpScript($StartUpScript);
        dd($runScript);*/
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       /* $user_id = Auth::user()->id;
        $bot_id = isset($request->bot_id) ? $request->bot_id : '';
        try {
            $bots = null;
            $botObj = Bots::find($bot_id);
            if(empty($botObj)){
                return redirect()->back()->with('error', 'Bot Not Found Please Try Again');
            } else {
                $bots = $botObj;
            }
            $keyPair = $this->CreateKeyPair();
            $SecurityGroup = $this->CreateSecurityGroupId();

            $keyPairName = $keyPair['keyName'];
            $keyPairPath = $keyPair['path'];

            $groupId = $SecurityGroup['securityGroupId'];
            $groupName = $SecurityGroup['securityGroupName'];
            $instanceIds = [];
            // Instance Create
            $newInstanceResponse = $this->LaunchInstance($keyPairName, $groupName, $bots);
            $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];

            array_push($instanceIds, $instanceId);
            $waitUntilResponse = $this->waitUntil($instanceIds);

            /*if(!empty($bots)){
                $StartUpScriptString = $bots->aws_startup_script;
                $StartUpScript = explode(PHP_EOL, $StartUpScriptString);
                $runScript = $this->RunStartUpScript($StartUpScript);
            }*/

            // Instance Describe for Public Dns Name
            /*$describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

            $LaunchTime = isset($instanceArray['LaunchTime']) ? $instanceArray['LaunchTime'] : '';
            $publicIp = isset($instanceArray['PublicIpAddress']) ? $instanceArray['PublicIpAddress'] : '';
            $publicDnsName = isset($instanceArray['PublicDnsName']) ? $instanceArray['PublicDnsName'] : '';

            $awsAmiId = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');

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
            if($userInstance->save()){
                $userInstanceDetail = new UserInstancesDetails();
                $userInstanceDetail->user_instance_id = $userInstance->id;
                $userInstanceDetail->start_time = $created_at;
                $userInstanceDetail->save();
                session()->flash('success', 'Instance Create successfully');
                return redirect(route('user.instance.index'));
            }
            session()->flash('error', 'Please Try again later');
            return redirect(route('user.instance.index'));
        }
        catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return redirect(route('user.instance.index'));
        }*/
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function show(UserInstances $userInstances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function edit(UserInstances $userInstances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserInstances $userInstances)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserInstances $userInstances)
    {
        //
    }


    public function storeBotIdInSession(Request $request)
    {
        $userInstance = new UserInstances();
        $userInstance->user_id = $request->user_id;
        $userInstance->bot_id = $request->bot_id;
        if($userInstance->save()){
            Log::debug('IN-queued Instance : '.json_encode($userInstance));
            Session::put('instance_id', $userInstance->id);
            return response()->json(['type' => 'success','data' => $userInstance->id],200);
        }

        return response()->json(['type' => 'error','data' => ''],200);
    }

    /* execute job to store user instance data */
    public function dispatchLaunchInstance(Request $request)
    {
        $user = Auth::user();
        $result =  dispatch(new StoreUserInstance($request->all(), $user));
        Session::forget('instance_id');
        return response()->json(['type' => 'success'],200);
    }

    public function checkBotIdInQueue(Request $request)
    {
        $instance_ids = array();
        $user = $request->user();
        $userInstances = UserInstances::select('bot_id', 'id as instance_id', 'user_id')->where('user_id', $user->id)
            ->where('is_in_queue','=',1)
            ->get();
        foreach ($userInstances as $value) {
            array_push($instance_ids, $value->instance_id);
        }
        $instance_ids = array_unique($instance_ids);
        foreach($instance_ids as $instance_id) {
            $result = dispatch(new StoreUserInstance($instance_id, $user));
        }
        return response()->json(['type' => 'success','data' => $instance_ids],200);
    }
}
