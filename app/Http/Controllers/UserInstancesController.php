<?php

namespace App\Http\Controllers;

use App\Bots;
use App\UserInstances;
use App\UserInstancesDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserInstancesController extends AwsConnectionController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = Auth::user()->id;
        try{
            $UserInstance = UserInstances::findByUserId($user_id)->get();
            $botsArr = Bots::all();
            if(!$UserInstance->isEmpty()){
                $instancesId = [];
                array_push($instancesId,$UserInstance[0]->aws_instance_id);
//                $this->InstanceMonitoring($instancesId);
                return view('user.instance.index',compact('UserInstance','botsArr'));
            }
            session()->flash('error', 'Instance Not Found');
            return view('user.instance.index');
        } catch (\Exception $e){
            session()->flash('error', $e->getMessage());
            return view('user.instance.index');
        }
    }


    public function BotList(){
        try{
            $bots = Bots::get();
            return view('user.bots.index',compact('bots'));
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('user.bots.index');
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


    public function changeStatus(Request $request){
        try{
            $instanceObj = UserInstances::find($request->id);
            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $request->id])->latest()->first();
            $instanceIds = [];
            array_push($instanceIds, $instanceObj->aws_instance_id);
            $currentDate = date('Y-m-d H:i:s');

            $describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $reservationObj = $describeInstancesResponse->getPath('Reservations');
            if(empty($reservationObj)){
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is not exist');
                return 'false';
            }
            $InstStatus = $reservationObj[0]['Instances'][0]['State']['Name'];
            if($InstStatus == 'terminated'){
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is already terminated');
                return 'false';
            }

            if($request->status == 'start'){
                $instanceObj->status = 'running';
                $startObj = $this->StartInstance($instanceIds);
                $instanceDetail = new UserInstancesDetails();
                $instanceDetail->user_instance_id = $request->id;
                $instanceDetail->start_time = $currentDate;
                $instanceDetail->save();

            } elseif($request->status == 'stop') {
                $instanceObj->status = 'stop';
                $stopObj = $this->StopInstance($instanceIds);
                $instanceDetail->end_time = $currentDate;
                $deffTime = UserInstances::deffTime($instanceDetail->start_time, $instanceDetail->end_date);
                $instanceDetail->total_time = $deffTime;
                if($instanceDetail->save()){
                    $instanceObj->up_time = $instanceObj->up_time + $deffTime;
                }
            } else {
                $instanceObj->status = 'terminated';
                $terminateInstance = $this->TerminateInstance($instanceIds);
            }

            if($instanceObj->save()){
                session()->flash('success', 'Instance '.$request->status.' successfully!');
                return 'true';
            }
            session()->flash('error', 'Instance '.$request->status.' Not successfully!');
            return 'false';
        } catch (\Exception $e){
            session()->flash('error', $e->getMessage());
            return 'false';
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_id = Auth::user()->id;
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
            $describeInstancesResponse = $this->DescribeInstances($instanceIds);
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
        }
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
}
