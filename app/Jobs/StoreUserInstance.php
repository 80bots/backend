<?php

namespace App\Jobs;

use App\Bot;
use App\Events\InstanceLaunched;
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
     * @return bool
     */
    public function handle()
    {
        Log::info('Starting instance for ' . $this->instance->id ?? '');

        try {
            ini_set('memory_limit', '-1');

            $aws = new Aws;
            $aws->ec2Connection($this->region);

            $keyPair        = $aws->createKeyPair();
            Log::info('Created Key pair');
            $tagName        = $aws->createTagName();
            Log::info('Created tag name');
            $securityGroup  = $aws->createSecretGroup();
            Log::info('Created SecurityGroups');

            if (empty($keyPair) || empty($tagName) || empty($securityGroup)) {
                return false;
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

                $aws->waitUntil([$instanceId]);

                Log::info('wait until instance ' . $instanceId);

                $describeInstancesResponse = $aws->describeInstances([$instanceId]);

                Log::info('describe instances ' . $instanceId);

                if ($describeInstancesResponse->hasKey('Reservations')) {

                    Log::debug(print_r($describeInstancesResponse->get('Reservations'), true));

                    $instanceArray  = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
                    $launchTime     = $instanceArray['LaunchTime'] ?? '';
                    $awsStatus      = $instanceArray['State']['Name'];

                    // store instance details in database
                    $botInstanceDetail = $this->instance->details()->latest()->first();
                    $botInstanceDetail->update([
                        'tag_name' => $tagName,
                        'tag_user_email' => $this->user->email ?? '',
                        'aws_instance_id' => $instanceArray['InstanceId'] ?? '',
                        'aws_security_group_id' => $groupId,
                        'aws_security_group_name' => $groupName,
                        'aws_public_ip' => $instanceArray['PublicIpAddress'] ?? '',
                        'aws_public_dns' => $instanceArray['PublicDnsName'] ?? '',
                        'aws_pem_file_path' => $keyPairPath,
                        'is_in_queue' => 0,
                        'start_time' => $launchTime->format('Y-m-d H:i:s'),
                    ]);

                    if ($awsStatus === BotInstance::STATUS_RUNNING) {
                        $this->instance->setAwsStatusRunning();
                    }

                    broadcast(new InstanceLaunched($this->instance, $this->user));
                }

                return true;
            }

            return false;

        } catch (Throwable $throwable) {
            Log::debug("Error on catch : {$throwable->getMessage()}");
            return false;
        } catch (GuzzleException $expression) {
            Log::debug("Error on catch : {$expression->getMessage()}");
            return false;
        }
    }
}
