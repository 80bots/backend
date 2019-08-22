<?php

namespace App\Jobs;

use App\AwsAmi;
use App\AwsRegion;
use App\Bot;
use App\Events\InstanceLaunched;
use App\Services\Aws;
use App\User;
use App\UserInstance;
use App\UserInstancesDetails;
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

    protected $data;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $region;

    /**
     * Create a new job instance.
     *
     * @param $id
     * @param $user
     * @param string $region
     */
    public function __construct($id, $user, $region = '')
    {
        $this->id   = $id;
        $this->user = $user;
        $regions    = Aws::getEc2Regions();

        if (! empty($regions) && in_array($region, $regions)) {
            $this->region = $region;
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
        Log::info('Starting instance for ' . $this->id);

        try {
            ini_set('memory_limit', '-1');

            $userInstance   = UserInstance::findOrFail($this->id);
            $bot            = Bot::findOrFail($userInstance->bot_id);

            if (empty($bot) || empty($userInstance)) {
                return false;
            }

            $aws = new Aws;
            $aws->ec2Connection($this->region);

            $keyPair = $aws->createKeyPair();

            if (empty($keyPair)) {
                return false;
            }

            $keyPairName    = $keyPair['keyName'];
            $keyPairPath    = $keyPair['path'];
            Log::info('Created Key pair');

            $tagName        = $aws->createTagName();
            Log::info('Created tag name');

            $securityGroup  = $aws->createSecretGroup();
            $groupId        = $securityGroup['securityGroupId'];
            $groupName      = $securityGroup['securityGroupName'];
            Log::info('Created SecurityGroups');

            // Instance Create
            $newInstanceResponse = $aws->launchInstance($keyPairName, $groupName, $bot, $tagName, $this->user);

            if ($newInstanceResponse->hasKey('Instances')) {
                $instanceId = $newInstanceResponse->get('Instances')[0]['InstanceId'] ?? null;

                Log::info('Launched instance ' . $instanceId);

                $waitUntilResponse = $aws->waitUntil([$instanceId]);

                $describeInstancesResponse = $aws->describeInstances([$instanceId]);

                if ($describeInstancesResponse->hasKey('Reservations')) {

                    $instanceArray  = $describeInstancesResponse->get('Reservations')[0]['Instances'][0];
                    $launchTime     = $instanceArray['LaunchTime'] ?? '';

                    // store instance details in database
                    $userInstance->fill([
                        'tag_name' => $tagName,
                        'aws_ami_name' => $bot->aws_ami_name ?? '',
                        'aws_instance_id' => $instanceArray['InstanceId'] ?? '',
                        'aws_ami_id' => $instanceArray['ImageId'] ??  '',
                        'aws_security_group_id' => $groupId,
                        'aws_security_group_name' => $groupName,
                        'aws_public_ip' => $instanceArray['PublicIpAddress'] ?? '',
                        'status' => 'running',
                        'aws_public_dns' => $instanceArray['PublicDnsName'] ?? '',
                        'aws_pem_file_path' => $keyPairPath,
                        'created_at' => $launchTime->format('Y-m-d H:i:s'),
                        'is_in_queue' => 0,
                        'tag_user_email' => $this->user ? $this->user->email : null,
                    ]);

                    if($userInstance->save()){
                        Log::debug('Updated Instance : '.json_encode($userInstance));
                        Session::put('instance_id','');

                        $userInstanceDetail = new UserInstancesDetails;
                        $userInstanceDetail->fill([
                            'user_instance_id'  => $userInstance->id,
                            'start_time'        => $launchTime->format('Y-m-d H:i:s')
                        ]);
                        $userInstanceDetail->save();
                        session()->flash('success', 'Instance Created successfully');
                        broadcast(new InstanceLaunched($userInstance, $this->user));
                    }
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
