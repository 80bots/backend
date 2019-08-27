<?php

namespace App\Services;

use App\Bot;
use App\Helpers\GeneratorID;
use App\User;
use App\BotInstance;
use Aws\Ec2\Ec2Client;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Nubs\RandomNameGenerator\All as AllRandomName;
use Nubs\RandomNameGenerator\Alliteration as AlliterationName;
use Nubs\RandomNameGenerator\Vgng as VideoGameName;
use Throwable;

class Aws
{
    /**
     * @var Ec2Client
     */
    protected $ec2;

    /**
     * @var S3Client
     */
    protected $s3;

    /**
     * @var string
     */
    protected $s3Bucket;

    /**
     * @var array
     */
    protected $ignore;

    /**
     * @param string $region
     * @param array|null $credentials
     * @return void
     */
    public function ec2Connection(string $region = '', array $credentials = null): void
    {
        $this->ec2 = new Ec2Client([
            'region'        => empty($region) ? config('aws.region', 'us-east-2') : $region,
            'version'       => config('aws.version', 'latest'),
            'credentials'   => empty($credentials) ? config('aws.credentials') : $credentials
        ]);

        $this->ignore   = config('aws.instance_ignore');
    }

    /**
     * @param string $region
     * @param array|null $credentials
     * @param string $bucket
     * @return void
     */
    public function s3Connection(string $region = '', array $credentials = null, string $bucket = ''): void
    {
        $this->s3 = new S3Client([
            'region'        => empty($region) ? config('aws.region', 'us-east-2') : $region,
            'version'       => config('aws.version', 'latest'),
            'credentials'   => empty($credentials) ? config('aws.credentials') : $credentials
        ]);

        $this->s3Bucket = empty($bucket) ? config('aws.bucket') : $bucket;
    }

    /**
     * @return array
     */
    public static function getEc2Regions(): array
    {
        $ec2 = new Ec2Client([
            'region'        => empty($region) ? config('aws.region', 'us-east-2') : $region,
            'version'       => config('aws.version', 'latest'),
            'credentials'   => empty($credentials) ? config('aws.credentials') : $credentials
        ]);

        try {

            $result = $ec2->describeRegions();

            if ($result->hasKey('Regions')) {
                return collect($result->get('Regions'))->map(function ($item, $key) {
                    return $item['RegionName'] ?? '';
                })->toArray();
            }

            return [];

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return [];
        }
    }

    /**
     * @return array
     */
    public function getEc2RegionsWithName(): array
    {
        $regions = self::getEc2Regions();

        if (! empty($regions)) {

            try {

                $client = new Client;
                $res = $client->request('GET', 'https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html', []);

                if ($res->getStatusCode() === 200) {

                    $content = $res->getBody()->getContents();

                    $pattern = '/<p><code class="code">(.*)<\/code><\/p>\s*<\/td>\s*<td>\s*<p>(.*)<\/p>/im';

                    if (preg_match_all($pattern, $content, $matches)) {
                        $codes = $matches[1];
                        $names = $matches[2];

                        $result = [];

                        foreach ($codes as $key => $code) {
                            if (in_array($code, $regions)) {
                                $result[] = [
                                    'code'  => $code,
                                    'name'  => $names[$key]
                                ];
                            }
                        }

                        return $result;
                    }

                    return [];
                }

            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
                return [];
            }
        }

        return [];
    }

    /**
     * Create a Key Pair
     *
     * @param string $bucket
     * @return array|null
     */
    public function createKeyPair(string $bucket = ''): ?array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        // Create Aws Pair Key
        $random = GeneratorID::generate();
        $keyPairName = "{$random}_psbt";
        $result = $this->ec2->createKeyPair([
            'KeyName' => $keyPairName
        ]);

        #todo: upload to secure S3 bucket (private, highly-restricted env)

        if ($result->hasKey('KeyMaterial')) {

            $pemKey = $result->get('KeyMaterial');
            $saveKeyLocation = "keys/{$keyPairName}.pem";

            if (empty($this->s3)) {
                $this->s3Connection();
            }

            $bucket = empty($bucket) ? $this->s3Bucket : $bucket;

            // Save the private key
            $res = $this->s3->putObject([
                'Bucket'    => $bucket,
                'Key'       => $saveKeyLocation,
                'Body'      => $pemKey
            ]);

            if ($res->hasKey('ObjectURL')) {
                return [
                    'path'      => $saveKeyLocation,
                    'keyName'   => $keyPairName
                ];
            }

            return null;
        }

        return null;
    }

    /**
     * @param string $path
     * @param string $bucket
     * @return Result|null
     */
    public function getKeyPairObject(string $path, string $bucket = ''): ?Result
    {
        if (empty($this->s3)) {
            $this->s3Connection();
        }

        $bucket = empty($bucket) ? $this->s3Bucket : $bucket;

        try {
            return $this->s3->getObject([
                'Bucket'    => $bucket,
                'Key'       => $path
            ]);
        } catch (S3Exception $exception) {
            return null;
        }
    }

    /**
     * @param string $name
     */
    public function deleteKeyPair(string $name): void
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        try {
            $result = $this->ec2->describeKeyPairs([
                'KeyNames' => [$name]
            ]);

            if ($result->hasKey('KeyPairs')) {
                $this->ec2->deleteKeyPair([
                    'KeyName' => $name,
                ]);
            }
        } catch (Throwable $throwable) {
            Log::error("KeyPair ({$name}) removal is impossible");
        }
    }

    public function deleteS3KeyPair(string $path, string $bucket = ''): void
    {
        if (empty($this->s3)) {
            $this->s3Connection();
        }

        $bucket = empty($bucket) ? $this->s3Bucket : $bucket;

        try {
            $result = $this->s3->getObject([
                'Bucket'    => $bucket,
                'Key'       => $path
            ]);

            if ($result->hasKey('Body')) {
                $this->s3->deleteObject([
                    'Bucket'    => $bucket,
                    'Key'       => $path
                ]);
            }
        } catch (S3Exception $exception) {
            Log::error("KeyPair ({$path}) removal is impossible");
        }
    }

    /**
     * @param string $groupId
     * @param string $groupName
     * @return bool
     */
    public function deleteSecurityGroup(string $groupId, string $groupName = ''): bool
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        try {

            $result = $this->ec2->describeSecurityGroups([
                'GroupIds' => [$groupId]
            ]);

            if ($result->hasKey('SecurityGroups')) {

                $res = $this->ec2->deleteSecurityGroup([
                    'GroupId'   => $groupId,
                    'GroupName' => $groupName
                ]);

                if ($res->hasKey('@metadata')) {
                    $meta = $res->get('@metadata');

                    Log::debug("deleteSecurityGroup @metadata => {$meta['statusCode']}");

                    return $meta['statusCode'] === 200;
                }

                return false;
            }

            return false;

        } catch (Throwable $throwable) {
            if (strpos($throwable->getMessage(), "<Code>InvalidGroup.NotFound</Code>")) {
                return true;
            }
            Log::error("SecurityGroups ({$groupId}) removal is impossible");
            return false;
        }
    }

    /**
     * @return Result
     */
    public function getListKeyPairs(): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->describeKeyPairs();
    }

    /**
     * The random string with number
     * @return string
     */
    public function createTagName(): string
    {
        $generator = new AllRandomName([
            new AlliterationName(),
            new VideoGameName()
        ]);

        return strtolower(str_replace(' ', '', $generator->getName())) . rand(10,99);
    }

    /**
     * Create a Security Group
     *
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createSecretGroup(): ?array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        $random = GeneratorID::generate();
        $securityGroupName = "{$random}_80bots";

        try {

            // Create the security group
            $result = $this->ec2->createSecurityGroup([
                'GroupName'     => $securityGroupName,
                'Description'   => 'Basic web server security.'
            ]);

            if ($result->hasKey('GroupId')) {
                $this->setSecretGroupIngress($securityGroupName);
                // Get the security group ID (optional)
                return [
                    'securityGroupId'   => $result->get('GroupId'),
                    'securityGroupName' => $securityGroupName,
                    'result'            => $result
                ];
            }

        } catch (Throwable $throwable) {
            Log::error("File: {$throwable->getFile()} / Line: {$throwable->getLine()} / {$throwable->getMessage()}");
        }

        return null;
    }

    /**
     * Add an Ingress Rule
     *
     * @param null $securityGroupName
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setSecretGroupIngress($securityGroupName = null): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        $serverIp = $this->getServerIp();

        // Set ingress rules for the security group
        return $this->ec2->authorizeSecurityGroupIngress([
            'GroupName' => $securityGroupName,
            'IpPermissions' => [
                [
                    'IpProtocol' => 'tcp',
                    'FromPort' => 6080,
                    'ToPort' => 6080,
                    'IpRanges' => [
                       ['CidrIp' => '0.0.0.0/0']
                    ],
                ],
                [
                    'IpProtocol' => 'tcp',
                    'FromPort' => 22,
                    'ToPort' => 22,
                    'IpRanges' => [
                        ['CidrIp' => $serverIp . '/32']
                    ],
                ],
                [
                    'IpProtocol' => 'tcp',
                    'FromPort' => 8080,
                    'ToPort' => 8080,
                    'IpRanges' => [
                        ['CidrIp' => $serverIp . '/32']
                    ],
                ]
            ]
        ]);
    }

    /**
     * Launch EC2 Instance
     *
     * @param Bot $bot
     * @param BotInstance $instance
     * @param User $user
     * @param string $keyPairName
     * @param string $securityGroupName
     * @param string $tagName
     * @return Result|null
     */
    public function launchInstance(Bot $bot, BotInstance $instance, User $user, string $keyPairName, string $securityGroupName, string $tagName): ?Result
    {
        $botDetail = $instance->details()->latest()->first();

        if (empty($botDetail)) {
            return null;
        }

        $imageId            = $botDetail->aws_image_id ?? config('aws.image_id');
        $instanceType       = $botDetail->aws_instance_type ?? config('aws.instance_type');
        $volumeSize         = $botDetail->aws_storage_gb ?? config('aws.volume_size');
        $userData           = $bot->aws_startup_script ?? '';
        $botScript          = $bot->aws_custom_script ?? '';

        $_shebang           = '#!/bin/bash';
        $userData           = "{$_shebang}\n {$userData}\n";
        $consoleOverrides   = $this->getConsoleOverrides();

        $botScript = "{$consoleOverrides}\n {$botScript}";

        if (! is_null($botScript) || ! empty($botScript)) {
            $staticBotScript    = $this->getStaticBotScript($botScript);
            $userData           = "{$userData}\n {$staticBotScript}";
        }

        $userData = base64_encode($userData);

        if (empty($this->ec2)) {
            $this->ec2Connection($instance->region->code);
        }

        $tags = [
            [
                'Key' => 'Name',
                'Value' => $tagName,
            ],
        ];

        if (! empty($user)) {
            array_push($tags, [
                'Key' => 'User Email',
                'Value' => $user->email ?? '',
            ]);
        }

        $instanceLaunchRequest = $this->getInstanceLaunchRequest($imageId, $volumeSize, $instanceType, $keyPairName, $tags, $securityGroupName);

        if (isset($userData) && !empty($userData)) {
            $instanceLaunchRequest = Arr::add($instanceLaunchRequest, 'UserData', $userData);
        }

        return $this->ec2->runInstances($instanceLaunchRequest);
    }

    /**
     * @param string $region
     * @param int $limit This value can be between 5 and 1000.
     * @param string $token
     * @return array|null
     */
    public function sync(string $region, int $limit = 5, string $token = ''): array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        if (! empty($token)) {
            $params = ['NextToken' => $token];
        } else {
            $params = ['MaxResults' => $limit];
        }

        $nextToken = null;

        // Describes all of AWS account's instances.
        $result = $this->ec2->describeInstances($params);

        if ($result->hasKey('NextToken')) {
            $nextToken = $result->get('NextToken');
        }

        if ($result->hasKey('Reservations')) {

            $instancesByStatus = [
                'data'      => [],
                'nextToken' => $nextToken
            ];

            foreach ($result->get('Reservations') as $reservation) {

                $instances = $reservation['Instances'];

                if ($instances) {

                    foreach ($instances as $instance) {

                        try {

                            $name  = null;
                            $email = null;

                            if( isset($instance['Tags']) && count($instance['Tags'])) {
                                foreach ($instance['Tags'] as $key => $tag) {
                                    if(isset($tag['Key']) && $tag['Key'] == 'Name') {
                                        $name = $tag['Value'];
                                    }
                                    if(isset($tag['Key']) && $tag['Key'] == 'User Email') {
                                        $email = $tag['Value'];
                                    }
                                }
                            }

                            if(! empty($name) && in_array($name, $this->ignore)) {
                                continue;
                            }

                            $instancesByStatus['data'][$instance['State']['Name']][] = [
                                'tag_name'                => $name,
                                'tag_user_email'          => $email,
                                'aws_instance_id'         => $instance['InstanceId'],
                                'aws_ami_id'              => $instance['ImageId'],
                                'aws_security_group_id'   => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupId'] : null,
                                'aws_security_group_name' => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupName'] : null,
                                'aws_public_ip'           => $instance['PublicIpAddress'] ?? null,
                                'aws_public_dns'          => $instance['PublicDnsName'] ?? null,
                                'created_at'              => date('Y-m-d H:i:s', strtotime($instance['LaunchTime']))
                            ];

                        } catch (Throwable $throwable) {
                            Log::error($throwable->getMessage());
                            Log::error('An error occurred while syncing '. $instance['InstanceId']);
                        }
                    }
                }
            }

            return $instancesByStatus;
        }

        return [];
    }

    /**
     * @param $instanceId
     * @return mixed
     */
    public function waitUntil($instanceId)
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->waitUntil('InstanceRunning', ['InstanceIds' => $instanceId]);
    }

    /**
     * @param $instanceIds
     * @return Result
     */
    public function describeInstances($instanceIds): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        // Describe the now-running instance to get the public URL
        return $this->ec2->describeInstances([ 'InstanceIds' => $instanceIds ]);
    }

    /**
     * @param $instanceIds
     * @return Result
     */
    public function startInstance($instanceIds): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->startInstances([ 'InstanceIds' => $instanceIds ]);
    }

    /**
     * @param $instanceIds
     * @return Result
     */
    public function stopInstance($instanceIds): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->stopInstances([ 'InstanceIds' => $instanceIds ]);
    }

    /**
     * @param $instanceIds
     * @return Result
     */
    public function terminateInstance($instanceIds): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->terminateInstances([
            'DryRun'        => false,
            'InstanceIds'   => $instanceIds,
        ]);
    }

    /**
     * @param $instanceId
     * @return Result|null
     */
    public function allocateAddresses($instanceId): ?Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        $allocation = $this->ec2->allocateAddress([
            'DryRun' => false,
            'Domain' => 'vpc',
        ]);

        if ($allocation->hasKey('AllocationId')) {
            return $this->ec2->associateAddress([
                'DryRun'        => false,
                'InstanceId'    => $instanceId,
                'AllocationId'  => $allocation->get('AllocationId')
            ]);
        }

        return null;
    }

    /**
     * @param $instanceIds
     * @param string $monitorInstance
     * @return Result
     */
    public function instanceMonitoring($instanceIds, $monitorInstance = 'ON'): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        if ($monitorInstance == 'ON') {
            return $this->ec2->monitorInstances([ 'InstanceIds' => $instanceIds ]);
        } else {
            return $this->ec2->unmonitorInstances([ 'InstanceIds' => $instanceIds ]);
        }
    }

    /**
     * @param $StartUpScript
     * @return array
     */
    public function runStartUpScript($StartUpScript): array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        exec('mkdir -p Shell');
        chdir('Shell');
        $returnArr['status'] = [];

        foreach ($StartUpScript as $script) {
            exec($script, $output, $return);
            if (! $return) {
                array_push($returnArr['status'], 'Success');
            } else {
                array_push($returnArr['status'], 'Fail');
            }
        }

        return $returnArr;
    }

    /**
     * @param $botScript
     * @return string
     */
    protected function getStaticBotScript($botScript): string
    {
        return <<<HERESHELL
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
    }

    /**
     * @return string
     */
    protected function getConsoleOverrides(): string
    {
        return <<<HERECONSOLE
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
    }

    /**
     * @param $imageId
     * @param $volumeSize
     * @param $instanceType
     * @param $keyPairName
     * @param $tags
     * @param $securityGroupName
     * @return array
     */
    protected function getInstanceLaunchRequest($imageId, $volumeSize, $instanceType, $keyPairName, $tags, $securityGroupName): array
    {
        return [
            'ImageId'   => $imageId,
            'MinCount'  => 1,
            'MaxCount'  => 1,
            'BlockDeviceMappings' => [
                [
                    'DeviceName' => 'sdh',
                    'Ebs' => [
                        'VolumeSize' => (int)$volumeSize
                    ],
                ],
            ],
            'InstanceType'  => $instanceType,
            'KeyName'       => $keyPairName,
            'TagSpecifications' => [
                [
                    'ResourceType' => 'instance',
                    'Tags' => $tags,
                ],
            ],
            'SecurityGroups' => [$securityGroupName]
        ];
    }

    /**
     * Get Instance Metadata (public-ipv4)
     * @url https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-instance-metadata.html
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getServerIp(): ?string
    {
        if(config('app.env') === 'local') {
            return '127.0.0.1';
        } else {
            // To view all categories of instance metadata from within a running instance, use the following URI:
            $client = new Client(['base_uri' => config('aws.instance_metadata')]);
            try {
                $response = $client->request('GET', 'public-ipv4');
                if ($response->getStatusCode() === 200) {
                    $content = $response->getBody()->getContents();
                    if (!empty($content) && is_string($content)) {
                        return $content;
                    }
                }
                return null;
            } catch (RequestException $exception) {
                Log::error("File: {$exception->getFile()} / {$exception->getMessage()}");
                return null;
            }
        }
    }

    /**
     * @param string $region
     * @param string $owner
     * @return Result|null
     */
    public function describeImages(string $region, string $owner): ?Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        try {
            return $this->ec2->describeImages([
                'Filters' => [
                    ['Name' => 'owner-id', 'Values' => [$owner]],
                    //['Name' => 'image-id', 'Values' => ['ami-0de51bde84cbc7049']]
                ]
            ]);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return null;
        }
    }

    /**
     * @param string $region
     * @return Result|null
     */
    public function getEc2AccountAttributes(string $region): ?Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        try {
            return $this->ec2->describeAccountAttributes();
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return null;
        }
    }

    protected function getEc2InstanceTypes(): ?array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }
        // TODO: need to get available instance types here via pricing API
    }
}
