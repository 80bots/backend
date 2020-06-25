<?php

namespace App\Jobs;

use App\Bot;
use App\BotInstance;
use App\BotInstancesDetails;
use App\Events\InstanceLaunched;
use App\Helpers\InstanceHelper;
use App\AboutInstance;
use App\Services\Aws;
use App\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use function Psy\debug;

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
     * @var BotInstancesDetails
     */
    protected $instanceDetail;

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
     * @var string|null
     */
    protected $ip;

    /**
     * Create a new job instance.
     * @param Bot $bot
     * @param BotInstance $instance
     * @param User $user
     * @param array|null $params
     * @param string|null $ip
     */
    public function __construct(Bot $bot, BotInstance $instance, User $user, ?array $params, ?string $ip)
    {
        $this->bot              = $bot;
        $this->instance         = $instance;
        $this->instanceDetail   = $this->instance->details()->latest()->first();
        $this->user             = $user;
        $regions                = Aws::getEc2Regions();
        $this->params           = $params;
        $this->ip               = $ip;

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

            $awsData = InstanceHelper::createAwsKeyAndGroup($aws, $this->ip);

            if (empty($awsData)) {
                return;
            }

            // Instance Create
            $newInstanceResponse = $aws->launchInstance(
                $this->bot,
                $this->instance,
                $this->user,
                $awsData['keyPairName'],
                $awsData['groupName'],
                $awsData['tagName'],
                $this->params
            );

            if (empty($newInstanceResponse)) {
                return;
            }

            if ($newInstanceResponse->hasKey('Instances')) {

                $instanceId = $newInstanceResponse->get('Instances')[0]['InstanceId'] ?? null;

                Log::info('Launched instance ' . $instanceId);

                $aws->waitUntil([$instanceId]);

                Log::info('wait until instance ' . $instanceId);

                $describeInstancesResponse = $aws->describeInstances([$instanceId], $this->region);

                Log::info('describe instances ' . $instanceId);

                Log::debug(print_r($describeInstancesResponse, true));

                if ($describeInstancesResponse->hasKey('Reservations')) {

                    Log::info('update instance data: ' . $instanceId);

                    $instanceArray  = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
                    $launchTime     = $instanceArray['LaunchTime'] ?? '';
                    $awsStatus      = $instanceArray['State']['Name'];

                    // store instance details in database
                    $this->instanceDetail->update([
                        'aws_security_group_id'     => $awsData['groupId'],
                        'aws_security_group_name'   => $awsData['groupName'],
                        'aws_public_dns'            => $instanceArray['PublicDnsName'] ?? '',
                        'aws_pem_file_path'         => $awsData['keyPairPath'],
                        'start_time'                => $launchTime->format('Y-m-d H:i:s'),
                    ]);

                    $this->instance->update([
                        'tag_name'          => $awsData['tagName'],
                        'aws_instance_id'   => $instanceArray['InstanceId'] ?? '',
                        'aws_public_ip'     => $instanceArray['PublicIpAddress'] ?? '',
                        'start_time'        => $launchTime->format('Y-m-d H:i:s'),
                    ]);

                    //
                    $this->addInstanceInfo();

                    if ($awsStatus === BotInstance::STATUS_RUNNING) {
                        $this->instance->setAwsStatusRunning();
                    }
                }

                Log::info('completed handle ' . $instanceId);
            }

        } catch (GuzzleException $exception) {

            $pos = strpos($exception->getMessage(), '<?xml version="1.0" encoding="UTF-8"?>');

            if ($pos === false) {
                Log::error("Error on catch Throwable : {$exception->getMessage()}");
            } else {
                $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $exception->getMessage());
                Log::error("Error on catch GuzzleException : {$message}");
            }

            $this->removeInstance();

        } catch (Throwable $throwable) {

            $pos = strpos($throwable->getMessage(), '<?xml version="1.0" encoding="UTF-8"?>');

            if ($pos === false) {
                Log::error("Error on catch Throwable : {$throwable->getMessage()}");
            } else {
                $message = preg_replace('/^(.*)<\?xml version="1\.0" encoding="UTF-8"\?>/s', '', $throwable->getMessage());
                Log::error("Error on catch Throwable : {$message}");
            }

            $this->removeInstance();
        }

        broadcast(new InstanceLaunched($this->instance, $this->user));
    }

    /**
     * @return void
     */
    private function removeInstance()
    {
        Log::debug("removeInstance");
        Log::debug(print_r($this->instance, true));

        if (! empty($this->instance)) {
            $this->instance->setAwsStatusTerminated();
        }
    }

    /**
     * @return void
     */
    private function addInstanceInfo()
    {
        try {

            Log::debug("Start addInstanceInfo");

            $details = $this->instanceDetail->only('aws_instance_type', 'aws_storage_gb', 'aws_image_id');
            $parameters = json_encode($this->params);
            $data = array_merge([
                'instance_id'               => $this->instance->id,
                'tag_name'                  => $this->instance->tag_name,
                'tag_user_email'            => $this->instance->tag_user_email,
                'bot_path'                  => $this->bot->path,
                'bot_name'                  => $this->bot->name,
                'params'                    => $parameters,
                'aws_region'                => $this->instance->region->code,
                'aws_custom_script'         => $this->bot->aws_custom_script,
                'aws_custom_package_json'   => $this->bot->aws_custom_package_json,
            ], $details);

            Log::debug(print_r($data, true));

            AboutInstance::create($data);

            Log::debug("Completed addInstanceInfo");

        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
        }
    }
}
