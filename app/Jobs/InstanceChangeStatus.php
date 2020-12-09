<?php

namespace App\Jobs;

use App\AwsRegion;
use App\BotInstance;
use App\BotInstancesDetails;
use App\Events\InstanceLaunched;
use App\Helpers\CommonHelper;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Illuminate\Support\Facades\Storage;

class InstanceChangeStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var BotInstance
     */
    protected $instance;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var BotInstancesDetails
     */
    protected $details;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $currentDate;

    /**
     * @var AwsRegion
     */
    protected $region;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     * @param User $user
     * @param AwsRegion $region
     * @param string $status
     */
    public function __construct(BotInstance $instance, User $user, AwsRegion $region, string $status)
    {
        $this->instance     = $instance;
        $this->user         = $user;
        $this->region       = $region;
        $this->details      = $instance->details()->latest()->first();
        $this->currentDate  = Carbon::now()->toDateTimeString();
        $this->status       = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        Log::debug("Starting InstanceChangeStatus for  {$this->instance->id}  status  {$this->status}");

        $aws = new Aws;
        $aws->ec2Connection($this->region->code);

        switch ($this->status) {
            case BotInstance::STATUS_RUNNING:
                $this->setStatusRunning($aws);
                break;
            case BotInstance::STATUS_STOPPED:
                $this->setStatusStopped($aws);
                break;
            case BotInstance:: STATUS_RESTART;
                $this->restartBot($aws);
                break;
            default:
                $this->setStatusTerminated($aws);
                break;
        }

        Log::info('Completed InstanceChangeStatus for ' . $this->instance->id ?? '');
    }
    



    /**
     * @param Aws $aws
     * @return string|null
     */
    private function getCurrentInstanceStatus(Aws $aws): ?string
    {
        $result = $aws->describeInstances([$this->instance->aws_instance_id], $this->region->code);

        if ($result->hasKey('Reservations')) {
            $reservations = collect($result->get('Reservations'));
            if ($reservations->isNotEmpty()) {
                $instance = $reservations->first()['Instances'][0];
                return $instance['State']['Name'];
            }
        }

        return null;
    }


    /**
     * @param Aws $aws
     * @return void
     */
    
    private function restartBot(Aws $aws){
        Log::debug("restart  {$this->instance->id}");
        $current = $this->getCurrentInstanceStatus($aws);
        Log::debug("current status   {$current}");
        if ($current === BotInstance::STATUS_RUNNING) {
           // Log::debug("botinstance ip : {$this->instance->aws_public_ip} ");
            $instanceDetail =  $this->instance->details()->latest()->first();
            //Log::debug("instanceDetail {$instanceDetail} ");
            $result = $aws->getKeyPairObject($instanceDetail->aws_pem_file_path ?? '');
            if (empty($result)) {
                return $this->error(__('user.error'), __('user.access_denied'));
            }
            $body = $result->get('Body');
            $dir = sys_get_temp_dir();
            $tmp = tempnam($dir, "foo");
            $tmp = $tmp.'.pem';
            Log::debug("tmp file {$tmp} ");
            file_put_contents($tmp, $body);
            $key = new RSA();
            //$key->loadKey($body);
            Log::debug("use file");
            $key->loadKey(file_get_contents($tmp));
           
            //echo "key ".$key;
           
            $ssh = new SSH2($this->instance->aws_public_ip);
            if (!$ssh->login('ubuntu', $key)) {
                Log::debug("ssh login failed to {$this->instance->aws_public_ip}");
                return null;
            }
            $ssh->exec('sudo pkill -f node;  sudo pkill -f chromium;', function ($str) {
                Log::debug($str);
            });
            sleep(5);
            $ssh->exec('sudo /etc/rc.local start', function ($str) {
                Log::debug($str);
            });
            dispatch(new SyncS3Objects($this->instance));
            broadcast(new InstanceLaunched($this->instance, $this->user));

        } else {
            Log::debug("We can only restart stopped or terminated bot!");

        }
    }



    /**
     * @param Aws $aws
     * @return void
     */
    private function setStatusRunning(Aws $aws)
    {
        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_STOPPED) {

            $result = $aws->startInstance([$this->instance->aws_instance_id]);

            if ($result->hasKey('StartingInstances')) {

                $aws->waitUntil([$this->instance->aws_instance_id]);

                $info = $this->getPublicIpAddressAndDns($aws);

                if ($info->isNotEmpty()) {

                    $this->instance->setAwsStatusRunning();

                    $newInstanceDetail = $this->details->replicate([
                        'end_time', 'total_time'
                    ]);

                    $newInstanceDetail->fill([
                        'start_time'        => $this->currentDate,
                        'aws_public_dns'    => $info['dns']
                    ]);

                    $newInstanceDetail->save();

                    $this->instance->update([
                        'aws_public_ip' => $info['ip'],
                        'start_time'    => $this->currentDate,
                    ]);
                }

                // Update directory tree on instance status change
                dispatch(new SyncS3Objects($this->instance));

                broadcast(new InstanceLaunched($this->instance, $this->user));
            }

        } else {
            // dispatch(new InstanceChangeStatus(
            //     $this->instance,
            //     $this->user,
            //     $this->region,
            //     $this->status)
            // )->delay(30);
            Log::debug(">>>>We can not start a bot which is already running!");
        }
    }

    /**
     * @param Aws $aws
     * @throws Exception
     */
    private function setStatusStopped(Aws $aws)
    {
        $current = $this->getCurrentInstanceStatus($aws);

        if ($current === BotInstance::STATUS_RUNNING) {

            $result = $aws->stopInstance([$this->instance->aws_instance_id]);

            if ($result->hasKey('StoppingInstances')) {

                $this->instance->setAwsStatusStopped();

                $this->updateUpTime();

                // Update directory tree on instance status change
                dispatch(new SyncS3Objects($this->instance));

                broadcast(new InstanceLaunched($this->instance, $this->user));
            }

        } else {
            // dispatch(new InstanceChangeStatus(
            //         $this->instance,
            //         $this->user,
            //         $this->region,
            //         $this->status)
            // )->delay(30);
            Log::debug(">>>>We can not stop a bot which is already stopped!");
        }
    }

    /**
     * @param Aws $aws
     * @throws Exception
     */
    private function setStatusTerminated(Aws $aws)
    {
        $terminateInstance = $aws->terminateInstance([$this->instance->aws_instance_id]);

        if ($terminateInstance->hasKey('TerminatingInstances')) {

            $result = collect($terminateInstance->get('TerminatingInstances'));

            $previousState = $result->map(function ($item, $key) {
                return $item['PreviousState']['Name'] ?? null;
            })->first();

            // Check whether old status was 'running'
            if ($previousState === BotInstance::STATUS_RUNNING) {
                $this->updateUpTime();
            }

            $this->instance->setAwsStatusTerminated();

            InstanceHelper::cleanUpTerminatedInstanceData($aws, $this->details);

            if ($this->region->created_instances > 0) {
                $this->region->decrement('created_instances');
            }

            // Update directory tree on instance status change
            dispatch(new SyncS3Objects($this->instance));

            broadcast(new InstanceLaunched($this->instance, $this->user));
        }
    }

    /**
     * @param Aws $aws
     * @return Collection
     */
    private function getPublicIpAddressAndDns(Aws $aws): Collection
    {
        $result = $aws->describeInstances([$this->instance->aws_instance_id], $this->region->code);

        if ($result->hasKey('Reservations')) {
            $reservations = collect($result->get('Reservations'));
            if ($reservations->isNotEmpty()) {
                $instance = $reservations->first()['Instances'][0];

                return collect([
                    'ip'    => $instance['PublicIpAddress'],
                    'dns'   => $instance['PublicDnsName']
                ]);
            }
        }

        return collect([]);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function updateUpTime(): void
    {
        $diffTime = CommonHelper::diffTimeInMinutes($this->details->start_time, $this->currentDate);

        $this->details->update([
            'end_time'      => $this->currentDate,
            'total_time'    => $diffTime
        ]);

        $upTime = $diffTime + $this->instance->total_up_time;

        $this->instance->update([
            'cron_up_time'  => 0,
            'total_up_time' => $upTime,
            'up_time'       => $upTime,
        ]);
    }
}
