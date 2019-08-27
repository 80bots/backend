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
     * Create a new job instance.
     *
     * @param Bot $bot
     * @param BotInstance $instance
     * @param User $user
     */
    public function __construct(Bot $bot, BotInstance $instance, User $user)
    {
        $this->bot      = $bot;
        $this->instance = $instance;
        $this->user     = $user;
        $regions        = Aws::getEc2Regions();

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
                $tagName
            );

            if ($newInstanceResponse->hasKey('Instances')) {
                $instanceId = $newInstanceResponse->get('Instances')[0]['InstanceId'] ?? null;

                Log::info('Launched instance ' . $instanceId);

                $waitUntilResponse = $aws->waitUntil([$instanceId]);

                $describeInstancesResponse = $aws->describeInstances([$instanceId]);

                if ($describeInstancesResponse->hasKey('Reservations')) {

                    $instanceArray  = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
                    $launchTime     = $instanceArray['LaunchTime'] ?? '';

                    // store instance details in database
//                    $this->instance->fill([
//                        'tag_name' => $tagName,
//                        'aws_ami_name' => $bot->aws_ami_name ?? '',
//                        'aws_instance_id' => $instanceArray['InstanceId'] ?? '',
//                        'aws_ami_id' => $instanceArray['ImageId'] ??  '',
//                        'aws_security_group_id' => $groupId,
//                        'aws_security_group_name' => $groupName,
//                        'aws_public_ip' => $instanceArray['PublicIpAddress'] ?? '',
//                        'status' => 'running',
//                        'aws_public_dns' => $instanceArray['PublicDnsName'] ?? '',
//                        'aws_pem_file_path' => $keyPairPath,
//                        'created_at' => $launchTime->format('Y-m-d H:i:s'),
//                        'is_in_queue' => 0,
//                        'tag_user_email' => $this->user ? $this->user->email : null,
//                    ]);
//
//                    if($this->instance->save()){
//                        $userInstanceDetail = new UserInstancesDetails;
//                        $userInstanceDetail->fill([
//                            'user_instance_id'  => $this->instance->id,
//                            'start_time'        => $launchTime->format('Y-m-d H:i:s')
//                        ]);
//                        $userInstanceDetail->save();
//                        broadcast(new InstanceLaunched($this->instance, $this->user));
//                    }
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
