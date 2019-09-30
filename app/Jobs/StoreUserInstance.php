<?php

namespace App\Jobs;

use App\Bot;
use App\Events\InstanceLaunched;
use App\Helpers\CreditUsageHelper;
use App\Services\Aws;
use App\User;
use App\BotInstance;
use App\BotInstancesDetails;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class StoreUserInstance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const START_INSTANCE_CREDIT = 1;

    /**
     * @var Bot
     */
    protected $bot;

    /**
     * @var BotInstance
     */
    protected $instance;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $region;

    /**
     * @var array
     */
    protected $params;

    /**
     * Create a new job instance.
     * @param Bot $bot
     * @param BotInstance $instance
     * @param User $user
     * @param array|null $params
     */
    public function __construct(Bot $bot, BotInstance $instance, User $user, ?array $params)
    {
        $this->bot      = $bot;
        $this->instance = $instance;
        $this->user     = $user;
        $regions        = Aws::getEc2Regions();
        $this->params   = $params;

        if (! empty($regions) && in_array($instance->region->code, $regions)) {
            $this->region = $instance->region->code;
        } else {
            $this->region = config('aws.region', 'us-east-2');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting instance for ' . $this->instance->id ?? '');

        try {
            ini_set('memory_limit', '-1');

            $aws = new Aws;
            $aws->ec2Connection($this->region);

            Log::debug("Connect to region: {$this->region}");

            $keyPair        = $aws->createKeyPair();
            Log::debug("Created Key pair: {$keyPair['keyName']}");
            $tagName        = $aws->createTagName();
            Log::debug("Created tag name: {$tagName}");
            $securityGroup  = $aws->createSecretGroup();
            Log::debug("Created SecurityGroups: {$securityGroup['securityGroupName']}");

            if (empty($keyPair) || empty($tagName) || empty($securityGroup)) {
                return;
            }

            $keyPairName    = $keyPair['keyName'];
            $keyPairPath    = $keyPair['path'];
            $groupId        = $securityGroup['securityGroupId'];
            $groupName      = $securityGroup['securityGroupName'];

            // Instance Create
            $newInstanceResponse = $aws->launchInstance(
                $this->bot,
                $this->instance,
                $this->user,
                $keyPairName,
                $groupName,
                $tagName,
                $this->params
            );

            if ($newInstanceResponse->hasKey('Instances')) {

                $instanceId = $newInstanceResponse->get('Instances')[0]['InstanceId'] ?? null;

                Log::info('Launched instance ' . $instanceId);

                CreditUsageHelper::startInstance(
                    $this->user,
                    self::START_INSTANCE_CREDIT,
                    $this->instance->id,
                    $tagName
                );

                $aws->waitUntil([$instanceId]);

                Log::info('wait until instance ' . $instanceId);

                $describeInstancesResponse = $aws->describeInstances([$instanceId], $this->region);

                Log::info('describe instances ' . $instanceId);

                if ($describeInstancesResponse->hasKey('Reservations')) {

                    $instanceArray  = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
                    $launchTime     = $instanceArray['LaunchTime'] ?? '';
                    $awsStatus      = $instanceArray['State']['Name'];

                    // store instance details in database
                    $botInstanceDetail = $this->instance->details()->latest()->first();
                    $botInstanceDetail->update([
                        'aws_security_group_id'     => $groupId,
                        'aws_security_group_name'   => $groupName,
                        'aws_public_dns'            => $instanceArray['PublicDnsName'] ?? '',
                        'aws_pem_file_path'         => $keyPairPath,
                        'is_in_queue'               => 0,
                        'start_time'                => $launchTime->format('Y-m-d H:i:s'),
                    ]);

                    $this->instance->update([
                        'tag_name'          => $tagName,
                        'tag_user_email'    => $this->user->email ?? '',
                        'aws_instance_id'   => $instanceArray['InstanceId'] ?? '',
                        'aws_public_ip'     => $instanceArray['PublicIpAddress'] ?? '',
                        'start_time'        => $launchTime->format('Y-m-d H:i:s'),
                    ]);

                    if ($awsStatus === BotInstance::STATUS_RUNNING) {
                        $this->instance->setAwsStatusRunning();
                    }

                    broadcast(new InstanceLaunched($this->instance, $this->user));
                }
            }

            Log::debug("Launch Instance Error:");
            Log::debug(print_r($newInstanceResponse, true));

        } catch (GuzzleException $exception) {
            $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $exception->getMessage());
            Log::debug("Error on catch GuzzleException : {$message}");
        } catch (Throwable $throwable) {
            $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $throwable->getMessage());
            Log::debug("Error on catch Throwable : {$message}");
        }
    }
}
