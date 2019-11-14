<?php

namespace App\Services;

use App\AwsSetting;
use App\Bot;
use App\BotInstance;
use App\Helpers\GeneratorID;
use App\MongoInstance;
use App\User;
use Aws\Ec2\Ec2Client;
use Aws\Exception\AwsException;
use Aws\Iam\Exception\IamException;
use Aws\Iam\IamClient;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\Transfer;
use Aws\ServiceQuotas\ServiceQuotasClient;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
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
     * @var IamClient
     */
    protected $iam;

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
     * @return string
     */
    public function getS3Bucket()
    {
        return $this->s3Bucket;
    }

    /**
     * @param string $region
     * @param array|null $credentials
     */
    public function iamConnection(string $region = '', array $credentials = null)
    {
        $this->iam = new IamClient([
            'region'        => empty($region) ? config('aws.region', 'us-east-2') : $region,
            'version'       => config('aws.version', 'latest'),
            'credentials'   => empty($credentials) ? config('aws.credentials') : $credentials
        ]);
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
     * @param string $name
     * @param string $email
     * @return array|null
     */
    public function createIamUser(string $name, string $email): ?array
    {
        if (empty($this->iam)) {
            $this->iamConnection();
        }

        $access = null;
        $user = null;

        try {

            $getUser = $this->iam->getUser([
                'UserName' => $name,
            ]);

            if ($getUser->hasKey('User')) {
                $user = $getUser->get('User');
            }
        } catch (IamException $exception) {
            Log::error("User {$name} Not Found");
        }

        if (empty($user)) {

            try {
                $result = $this->iam->CreateUser([
                    'UserName' => $name
                ]);

                $this->iam->addUserToGroup([
                    'GroupName' => config('aws.iam.group', 'saas-s3'),
                    'UserName' => $name,
                ]);

                $result = $this->iam->createAccessKey([
                    'UserName' => $name,
                ]);

                if ($result->hasKey('AccessKey')) {
                    $accessKey = $result->get('AccessKey');

                    $access = [
                        'key' => $accessKey['AccessKeyId'] ?? '',
                        'secret' => $accessKey['SecretAccessKey'] ?? '',
                    ];
                }
            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
            }
        }

        return $access;
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
            Log::error($exception->getMessage());
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

    /**
     * @param string $path
     * @param string $bucket
     */
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
     * Create a Security Group
     *
     * @param string|null $ip
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createSecretGroup(?string $ip): ?array
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
                $this->setSecretGroupIngress($ip, $securityGroupName);
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
     * @param string $securityGroupId
     * @return Result
     */
    public function describeSecurityGroups(string $securityGroupId): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->describeSecurityGroups([
            'GroupIds' => [$securityGroupId],
        ]);
    }

    /**
     * @param int $port
     * @param string $ip
     * @param string $ipProtocol
     * @param null $securityGroupId
     * @return Result
     */
    public function updateSecretGroupIngress(int $port, string $ip, string $ipProtocol = 'tcp', $securityGroupId = null): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->authorizeSecurityGroupIngress([
            'GroupId' => $securityGroupId,
            'IpPermissions' => [
                [
                    'IpProtocol' => $ipProtocol,
                    'FromPort' => $port,
                    'ToPort' => $port,
                    'IpRanges' => [
                        ['CidrIp' => "{$ip}/32"]
                    ],
                ],
            ]
        ]);
    }

    /**
     * Add an Ingress Rule
     *
     * @param string|null $ip
     * @param null $securityGroupName     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setSecretGroupIngress(?string $ip, $securityGroupName = null): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        $serverIp = config('app.env') === 'local' ? '0.0.0.0/0' : "{$ip}/32";

        $userPorts = config('aws.ports.access_user');
        $adminPorts = config('aws.ports.access_admin');

        $ipPermissions = [];

        foreach ($userPorts as $port) {
            array_push($ipPermissions, [
                'IpProtocol' => 'tcp',
                'FromPort' => $port,
                'ToPort' => $port,
                'IpRanges' => [
                    ['CidrIp' => $serverIp]
                ],
            ]);
        }

        foreach ($adminPorts as $adminPort) {
            array_push($ipPermissions, [
                'IpProtocol' => 'tcp',
                'FromPort' => $adminPort,
                'ToPort' => $adminPort,
                'IpRanges' => [
                    ['CidrIp' => '0.0.0.0/0'] // TODO: add admin IP
                ],
            ]);
        }

        // Set ingress rules for the security group
        return $this->ec2->authorizeSecurityGroupIngress([
            'GroupName' => $securityGroupName,
            'IpPermissions' => $ipPermissions
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
     * @param array|null $params
     * @return Result|null
     */
    public function launchInstance(Bot $bot, BotInstance $instance, User $user, string $keyPairName, string $securityGroupName, string $tagName, ?array $params): ?Result
    {
        Log::debug("AWS: start launch instance");

        $botInstanceDetail = $instance->details()->latest()->first();

        if (empty($botInstanceDetail)) {
            return null;
        }

        $region         = ! empty($instance->region) ? $instance->region->code : config('aws.region', 'us-east-2');
        $imageId        = $botInstanceDetail->aws_image_id ?? config('aws.image_id');
        $instanceType   = $botInstanceDetail->aws_instance_type ?? config('aws.instance_type');
        $volumeSize     = $botInstanceDetail->aws_storage_gb ?? config('aws.volume_size');

        $userData       = '';

        if (! empty($params)) {

            $formattedParams = [];

            foreach ($params as $key => $param) {
                $formattedParams[$key] = [
                     'value' => $param
                ];
            }

            $formattedParams['userEmail'] = [
                'value' => $user->email ?? ''
            ];

            $formattedParams['instanceId'] = [
                'value' => $instance->id ?? ''
            ];

            $userData = base64_encode("#!/bin/bash\n{$this->startupScript(json_encode($formattedParams), $bot->path ?? '')}");
        }

        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        $tags = [
            [
                'Key'   => 'Name',
                'Value' => $tagName,
            ],
            [
                'Key'   => 'User Email',
                'Value' => $user->email ?? '',
            ],
            [
                'Key'   => 'Bot',
                'Value' => $bot->name ?? '',
            ]
        ];

        $instanceLaunchRequest = $this->getInstanceLaunchRequest(
            $imageId,
            $volumeSize,
            $instanceType,
            $keyPairName,
            $tags,
            $securityGroupName,
            $userData
        );

        Log::debug("Instance Launch Request");
        Log::debug(print_r($instanceLaunchRequest, true));

        return $this->ec2->runInstances($instanceLaunchRequest);
    }

    /**
     * @param MongoInstance $instance
     * @param string $keyPairName
     * @param string $securityGroupName
     * @return Result
     */
    public function restoreInstance(MongoInstance $instance, string $keyPairName, string $securityGroupName): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($instance->aws_region);
        }

        $params = array_merge([
            'userEmail'     => $instance->tag_user_email ?? '',
            'instanceId'    => $instance->instance_id ?? '',
        ], $instance->params);

        $userData = $this->createUserData($params, $instance->bot_path);

        $tags = [
            [
                'Key'   => 'Name',
                'Value' => $instance->tag_name,
            ],
            [
                'Key'   => 'User Email',
                'Value' => $instance->tag_user_email ?? '',
            ],
            [
                'Key'   => 'Bot',
                'Value' => $instance->bot_name ?? '',
            ]
        ];

        $instanceLaunchRequest = $this->getInstanceLaunchRequest(
            $instance->aws_image_id,
            $instance->aws_storage_gb,
            $instance->aws_instance_type,
            $keyPairName,
            $tags,
            $securityGroupName,
            $userData
        );

        Log::debug("Instance Restore From DB");
        Log::debug(print_r($instanceLaunchRequest, true));

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

                if (! empty($instances)) {

                    foreach ($instances as $instance) {

                        try {

                            $name   = null;
                            $email  = null;
                            $bot    = null;

                            if( isset($instance['Tags']) && count($instance['Tags'])) {
                                foreach ($instance['Tags'] as $key => $tag) {
                                    if($tag['Key'] === 'Name') {
                                        $name = $tag['Value'];
                                    } elseif ($tag['Key'] === 'User Email') {
                                        $email = $tag['Value'];
                                    } elseif ($tag['Key'] === 'Bot') {
                                        $bot = $tag['Value'];
                                    }
                                }
                            }

                            if (empty($email) || in_array($name, $this->ignore)) {
                                continue;
                            }

                            $paramsDescribeVolumes = [];

                            foreach ($instance['BlockDeviceMappings'] as $blockDeviceMapping) {
                                $paramsDescribeVolumes[] = $blockDeviceMapping['Ebs']['VolumeId'];
                            }

                            $instancesByStatus['data'][$instance['State']['Name']][] = [
                                'tag_name'                => $name,
                                'tag_user_email'          => $email,
                                'tag_bot_name'            => $bot,
                                'aws_instance_id'         => $instance['InstanceId'],
                                'aws_image_id'            => $instance['ImageId'],
                                'aws_instance_type'       => $instance['InstanceType'],
                                'aws_key_name'            => $instance['KeyName'],
                                'aws_launch_time'         => $instance['LaunchTime'],
                                'aws_security_group_id'   => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupId'] : null,
                                'aws_security_group_name' => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupName'] : null,
                                'aws_public_ip'           => $instance['PublicIpAddress'] ?? null,
                                'aws_public_dns'          => $instance['PublicDnsName'] ?? null,
                                'aws_volumes_params'      => $paramsDescribeVolumes,
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
     * @param string $region
     * @param array $volumes
     * @return Result
     */
    public function describeVolumes(string $region, array $volumes): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        return $this->ec2->describeVolumes([
            'VolumeIds' => $volumes
        ]);
    }

    /**
     * @param array $instanceIds
     * @return void
     */
    public function waitUntil(array $instanceIds)
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        $this->ec2->waitUntil('InstanceRunning', ['InstanceIds' => $instanceIds]);
    }

    /**
     * @param string $instanceId
     * @return Result
     */
    public function describeOneInstanceStatus(string $instanceId): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }

        return $this->ec2->describeInstanceStatus([
            'Filters' => [
                [
                    'Name' => 'instance-status.status',
                    'Values' => ['impaired'],
                ],
            ],
            'InstanceIds' => [$instanceId]
        ]);
    }

    /**
     * @param array $instanceIds
     * @param string $region
     * @return Result
     */
    public function describeInstances(array $instanceIds, string $region): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        // Describe the now-running instance to get the public URL
        return $this->ec2->describeInstances([ 'InstanceIds' => $instanceIds ]);
    }

    /**
     * @param string $region
     * @param array $parameters
     * @return Result
     */
    public function describeInstanceStatus(string $region, array $parameters): Result
    {
        if (empty($this->ec2)) {
            $this->ec2Connection($region);
        }

        return $this->ec2->describeInstanceStatus($parameters);
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
     * @return array
     */
    public function runStartUpScript(): array
    {
//        if (empty($this->ec2)) {
//            $this->ec2Connection();
//        }

//        $cred = [
//            'credentials' => config('aws.credentials'),
//            //'service' => 'ec2',
//            'region'  => config('aws.region', 'us-east-2'),
//            'version' => config('aws.version', 'latest')
//        ];

//        $ec2 = new Ec2Client($cred);
//        //$client = new AwsClient($cred);
//
//        $instanceIds = ['i-0554762900be26c9a'];
//
//        $result = $ec2->getApi();
//
//        Log::debug(print_r($result['operations'], true));
//
//        dd($result['operations']);

//        exec('mkdir -p Shell');
//        chdir('Shell');
//        $returnArr['status'] = [];
//
//        foreach ($StartUpScript as $script) {
//            exec($script, $output, $return);
//            if (! $return) {
//                array_push($returnArr['status'], 'Success');
//            } else {
//                array_push($returnArr['status'], 'Fail');
//            }
//        }
//
//        return $returnArr;
        return [];
    }

    /**
     * @param string $params
     * @param string $path
     * @return string
     */
    protected function startupScript(string $params = '', string $path = ''): string
    {
        // scripts performing after reloading server
        $shell = <<<HERESHELL
############## Output to startup.sh file ###############
shellFile="startup.sh"
cat > \$shellFile <<EOF
#!/bin/bash
su - \$username -c 'cd ~/data-streamer && git pull && yarn && yarn build && pm2 start --name "data-streamer" yarn -- start'
su - \$username -c 'cd ~/puppeteer && yarn && DISPLAY=:1 node {$path} > /dev/null'
EOF
chmod +x \$shellFile && chown \$username:\$username \$shellFile
HERESHELL;

        // This file is performed after reloading server
        $rc = <<<HERESHELL
############## Output to /etc/rc.local file ###############
rcFile="/etc/rc.local"
cat > \$rcFile <<EOF
#!/bin/bash
/home/\$username/\$shellFile
exit 0
EOF
chmod +x \$rcFile
HERESHELL;

        $accessKey = config('aws.iam.access_key');
        $secretKey = config('aws.iam.secret_key');

        // file with credentials to AWS S3
        $credentials = <<<HERESHELL
############## Output to credentials.json file ###############
credentialsFile="credentials.json"
cat > \$credentialsFile <<EOF
{
    "access": "{$accessKey}",
    "secret": "{$secretKey}",
}
EOF
chown \$username:\$username \$credentialsFile
HERESHELL;

        $settings = AwsSetting::isDefault()->first();
// run data-streamer first ($settings->script)
        return <<<HERESHELL
{$settings->script}
{$shell}
{$rc}
{$credentials}
############## Output user params to params.json file ###############
su - \$username -c 'echo "starting script {$path}"'
su - \$username -c 'rm -rf ~/.screenshots/*'
su - \$username -c 'cd ~/puppeteer && git pull'
cat > \$file <<EOF
{$params}
EOF
su - \$username -c 'cd ~/puppeteer && yarn && mkdir logs && DISPLAY=:1 node {$path} > /dev/null'
HERESHELL;
    }

    /**
     * @param $imageId
     * @param $volumeSize
     * @param $instanceType
     * @param $keyPairName
     * @param $tags
     * @param $securityGroupName
     * @param $userData
     * @return array
     */
    protected function getInstanceLaunchRequest(
        string $imageId,
        int $volumeSize,
        string $instanceType,
        string $keyPairName,
        array $tags,
        string $securityGroupName,
        string $userData = ''): array
    {
        return [
            'ImageId'   => $imageId,
            'MinCount'  => 1,
            'MaxCount'  => 1,
            'InstanceType'  => $instanceType,
            'KeyName'       => $keyPairName,
            'TagSpecifications' => [
                [
                    'ResourceType' => 'instance',
                    'Tags' => $tags,
                ],
            ],
            'SecurityGroups'    => [$securityGroupName],
            'UserData'          => $userData
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
            return '0.0.0.0';
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

    /**
     * @param string $region
     * @param array|null $credentials
     * @return Result
     */
    public function getServiceQuotasT3MediumInstance(string $region, array $credentials = null): Result
    {
        $sqc = new ServiceQuotasClient([
            'region'        => empty($region) ? config('aws.region', 'us-east-2') : $region,
            'version'       => config('aws.version', 'latest'),
            'credentials'   => empty($credentials) ? config('aws.credentials') : $credentials
        ]);

        return $sqc->getServiceQuota([
            "QuotaCode"     => config('aws.quota.code_t3_medium'),
            "ServiceCode"   => config('aws.services.ec2.code'),
        ]);
    }

    /**
     * @return array|null
     */
    protected function getEc2InstanceTypes(): ?array
    {
        if (empty($this->ec2)) {
            $this->ec2Connection();
        }
        // TODO: need to get available instance types here via pricing API

        return null;
    }

    /**
     * @param $instanceId
     * @param $images
     * @return array|null
     */
    public function uploadScreenshots($instanceId, $images): ?array
    {
        $result = [];

        if(empty($this->s3)) {
            $this->s3Connection('us-east-2', null,'80bots-issued-screenshots');
        }

        foreach ($images as $image) {
            $saveKeyLocation = "screenshots/{$instanceId}/{$image->getClientOriginalName()}";
            $bucket = empty($bucket) ? $this->s3Bucket : $bucket;

            // Save the private key
            $res = $this->s3->putObject([
                'Bucket'      => $bucket,
                'Key'         => $saveKeyLocation,
                'Body'        => $image->get(),
                'ContentType' => $image->getClientMimeType()
            ]);

            $result[] = $res['ObjectURL'];
        }
        return $result;
    }

    /**
     * @param string $bucket
     * @param string $key
     * @param string $saveAs
     * @return Result
     */
    public function getS3Object(string $bucket, string $key, string $saveAs = ''): Result
    {
        $params = [
            'Bucket'    => $bucket,
            'Key'       => $key
        ];

        if (! empty($saveAs)) {
            $params['SaveAs'] = $saveAs;
        }

        return $this->s3->getObject($params);
    }

    /**
     * @param string $bucket
     * @param int $limit
     * @param string|null $prefix ('streamer-data/2019-10-06')
     * @param string|null $next
     * @return Result
     */
    public function getS3ListObjects(string $bucket, int $limit, string $prefix = null, string $next = null): Result
    {
        $params = [
            'Bucket'  => $bucket,
            'MaxKeys' => $limit
        ];

        if (! empty($prefix)) {
            $params['Prefix'] = $prefix;
        }

        if (! empty($next)) {
            $params['ContinuationToken'] = $next;
        }

        return $this->s3->listObjectsV2($params);
    }

    /**
     * @param Collection $keys
     * @return array
     */
    public function getS3Objects(Collection $keys): array
    {
        $data = [];

        foreach ($keys as $key) {

            $promise = $this->s3->getObjectAsync([
                'Bucket' => '80bots',
                'Key' => $key,
            ]);

            try {

                $result = $promise->wait();
                array_push($data, [
                    'type' => $result->get('ContentType'),
                    'body'  => $result->get('Body')
                ]);

            } catch (AwsException $exception) {
                // Handle the error
                Log::error($exception->getMessage());
            }
        }

        return $data;
    }

    /**
     * @param string $bucket
     * @param string $path
     */
    public function s3TransferFolder(string $bucket, string $path)
    {
        $source = "s3://{$bucket}/{$path}";
        $dest = storage_path('logs/transfer');
        $manager = new Transfer($this->s3, $source, $dest);
        $manager->transfer();
    }

    /**
     * @param string $bucket
     * @param string $key
     * @return string
     */
    public function getPresignedLink(string $bucket, string $key): string
    {
//        if (! $this->s3->doesObjectExist($bucket, $key)) {
//            return '';
//        }

        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket'    => $bucket,
            'Key'       => $key
        ]);

        $request = $this->s3->createPresignedRequest($cmd, '+60 minutes');

        return (string)$request->getUri();
    }

    private function createUserData(array $params, string $path): string
    {
        if (! empty($params)) {

            $formattedParams = [];

            foreach ($params as $key => $param) {
                $formattedParams[$key] = [
                    'value' => $param
                ];
            }

            return base64_encode("#!/bin/bash\n{$this->startupScript(json_encode($formattedParams), $path ?? '')}");
        }

        return '';
    }
}
