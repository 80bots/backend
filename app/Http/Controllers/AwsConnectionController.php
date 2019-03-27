<?php

namespace App\Http\Controllers;

use App\AwsConnection;
use App\Http\Controllers\AppController;
use Illuminate\Http\Request;

class AwsConnectionController extends AppController
{
    public function CreateKeyPair(){
        $keyPair = AwsConnection::AwsCreateKeyPair();
        return $keyPair;
    }

    public function CreateSecurityGroupId(){
        $groupId = AwsConnection::AwsCreateSecretGroup();
        return $groupId;
    }

    public function LaunchInstance($keyPair,$group_id, $bots = null){
        $instanceResponse = AwsConnection::AwsLaunchInstance($keyPair,$group_id, $bots);
        return $instanceResponse;
    }

    public function waitUntil($instanceIds){
        $waitUntilResponse = AwsConnection::waitUntil($instanceIds);
        return $waitUntilResponse;
    }

    public function DescribeInstances($instanceIds){
        $describeInstances = AwsConnection::DescribeInstances($instanceIds);
        return $describeInstances;
    }

    public function StartInstance($instanceIds){
        $startResponse = AwsConnection::StartInstance($instanceIds);
        return $startResponse;
    }

    public function StopInstance($instanceIds){
        $startResponse = AwsConnection::StopInstance($instanceIds);
        return $startResponse;
    }

    public function TerminateInstance($instanceIds){
        $startResponse = AwsConnection::TerminateInstance($instanceIds);
        return $startResponse;
    }

    public function AllocateAddresses($instanceIds){
        $addressResponse = AwsConnection::AllocateAddresses($instanceIds);
        return $addressResponse;
    }

    public function InstanceMonitoring($instanceIds){
        $monitoringRespinse = AwsConnection::InstanceMonitoring($instanceIds);
        return $monitoringRespinse;
    }

}
