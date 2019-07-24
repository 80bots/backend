<?php

namespace App;

use Auth;
use Aws\Ec2\Ec2Client;
use File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Nubs\RandomNameGenerator\All as AllRandomName;
use Nubs\RandomNameGenerator\Alliteration as AlliterationName;
use Nubs\RandomNameGenerator\Vgng as VideoGameName;
use Storage;

class AwsConnection extends BaseModel
{
    public static function AwsConnection()
    {
        $ec2Client = new Ec2Client([
            'region' => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key' => 'AKIAIO7MFUMEZ33ZDXKA',
                'secret' => '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',
            ],
        ]);
        return $ec2Client;
    }

    public static function AwsCreateKeyPair()
    {
        $ec2Client = self::AwsConnection();

        // Create Aws Pair Key
        $keyPairName = time() . '_psbt';
        $result = $ec2Client->createKeyPair(array(
            'KeyName' => $keyPairName
        ));

        #todo: upload to secure S3 bucket (private, highly-restricted env)

        $path = public_path("keys");
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 777, true, true);
        }

        // Save the private key
        $saveKeyLocation = "/home/www/storage/keys/" . time() . "_{$keyPairName}.pem";
        //$saveKeyLocation = 'keys/' . time() . "_{$keyPairName}.pem";
        $pemKey = $result->getPath('KeyMaterial');
        //Storage::disk('s3')->put($saveKeyLocation, $pemKey);

        file_put_contents($saveKeyLocation, $pemKey);
        // Update the key's permissions so it can be used with SSH
        chmod($saveKeyLocation, 0600);
        $return['path'] = $saveKeyLocation;
        $return['keyName'] = $keyPairName;
        return $return;
    }

    public static function AwsCreateTagName()
    {
        $generator = new AllRandomName([
            new AlliterationName(),
            new VideoGameName()
        ]);

        $randName = strtolower(str_replace(' ', '-', $generator->getName()));

        $name = $randName;
        $name = str_replace(' ', '', $name);
        $name = preg_replace('/[^A-Za-z\-]/', '', $name);
        $name = preg_replace('/-+/', '', $name);

        $numbers = rand(0, 9) . rand(0, 9);

        $name = $name . $numbers;

        return $name;
    }

    public static function AwsSetSecretGroupIngress($securityGroupName)
    {
        $clientIp = \Request::ip();
        $serverIp = @file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
        if ($serverIp === FALSE) {
            $serverIp = str_replace('http://', '', env('APP_URL'));
        }

        //$serverIp = file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
        $ec2Client = self::AwsConnection();
        // Set ingress rules for the security group
        $securityGroupIngress =
            $ec2Client->authorizeSecurityGroupIngress(array(
                'GroupName' => $securityGroupName,
                'IpPermissions' => array(
                    array(
                        'IpProtocol' => 'tcp',
                        'FromPort' => 6080,
                        'ToPort' => 6080,
                        'IpRanges' => array(
                            array('CidrIp' => '0.0.0.0/0')
                        ),
                    ),
                    array(
                        'IpProtocol' => 'tcp',
                        'FromPort' => 22,
                        'ToPort' => 22,
                        'IpRanges' => array(
                            array('CidrIp' => $serverIp . '/32')
                        ),
                    ),
                    array(
                        'IpProtocol' => 'tcp',
                        'FromPort' => 8080,
                        'ToPort' => 8080,
                        'IpRanges' => array(
                            array('CidrIp' => $serverIp . '/32')
                        ),
                    )
                )
            ));
        return $securityGroupIngress;
    }

    public static function AwsCreateSecretGroup()
    {
        $ec2Client = self::AwsConnection();

        $securityGroupName = time() . '_80bots';
        // Create the security group
        $result = $ec2Client->createSecurityGroup(array(
            'GroupName' => $securityGroupName,
            'Description' => 'Basic web server security'
        ));

        self::AwsSetSecretGroupIngress($securityGroupName);

        // Get the security group ID (optional)
        $securityGroupId = $result->get('GroupId');
        $return['securityGroupId'] = $securityGroupId;
        $return['securityGroupName'] = $securityGroupName;
        return $return;
    }

    public static function AwsLaunchInstance($keyPairName, $securityGroupName, $bot, $tagName, $user)
    {
        if ($bot) {
            $imageId = $bot->aws_ami_image_id ??  env('AWS_IMAGEID', 'ami-0cd3dfa4e37921605');
            $instanceType = $bot->aws_instance_type ?? env('AWS_INSTANCE_TYPE', 't2.micro');
            $volumeSize = $bot->aws_storage_gb ?? env('AWS_Volume_Size', '8');
            $userData = $bot->aws_startup_script ?? '';
            $botScript = $bot->aws_custom_script ?? '';
            $_shebang = '#!/bin/bash';
            $userData = "{$_shebang}\n {$userData}\n";
            $consoleOverrides = <<<HERECONSOLE
/*
const eighty_bots_fs = require('fs')
const eighty_bots_logStdOut = process.stdout
const eighty_bots_logStdErr = process.stderr
const eighty_bots_access = eighty_bots_fs.createWriteStream('~/node.access.log', { mode: 0o755, flags: 'a' })
const eighty_bots_errors = eighty_bots_fs.createWriteStream('~/node.errors.log', { mode: 0o755, flags: 'a' })
const eighty_bots_infos = eighty_bots_fs.createWriteStream('~/node.infos.log', { mode: 0o755, flags: 'a' })

console.log = (d) => {
    let _pid = process.pid
    let _date = [new Date().toISOString()];
    let message = \`[\\\${_date}]:: Process: _\\\${_pid}_ \\\${d} \\n\`
    eighty_bots_access.write(message)
    eighty_bots_logStdOut.write(message)
};

console.error = (d) => {
    let _pid = process.pid
    let shell = process.env.SHELL
    let _date = [new Date().toISOString()];
    let message = \`[\\\${_date}] Process: _\\\${_pid}_ \\\${shell} {\\\${__filename}}:: \\\${d} \\n\`
    eighty_bots_errors.write(message)
    eighty_bots_logStdErr.write(message)
};

console.info = (d) => {
    let _date = [new Date().toISOString()];
    let message = \`[\\\${_date}] {\\\${__filename}}:: \\\${d} \\n\`
    eighty_bots_infos.write(message)
    eighty_bots_logStdOut.write(message)
};
*/
HERECONSOLE;
            $botScript = "{$consoleOverrides}\n {$botScript}";

            if (!is_null($botScript) || !empty($botScript)) {
                $staticBotScript = <<<HERESHELL
file="script.js"
username="kabas"
cd /home/\$username/
if [ -f \$file ]
    then
    rm -rf \$file
fi

############## Output variable to script file ###############
cat > \$file <<EOF
{$botScript}
EOF
apt-get install dos2unix -y
dos2unix \$file
chown \$username:\$username \$file
chmod +x \$file
su - \$username -c "DISPLAY=:1 node \$file"
changedir() {
    cd /home/\$username
    frontail -p 9001 node.access.log
    frontail -p 9002 node.infos.log
    frontail -p 9003 node.errors.log
}
changedir
HERESHELL;
                $userData = "{$userData}\n {$staticBotScript}";
            }


            $userData = base64_encode($userData);

        } else {
            $imageId = env('AWS_IMAGEID', 'ami-0de51bde84cbc7049');
            $instanceType = env('AWS_INSTANCE_TYPE', 't3a.small');
            $volumeSize = env('AWS_Volume_Size', '16');
        }

        $ec2Client = self::AwsConnection();
        $tags = [
            [
                'Key' => 'Name',
                'Value' => $tagName,
            ],
        ];

        if ($user) {
            array_push($tags, [
                'Key' => 'User Email',
                'Value' => $user->email,
            ]);
        }

        $instanceLaunchRequest = array(
            'ImageId' => $imageId,
            'MinCount' => 1,
            'MaxCount' => 1,
            'BlockDeviceMappings' => array(
                array(
                    'DeviceName' => 'sdh',
                    'Ebs' => array(
                        'VolumeSize' => (int)$volumeSize
                    ),
                ),
            ),
            'InstanceType' => $instanceType,
            'KeyName' => $keyPairName,
            'TagSpecifications' => [
                [
                    'ResourceType' => 'instance',
                    'Tags' => $tags,
                ],
            ],
            'SecurityGroups' => array($securityGroupName)
        );

        if (isset($userData) && !empty($userData)) {
            $instanceLaunchRequest = array_add($instanceLaunchRequest, 'UserData', $userData);
        }

        $result = $ec2Client->runInstances($instanceLaunchRequest);

        return $result;
    }

    public static function waitUntil($instanceId)
    {
        $ec2Client = self::AwsConnection();

        $waitResponse = $ec2Client->waitUntil('InstanceRunning', ['InstanceIds' => $instanceId]);
        return $waitResponse;
    }

    public static function DescribeInstances($instanceIds)
    {
        $ec2Client = self::AwsConnection();

        // Describe the now-running instance to get the public URL
        $resultDescribe = $ec2Client->describeInstances(
            array(
                'InstanceIds' => $instanceIds,
            ));
        return $resultDescribe;
    }

    public static function StartInstance($instanceIds)
    {
        $ec2Client = self::AwsConnection();

        $result = $ec2Client->startInstances(array(
            'InstanceIds' => $instanceIds,
        ));

        return $result;
    }

    public static function StopInstance($instanceIds)
    {
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

    public static function AllocateAddresses($instanceId)
    {

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

    public static function InstanceMonitoring($instanceIds)
    {
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
        foreach ($StartUpScript as $script) {
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
