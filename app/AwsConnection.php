<?php

namespace App;

use Aws\Ec2\Ec2Client;
use Illuminate\Database\Eloquent\Model;

class AwsConnection extends Model
{
    public static function AwsConnection(){
        $ec2Client = new Ec2Client([
            'region' => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key'    => 'AKIAIO7MFUMEZ33ZDXKA',
                'secret' => '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',
            ],
        ]);
        return $ec2Client;
    }

    public static function AwsCreateKeyPair(){
        $ec2Client = self::AwsConnection();

        // Create Aws Pair Key
        $keyPairName = time().'_psbt';
        $result = $ec2Client->createKeyPair(array(
            'KeyName' => $keyPairName
        ));
        $uploadDirPath = "/uploads/ssh_keys/".time()."_{$keyPairName}.pem";
        // Save the private key
        $saveKeyLocation = public_path(). $uploadDirPath;
        $pemKey = $result->getPath('KeyMaterial');
        file_put_contents($saveKeyLocation, $pemKey);
        // Update the key's permissions so it can be used with SSH
        chmod($saveKeyLocation, 0600);
        $filePath = config('app.url').$uploadDirPath;

        $return['path'] = $filePath;
        $return['keyName'] = $keyPairName;
        return $return;
    }

    public static function AwsSetSecretGroupIngress($securityGroupName){
        $ec2Client = self::AwsConnection();
        // Set ingress rules for the security group
        $securityGroupIngress =
            $ec2Client->authorizeSecurityGroupIngress(array(
            'GroupName'     => $securityGroupName,
            'IpPermissions' => array(
                array(
                    'IpProtocol' => 'tcp',
                    'FromPort'   => 80,
                    'ToPort'     => 80,
                    'IpRanges'   => array(
                        array('CidrIp' => '0.0.0.0/0')
                    ),
                ),
                array(
                    'IpProtocol' => 'tcp',
                    'FromPort'   => 22,
                    'ToPort'     => 22,
                    'IpRanges'   => array(
                        array('CidrIp' => '0.0.0.0/0')
                    ),
                )
            )
        ));
        return $securityGroupIngress;
    }

    public static function AwsCreateSecretGroup(){
        $ec2Client = self::AwsConnection();

        $securityGroupName = time().'_80bots';
        // Create the security group
        $result = $ec2Client->createSecurityGroup(array(
            'GroupName'   => $securityGroupName,
            'Description' => 'Basic web server security'
        ));

        self::AwsSetSecretGroupIngress($securityGroupName);

        // Get the security group ID (optional)
        $securityGroupId = $result->get('GroupId');
        $return['securityGroupId'] = $securityGroupId;
        $return['securityGroupName'] = $securityGroupName;
        return $return;
    }

    public static function AwsLaunchInstance($keyPairName, $securityGroupName, $bots){

        if(!empty($bots)){
            $imageId = isset($bots->aws_ami_image_id) ? $bots->aws_ami_image_id : env('AWS_IMAGEID','ami-0cd3dfa4e37921605');
            $instanceType = isset($bots->aws_instance_type) ? $bots->aws_instance_type : env('AWS_INSTANCE_TYPE', 't2.micro');
            $volumeSize = isset($bots->aws_storage_gb) ? $bots->aws_storage_gb : env('AWS_Volume_Size', '8');
        } else {
            $imageId = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');
            $instanceType = env('AWS_INSTANCE_TYPE', 't2.micro');
            $volumeSize = env('AWS_Volume_Size', '8');
        }
        $ec2Client = self::AwsConnection();

        $result = $ec2Client->runInstances(array(
            'ImageId'        => $imageId,
            'MinCount'       => 1,
            'MaxCount'       => 1,
            'VolumeSize'     => $volumeSize ,
            'InstanceType'   => $instanceType,
            'KeyName'        => $keyPairName,
            'SecurityGroups' => array($securityGroupName),
        ));
        return $result;
    }

    public static function waitUntil($instanceId){
        $ec2Client = self::AwsConnection();

        $waitResponse = $ec2Client->waitUntil('InstanceRunning', ['InstanceIds' => $instanceId]);
        return $waitResponse;
    }

    public static function DescribeInstances($instanceIds){
        $ec2Client = self::AwsConnection();

        // Describe the now-running instance to get the public URL
        $resultDescribe = $ec2Client->describeInstances(
            array(
                'InstanceIds' => $instanceIds,
            ));
        return $resultDescribe;
    }

    public static function StartInstance($instanceIds){
        $ec2Client = self::AwsConnection();

        $result = $ec2Client->startInstances(array(
            'InstanceIds' => $instanceIds,
        ));

        return $result;
    }

    public static function StopInstance($instanceIds){
        $ec2Client = self::AwsConnection();

        $result = $ec2Client->stopInstances(array(
            'InstanceIds' => $instanceIds,
        ));

        return $result;
    }

    public static function TerminateInstance($instanceIds)
    {
        $ec2Client = self::AwsConnection();

        $result = $ec2Client->terminateInstances(array(
            'DryRun' => false,
            // InstanceIds is required
            'InstanceIds' => $instanceIds,
        ));

        return $result;
    }

    public static function AllocateAddresses($instanceId){

        $ec2Client = self::AwsConnection();
        $allocation = $ec2Client->allocateAddress(array(
            'DryRun' => false,
            'Domain' => 'vpc',
        ));
        $result = $ec2Client->associateAddress(array(
            'DryRun' => false,
            'InstanceId' => $instanceId,
            'AllocationId' => $allocation->get('AllocationId')
        ));
        return $result;
    }

    public static function InstanceMonitoring($instanceIds){
        $ec2Client = self::AwsConnection();
        $monitorInstance = 'ON';
        if ($monitorInstance == 'ON') {
            $result = $ec2Client->monitorInstances(array(
                'InstanceIds' => $instanceIds
            ));
        } else {
            $result = $ec2Client->unmonitorInstances(array(
                'InstanceIds' => $instanceIds
            ));
        }
        return $result;
    }
}
//"PublicDnsName" => "ec2-18-222-190-135.us-east-2.compute.amazonaws.com"
//            "PublicIpAddress" => "18.222.190.135"
