<?php

namespace App\Http\Controllers\admin;

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

    public function LaunchInstance($keyPair,$group_id){
        $instanceResponce = AwsConnection::AwsLaunchInstance($keyPair,$group_id);
        return $instanceResponce;
    }

    public function DescribeInstances($instanceIds){
        $describeInstances = AwsConnection::DescribeInstances($instanceIds);
        return $describeInstances;
    }
}
