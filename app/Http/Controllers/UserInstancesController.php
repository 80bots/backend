<?php

namespace App\Http\Controllers;

use App\AwsConnection;
use App\UserInstances;
use App\UserInstancesDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

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
            if($UserInstance){
                $instancesId = [];
                array_push($instancesId,$UserInstance[0]->aws_instance_id);
                $this->InstanceMonitoring($instancesId);
                return view('user.instance.index',compact('UserInstance'));
            }
            return view('user.instance.index')->with('error', 'Instance Are not Found');
        } catch (\Exception $e){
            return view('user.instance.index')->with('error', $e->getMessage());
        }
    }

    /*public function describe($instanceIds){

        $describeInstancesResponse = $this->DescribeInstances($instanceIds);
        $instanceArray = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0];

        $LaunchTime = isset($instanceArray['LaunchTime']) ? $instanceArray['LaunchTime'] : '';
        $publicIp = isset($instanceArray['PublicIpAddress']) ? $instanceArray['PublicIpAddress'] : '';
        $publicDnsName = isset($instanceArray['PublicDnsName']) ? $instanceArray['PublicDnsName'] : '';

        foreach ($instanceIds as $instanceId){
            $updateInstances = UserInstances::findByInstanceId($instanceId);
            $updateInstances->aws_public_ip = $publicIp;
            $updateInstances->aws_public_dns = $publicDnsName;
            $updateInstances->save();
        }

    }*/

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_id = Auth::user()->id;
        try {
            $keyPair = $this->CreateKeyPair();
            $SecurityGroup = $this->CreateSecurityGroupId();

            $keyPairName = $keyPair['keyName'];
            $keyPairPath = $keyPair['path'];

            $groupId = $SecurityGroup['securityGroupId'];
            $groupName = $SecurityGroup['securityGroupName'];
            $instanceIds = [];
            // Instance Create
            $newInstanceResponse = $this->LaunchInstance($keyPairName, $groupName);
            $instanceId = $newInstanceResponse->getPath('Instances')[0]['InstanceId'];


            array_push($instanceIds, $instanceId);

            $waitUntilResponse = $this->waitUntil($instanceIds);

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
                Session::flash('success', 'Instance Create successfully');
                return redirect(route('user.instance.index'));
            }
            Session::flash('error', 'Please Try again later');
            return redirect(route('user.instance.index'));
        }
        catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            return redirect(route('user.instance.index'));
        }
    }


    public function changeStatus(Request $request){
        try{
            $instanceObj = UserInstances::find($request->id);
            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $request->id])->latest()->first();
            $instanceIds = [];
            array_push($instanceIds, $instanceObj->aws_instance_id);
            $currentDate = date('Y-m-d H:i:s');

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
                $instanceDetail->save();
            } else {
                $instanceObj->status = 'terminated';
                $terminateInstance = $this->TerminateInstance($instanceIds);
            }

            if($instanceObj->save()){
                Session::flash('success', 'Instance '.$request->status.' successfully!');
                return 'true';
            }
            Session::flash('error', 'Instance '.$request->status.' Not successfully!');
            return 'false';
        } catch (\Exception $e){
            Session::flash('error', $e->getMessage());
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
        //
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
