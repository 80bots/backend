<?php

namespace App\Jobs;

use App\Bot;
use App\Http\Controllers\AwsConnectionController;
use App\Http\Controllers\BotInstanceController;
use App\Events\dispatchedInstanceEvent;
use App\Events\InstanceCreation;
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
     * Create a new job instance.
     *
     * @param $id
     * @param $user
     */
    public function __construct($id, $user)
    {
        $this->id   = $id;
        $this->user = $user;
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

            if (empty($bot)) {
                session()->flash('error', 'Bot Not Found Please Try Again');
                return response()->json(['message' => 'Bot Not Found Please Try Again'], 404);
            }

            $aws            = new Aws;
            $keyPair        = $aws->createKeyPair();

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
                        broadcast(new dispatchedInstanceEvent($userInstance));

                        if(! empty($this->user) && $this->user->hasRole('User')) {
                            broadcast(new InstanceCreation($this->user, $userInstance));
                        }
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
