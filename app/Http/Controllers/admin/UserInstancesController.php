<?php

namespace App\Http\Controllers\admin;

use App\AwsConnection;
use App\UserInstances;
use Aws\Ec2\Ec2Client;
use Illuminate\Http\Request;

class UserInstancesController extends AwsConnectionController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $keyPair = $this->CreateKeyPair();
        $SecurityGroup = $this->CreateSecurityGroupId();

        $keyPairName = $keyPair['keyName'];
        $keyPairPath = $keyPair['path'];

        $groupId = $SecurityGroup['securityGroupId'];
        $groupName = $SecurityGroup['securityGroupName'];

        // Instance Create
        $newInstanceResponce = $this->LaunchInstance($keyPairName, $groupName);
        $instanceId = $newInstanceResponce->getPath('Instances')[0]['InstanceId'];

        $instanceIds = [];
        array_push($instanceIds, $instanceId);

        // Instance Describe for Public Dns Name
        $describeInstancesResponse = $this->DescribeInstances($instanceIds);
        $publicDnsName = $describeInstancesResponse->getPath('Reservations')[0]['Instances'][0]['PublicDnsName'];

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
