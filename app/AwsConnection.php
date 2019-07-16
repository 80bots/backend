<?php

namespace App;

use Aws\Ec2\Ec2Client;
use Illuminate\Database\Eloquent\Model;
use File;

class AwsConnection extends BaseModel
{
    public static function AwsConnection(){
        $ec2Client = new Ec2Client([
            'region' => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key'    => 'AKIAQOGPXKZ2FNR2TFXX',
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

        $path = public_path("uploads/ssh_keys");
        if(!File::isDirectory($path)){
            File::makeDirectory($path, 777, true, true);
        }

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
            $userData = isset($bots->aws_startup_script) ? base64_encode($bots->aws_startup_script) : '';
        } else {
            $imageId = env('AWS_IMAGEID','ami-0cd3dfa4e37921605');
            $instanceType = env('AWS_INSTANCE_TYPE', 't2.micro');
            $volumeSize = env('AWS_Volume_Size', '8');
        }
        $ec2Client = self::AwsConnection();

        $instanceLaunchRequest = array(
            'ImageId'        => 'ami-082b5a644766e0e6f',
            'MinCount'       => 1,
            'MaxCount'       => 1,
            'BlockDeviceMappings'     => array(
                array(
                    'DeviceName' => 'sdh',
                    'Ebs' => array(
                        'VolumeSize' => (int) $volumeSize
                    ),
                ),
            ),
            'InstanceType'   => $instanceType,
            'KeyName'        => $keyPairName,
            'SecurityGroups' => array($securityGroupName)
        );
        if(!empty($userData) && isset($userData)){
            $instanceLaunchRequest = array_add($instanceLaunchRequest,'UserData', $userData);
        }
//        dd($instanceLaunchRequest);

            $result = $ec2Client->runInstances($instanceLaunchRequest);

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

    public static function RunStartUpScript($StartUpScript)
    {
        $ec2Client = self::AwsConnection();

        $ec2Client->

        /*exec('aws configure');*/
        exec('mkdir -p Shell');
        chdir('Shell');
        $returnArr['status'] = [];
        foreach ($StartUpScript as $script){
            exec($script, $output, $return);
            if (!$return) {
                array_push($returnArr['status'], 'Success');
            } else {
                array_push($returnArr['status'], 'Fail');
            }
        }
        return $returnArr;
    }
}
//"PublicDnsName" => "ec2-18-222-190-135.us-east-2.compute.amazonaws.com"
//            "PublicIpAddress" => "18.222.190.135"
